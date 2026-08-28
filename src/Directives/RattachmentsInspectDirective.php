<?php

declare(strict_types=1);

namespace AndyDefer\LaravelRattachments\Directives;

use AndyDefer\ConsoleWriter\Console\Components\KeyValue;
use AndyDefer\Directive\AbstractDirective;
use AndyDefer\Directive\Enums\ExitCode;
use AndyDefer\DomainStructures\Collections\Utility\StringTypedCollection;
use AndyDefer\DomainStructures\Utils\MapCollection;
use AndyDefer\LaravelRattachments\Contracts\RattachmentInterface;
use AndyDefer\LaravelRattachments\Contracts\Services\ConstraintDiscoveryServiceInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * CLI directive for inspecting rattachments constraints and connections.
 *
 * This directive provides insight into attachment constraints defined by models
 * and existing connections in the database. It helps developers understand
 * and debug attachment relationships.
 *
 * @example
 * // Inspect specific models
 * ./bin/app rattachments:inspect [App.Models.User, App.Models.Hospital]
 *
 * // Show only constraints
 * ./bin/app rattachments:inspect [App.Models.User] --constraints
 *
 * // Show only connections
 * ./bin/app rattachments:inspect [App.Models.User] --connections
 */
final class RattachmentsInspectDirective extends AbstractDirective
{
    private const CONSTRAINT_SECTION = '🔒 CONSTRAINTS';

    private const CONNECTION_SECTION = '🔗 EXISTING CONNECTIONS';

    private const DEFAULT_SOURCE = 'app.Models';

    private const LINE_LENGTH = 60;

    public function getSignature(): string
    {
        return 'rattachments:inspect 
                {models*}#"List of models to inspect (e.g., [App.Models.User, App.Models.Hospital])"
                {sources*}#"Directories to scan for discovery (e.g., [app.Models, tests.Fixtures.Models])"
                {--connections}#"Show existing connections in database"
                {--constraints}#"Show model constraints only"';
    }

    public function getDescription(): string
    {
        return 'Inspect rattachments constraints and existing connections for specific models.';
    }

    public function getAliases(): StringTypedCollection
    {
        return StringTypedCollection::from(['ri', 'rattachments:list']);
    }

    protected function execute(): ExitCode
    {
        $this->info('🔍 Inspecting rattachments...');
        $this->newLine();

        $showConnections = $this->getFlag('connections');
        $showConstraints = $this->getFlag('constraints');

        if (! $showConnections && ! $showConstraints) {
            $showConnections = true;
            $showConstraints = true;
        }

        $constrainedModels = $this->resolveModels();

        if ($showConstraints) {
            $this->displayConstraints($constrainedModels);
        }

        if ($showConnections) {
            $this->displayConnections($constrainedModels);
        }

        $this->newLine();
        $this->info('✅ Inspection completed');

        return ExitCode::SUCCESS;
    }

    private function resolveModels(): array
    {
        $models = $this->getVariadic('models');

        if (! empty($models)) {
            return $this->filterModels($models);
        }

        $this->info('No models specified. Discovering models from sources...');

        $sources = $this->getVariadic('sources');
        if (empty($sources)) {
            $sources = [self::DEFAULT_SOURCE];
            $this->info('No sources specified. Using default: '.self::DEFAULT_SOURCE);
        }

        $this->info('Scanning sources: '.implode(', ', $sources));

        $discoveryService = $this->getApplication()->make(ConstraintDiscoveryServiceInterface::class);

        return $discoveryService->discoverConstrainedModels($sources);
    }

    private function filterModels(array $models): array
    {
        $result = [];

        foreach ($models as $modelClass) {
            $modelClass = str_replace('.', '\\', $modelClass);

            if (! class_exists($modelClass)) {
                $this->warn("⚠️  Class not found: {$modelClass}");

                continue;
            }

            $reflection = new \ReflectionClass($modelClass);
            $implementsInterface = $reflection->implementsInterface(RattachmentInterface::class);

            try {
                $data = $this->buildModelData($modelClass, $implementsInterface);
                $result[$modelClass] = $data;
            } catch (\Exception $e) {
                $this->warn("⚠️  Error instantiating {$modelClass}: ".$e->getMessage());
            }
        }

        return $result;
    }

