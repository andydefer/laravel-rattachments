<?php

declare(strict_types=1);

namespace AndyDefer\LaravelRattachments\Directives;

use AndyDefer\ConsoleWriter\Console\Components\KeyValue;
use AndyDefer\Directive\AbstractDirective;
use AndyDefer\Directive\Enums\ExitCode;
use AndyDefer\DomainStructures\Collections\Utility\StringTypedCollection;
use AndyDefer\DomainStructures\Utils\MapCollection;
use AndyDefer\LaravelRattachments\Contracts\RattachmentConstraintsInterface;
use AndyDefer\LaravelRattachments\Contracts\Services\ConstraintDiscoveryServiceInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

final class RattachmentsInspectDirective extends AbstractDirective
{
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

        $models = $this->getVariadic('models');
        $sources = $this->getVariadic('sources');
        $showConnections = $this->getFlag('connections');
        $showConstraints = $this->getFlag('constraints');

        if (! $showConnections && ! $showConstraints) {
            $showConnections = true;
            $showConstraints = true;
        }

        if (! empty($models)) {
            $constrainedModels = $this->filterModels($models);
        } else {
            $this->info('No models specified. Discovering models from sources...');

            if (empty($sources)) {
                $sources = ['app.Models'];
                $this->info('No sources specified. Using default: app.Models');
            }

            $this->info('Scanning sources: '.implode(', ', $sources));

            $discoveryService = $this->getApplication()->make(ConstraintDiscoveryServiceInterface::class);
            $constrainedModels = $discoveryService->discoverConstrainedModels($sources);
        }

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
            $implementsInterface = $reflection->implementsInterface(RattachmentConstraintsInterface::class);