    private function buildModelData(string $modelClass, bool $implementsInterface): array
    {
        $allowedTargets = [];
        $uniqueTargets = [];
        $disallowedTargets = [];

        if ($implementsInterface) {
            /** @var RattachmentInterface $instance */
            $instance = new $modelClass;
            $allowedTargets = $instance->allowedTargets();

            $uniqueTargets = method_exists($instance, 'uniqueTargets')
                ? $instance->uniqueTargets()
                : [];

            $disallowedTargets = method_exists($instance, 'disallowedTargets')
                ? $instance->disallowedTargets()
                : [];
        }

        return [
            'allowedTargets' => $allowedTargets,
            'uniqueTargets' => $uniqueTargets,
            'disallowedTargets' => $disallowedTargets,
            'implementsInterface' => $implementsInterface,
        ];
    }

    private function displayConstraints(array $constrainedModels): void
    {
        $this->renderSectionHeader(self::CONSTRAINT_SECTION);

        if (empty($constrainedModels)) {
            $this->info('No constrained models found.');
            $this->newLine();

            return;
        }

        foreach ($constrainedModels as $modelClass => $data) {
            $this->renderModelConstraints($modelClass, $data);
        }
    }

    private function renderModelConstraints(string $modelClass, array $data): void
    {
        $shortName = $this->shortenClassName($modelClass);
        $this->line("📦 {$shortName}");
        $this->line("   FQCN: {$modelClass}");

        if (! ($data['implementsInterface'] ?? false)) {
            $this->line('   ℹ️  No constraints defined (does not implement RattachmentInterface)');
            $this->newLine();

            return;
        }

        $allowed = $data['allowedTargets'] ?? [];
        $disallowed = $data['disallowedTargets'] ?? [];
        $conflicts = array_intersect(array_keys($allowed), array_keys($disallowed));

        $this->renderAllowedTargets($allowed, $conflicts, $disallowed);
        $this->renderUniqueTargets($data['uniqueTargets'] ?? []);
        $this->renderDisallowedTargets($disallowed);

        if (! empty($conflicts)) {
            $this->renderConflictWarning($conflicts, $allowed, $disallowed);
        }

        $this->newLine();
    }

    private function renderAllowedTargets(array $allowed, array $conflicts, array $disallowed): void
    {
        $this->line('   ✅ Allowed targets:');

        if (empty($allowed)) {
            $this->line('      (none)');

            return;
        }

        $allowedData = MapCollection::from([]);
        foreach ($allowed as $target => $roles) {
            $shortTarget = $this->shortenClassName($target);
            $rolesLabel = $this->formatRoles($roles);

            if (in_array($target, $conflicts, true)) {
                $disallowedRoles = $disallowed[$target] ?? [];
                $rolesLabel = $this->appendConflictLabel($rolesLabel, $disallowedRoles);
            }

            $allowedData = $allowedData->put($shortTarget, $rolesLabel);
        }

        $this->getConsole()->raw(KeyValue::renderWithValueColor($allowedData, 'green'));
    }

    private function renderUniqueTargets(array $unique): void
    {
        $this->line('   🔒 Unique targets:');

        if (empty($unique)) {
            $this->line('      (none)');

            return;
        }

        $uniqueData = MapCollection::from([]);

        foreach ($unique as $target => $roles) {
            $shortTarget = $this->shortenClassName($target);

            if (empty($roles)) {
                $uniqueData = $uniqueData->put($shortTarget, 'one-to-one (any role)');
            } else {
                $rolesLabel = implode(', ', $this->formatRoles($roles));
                $uniqueData = $uniqueData->put($shortTarget, 'one-to-one (roles: '.$rolesLabel.')');
            }
        }

        $this->getConsole()->raw(KeyValue::renderWithValueColor($uniqueData, 'yellow'));
    }

    private function renderDisallowedTargets(array $disallowed): void
    {
        $this->line('   🚫 Disallowed targets:');

        if (empty($disallowed)) {
            $this->line('      (none)');

            return;
        }

        $disallowedData = MapCollection::from([]);
        foreach ($disallowed as $target => $roles) {
            $shortTarget = $this->shortenClassName($target);

            if (empty($roles)) {
                $disallowedData = $disallowedData->put($shortTarget, '🚫 All roles disallowed');
            } else {
                $rolesLabel = implode(', ', $this->formatRoles($roles));
                $disallowedData = $disallowedData->put($shortTarget, '🚫 Roles: '.$rolesLabel);
            }
        }

        $this->getConsole()->raw(KeyValue::renderWithValueColor($disallowedData, 'red'));
    }

    private function renderConflictWarning(array $conflicts, array $allowed, array $disallowed): void
    {
        $this->newLine();
        $this->line('   ⚠️  CONFLICT DETECTED: The following targets appear in both allowed and disallowed:');

        $conflictData = MapCollection::from([]);
        foreach ($conflicts as $target) {
            $shortTarget = $this->shortenClassName($target);
            $label = $this->buildConflictLabel($target, $allowed, $disallowed);
            $conflictData = $conflictData->put($shortTarget, $label);
        }

        $this->getConsole()->raw(KeyValue::renderWithValueColor($conflictData, 'magenta'));
    }

    private function buildConflictLabel(string $target, array $allowed, array $disallowed): string
    {
        $disallowedRoles = $disallowed[$target] ?? [];
        $allowedLabels = implode(', ', $this->formatRoles($allowed[$target] ?? []));

        if (empty($disallowedRoles)) {
            return '⚠️ All roles allowed but completely disallowed → DISALLOW WINS';
        }

        $disallowedLabels = implode(', ', $this->formatRoles($disallowedRoles));

        return "⚠️ Allowed: {$allowedLabels} | Disallowed: {$disallowedLabels} → DISALLOW WINS";
    }

    private function displayConnections(array $constrainedModels): void
    {
        $this->renderSectionHeader(self::CONNECTION_SECTION);

        if (! $this->tableExists('rattachments')) {
            $this->info('Table "rattachments" does not exist. Run migrations first.');
            $this->newLine();

            return;
        }

        $modelClasses = $this->extractModelClassesWithInterface($constrainedModels);

        if (empty($modelClasses)) {
            $this->info('No constrained models found. Nothing to display.');
            $this->newLine();

            return;
        }

        $connections = $this->fetchConnections($modelClasses);

        if ($connections->isEmpty()) {
            $this->info('No connections found in the database for the specified models.');
            $this->newLine();

            return;
        }

        $this->renderConnectionsSummary($connections);
        $this->renderRolesByConnection($connections);
        $this->newLine();
        $this->displayMissingConnectionsSuggestions($connections, $constrainedModels);
    }

    private function extractModelClassesWithInterface(array $constrainedModels): array
    {
        return array_keys(array_filter($constrainedModels, function ($data) {
            return $data['implementsInterface'] ?? false;
        }));
    }

    private function fetchConnections(array $modelClasses): Collection
    {
        return DB::table('rattachments')
            ->select('rattachable_type', 'target_type', DB::raw('COUNT(*) as count'))
            ->whereIn('rattachable_type', $modelClasses)
            ->orWhereIn('target_type', $modelClasses)
            ->groupBy('rattachable_type', 'target_type')
            ->orderBy('count', 'desc')
            ->get();
    }

    private function renderConnectionsSummary(Collection $connections): void
    {
        $this->line('📊 Found '.$connections->count().' connection types:');
        $this->newLine();

        $data = MapCollection::from([]);
        foreach ($connections as $conn) {
            $rattachable = $this->shortenClassName($conn->rattachable_type);
            $target = $this->shortenClassName($conn->target_type);
            $key = "{$rattachable} → {$target}";
            $data = $data->put($key, $conn->count.'x');
        }

        $this->getConsole()->raw(KeyValue::renderWithValueColor($data, 'green'));
        $this->newLine();
    }