            try {
                $allowedTargets = [];
                $uniqueTargets = [];

                if ($implementsInterface) {
                    /** @var RattachmentConstraintsInterface $instance */
                    $instance = new $modelClass;
                    $allowedTargets = $instance->allowedTargets();
                    $uniqueTargets = method_exists($instance, 'uniqueTargets')
                        ? $instance->uniqueTargets()
                        : [];
                }

                $result[$modelClass] = [
                    'allowedTargets' => $allowedTargets,
                    'uniqueTargets' => $uniqueTargets,
                    'implementsInterface' => $implementsInterface,
                ];
            } catch (\Exception $e) {
                $this->warn("⚠️  Error instantiating {$modelClass}: ".$e->getMessage());

                continue;
            }
        }

        return $result;
    }

    private function displayConstraints(array $constrainedModels): void
    {
        $this->line('═'.str_repeat('═', 60));
        $this->line('  🔒 CONSTRAINTS');
        $this->line('═'.str_repeat('═', 60));
        $this->newLine();

        if (empty($constrainedModels)) {
            $this->info('No constrained models found.');
            $this->newLine();

            return;
        }

        foreach ($constrainedModels as $modelClass => $data) {
            $shortName = basename(str_replace('\\', '/', $modelClass));
            $this->line("📦 {$shortName}");
            $this->line("   FQCN: {$modelClass}");

            $implementsInterface = $data['implementsInterface'] ?? true;

            if (! $implementsInterface) {
                $this->line('   ℹ️  No constraints defined (does not implement RattachmentConstraintsInterface)');
                $this->newLine();

                continue;
            }

            $allowed = $data['allowedTargets'] ?? [];
            $this->line('   ✅ Allowed targets:');

            if (empty($allowed)) {
                $this->line('      (none)');
            } else {
                $allowedData = MapCollection::from([]);
                foreach ($allowed as $target => $roles) {
                    // ✅ Afficher le FQCN complet, pas seulement le nom court
                    $rolesLabel = array_map(
                        fn ($role) => $role instanceof \BackedEnum ? $role->value : (string) $role,
                        $roles
                    );
                    $allowedData = $allowedData->put($target, implode(', ', $rolesLabel));
                }
                $this->getConsole()->raw(KeyValue::renderWithValueColor($allowedData, 'green'));
            }

            $unique = $data['uniqueTargets'] ?? [];
            $this->line('   🔒 Unique targets:');
            if (empty($unique)) {
                $this->line('      (none)');
            } else {
                $uniqueData = MapCollection::from([]);
                foreach ($unique as $target) {
                    // ✅ Afficher le FQCN complet
                    $uniqueData = $uniqueData->put($target, 'one-to-one');
                }
                $this->getConsole()->raw(KeyValue::renderWithValueColor($uniqueData, 'yellow'));
            }

            $this->newLine();
        }
    }

    private function displayConnections(array $constrainedModels): void
    {
        $this->line('═'.str_repeat('═', 60));
        $this->line('  🔗 EXISTING CONNECTIONS');
        $this->line('═'.str_repeat('═', 60));
        $this->newLine();

        if (! $this->tableExists('rattachments')) {
            $this->info('Table "rattachments" does not exist. Run migrations first.');
            $this->newLine();

            return;
        }

        // ✅ Filtrer uniquement les modèles qui implémentent l'interface
        $modelClasses = array_keys(array_filter($constrainedModels, function ($data) {
            return $data['implementsInterface'] ?? true;
        }));

        if (empty($modelClasses)) {
            $this->info('No constrained models found. Nothing to display.');
            $this->newLine();

            return;
        }

        $connections = DB::table('rattachments')
            ->select('rattachable_type', 'target_type', DB::raw('COUNT(*) as count'))
            ->whereIn('rattachable_type', $modelClasses)
            ->orWhereIn('target_type', $modelClasses)
            ->groupBy('rattachable_type', 'target_type')
            ->orderBy('count', 'desc')
            ->get();

        if ($connections->isEmpty()) {
            $this->info('No connections found in the database for the specified models.');
            $this->newLine();

            return;
        }

        $this->line('📊 Found '.$connections->count().' connection types:');
        $this->newLine();

        $data = MapCollection::from([]);
        foreach ($connections as $conn) {
            $rattachable = basename(str_replace('\\', '/', $conn->rattachable_type));
            $target = basename(str_replace('\\', '/', $conn->target_type));
            $key = "{$rattachable} → {$target}";
            $data = $data->put($key, $conn->count.'x');
        }

        $this->getConsole()->raw(KeyValue::renderWithValueColor($data, 'green'));
        $this->newLine();

        $this->line('📋 Roles by connection:');
        $this->newLine();

        foreach ($connections as $conn) {
            $roles = DB::table('rattachments')
                ->where('rattachable_type', $conn->rattachable_type)
                ->where('target_type', $conn->target_type)
                ->select('role', DB::raw('COUNT(*) as count'))
                ->groupBy('role')
                ->get();

            $rattachableShort = basename(str_replace('\\', '/', $conn->rattachable_type));
            $targetShort = basename(str_replace('\\', '/', $conn->target_type));

            $this->line("   {$rattachableShort} → {$targetShort}:");

            $roleData = MapCollection::from([]);
            foreach ($roles as $role) {
                $roleLabel = $role->role ?? 'null';
                $roleData = $roleData->put($roleLabel, $role->count);
            }

            $this->getConsole()->raw(KeyValue::renderWithValueColor($roleData, 'cyan'));
            $this->newLine();
        }

        $this->newLine();
        $this->displayMissingConnectionsSuggestions($connections, $constrainedModels);
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
            // ✅ Vérifier si la clé existe
            $implementsInterface = $data['implementsInterface'] ?? true;
            if (! $implementsInterface) {
                continue;
            }

            $allowed = $data['allowedTargets'] ?? [];
            $shortModel = basename(str_replace('\\', '/', $modelClass));

            foreach ($allowed as $targetClass => $roles) {
                if (! isset($found[$modelClass]) || ! in_array($targetClass, $found[$modelClass])) {
                    $shortTarget = basename(str_replace('\\', '/', $targetClass));
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
}