    private function renderRolesByConnection(Collection $connections): void
    {
        $this->line('📋 Roles by connection:');
        $this->newLine();

        foreach ($connections as $conn) {
            $roles = DB::table('rattachments')
                ->where('rattachable_type', $conn->rattachable_type)
                ->where('target_type', $conn->target_type)
                ->select('role', DB::raw('COUNT(*) as count'))
                ->groupBy('role')
                ->get();

            $rattachableShort = $this->shortenClassName($conn->rattachable_type);
            $targetShort = $this->shortenClassName($conn->target_type);

            $this->line("   {$rattachableShort} → {$targetShort}:");

            $roleData = MapCollection::from([]);
            foreach ($roles as $role) {
                $roleLabel = $role->role ?? 'null';
                $roleData = $roleData->put($roleLabel, $role->count);
            }

            $this->getConsole()->raw(KeyValue::renderWithValueColor($roleData, 'cyan'));
            $this->newLine();
        }
    }

    private function displayMissingConnectionsSuggestions(Collection $connections, array $constrainedModels): void
    {
        $this->line('💡 Possible missing connections (based on constraints):');
        $this->newLine();

        $found = [];
        foreach ($connections as $conn) {
            $found[$conn->rattachable_type][] = $conn->target_type;
        }

        $missingData = MapCollection::from([]);

        foreach ($constrainedModels as $modelClass => $data) {
            if (! ($data['implementsInterface'] ?? false)) {
                continue;
            }

            $allowed = $data['allowedTargets'] ?? [];
            $shortModel = $this->shortenClassName($modelClass);

            foreach ($allowed as $targetClass => $roles) {
                if (! isset($found[$modelClass]) || ! in_array($targetClass, $found[$modelClass], true)) {
                    $shortTarget = $this->shortenClassName($targetClass);
                    $key = "{$shortModel} → {$shortTarget}";
                    $missingData = $missingData->put($key, '⚠️ Constraint defined but no connections found');
                }
            }
        }

        if ($missingData->isEmpty()) {
            $this->info('✅ All constraints have corresponding connections.');
        } else {
            $this->getConsole()->raw(KeyValue::renderWithValueColor($missingData, 'red'));
        }

        $this->newLine();
    }

    private function tableExists(string $table): bool
    {
        try {
            return DB::table('information_schema.tables')
                ->where('table_schema', DB::connection()->getDatabaseName())
                ->where('table_name', $table)
                ->exists();
        } catch (\Exception $e) {
            try {
                DB::statement("SELECT 1 FROM {$table} LIMIT 1");

                return true;
            } catch (\Exception $e) {
                return false;
            }
        }
    }

    private function renderSectionHeader(string $title): void
    {
        $this->line('═'.str_repeat('═', self::LINE_LENGTH));
        $this->line("  {$title}");
        $this->line('═'.str_repeat('═', self::LINE_LENGTH));
        $this->newLine();
    }

    private function shortenClassName(string $class): string
    {
        return basename(str_replace('\\', '/', $class));
    }

    private function formatRoles(array $roles): array
    {
        return array_map(
            fn ($role) => $role instanceof \BackedEnum ? $role->value : (string) $role,
            $roles
        );
    }

    private function appendConflictLabel(array $rolesLabel, array $disallowedRoles): string
    {
        if (empty($disallowedRoles)) {
            return implode(', ', $rolesLabel).' ⚠️ OVERRIDDEN BY DISALLOW (all roles blocked)';
        }

        $labels = implode(', ', $this->formatRoles($disallowedRoles));

        return implode(', ', $rolesLabel).' ⚠️ OVERRIDDEN BY DISALLOW ('.$labels.')';
    }
}
