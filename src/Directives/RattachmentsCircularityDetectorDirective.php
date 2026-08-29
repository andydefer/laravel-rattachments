<?php

declare(strict_types=1);

namespace AndyDefer\LaravelRattachments\Directives;

use AndyDefer\Directive\AbstractDirective;
use AndyDefer\Directive\Enums\ExitCode;
use AndyDefer\DomainStructures\Collections\Utility\StringTypedCollection;
use AndyDefer\LaravelRattachments\Contracts\RattachmentInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

/**
 * CLI directive for detecting circularity violations between rattachables and targets.
 *
 * This directive checks all pairs of models (rattachable → target) to detect
 * circular relationships and unique constraint circularities.
 *
 * @example
 * // Check specific models
 * ./bin/app rattachments:circularity [App.Models.User] [App.Models.Profile]
 *
 * // Check multiple models
 * ./bin/app rattachments:circularity [App.Models.User, App.Models.Doctor] [App.Models.Profile, App.Models.Hospital]
 *
 * // Check with skipping same-class notifications
 * ./bin/app rattachments:circularity [App.Models.User] [App.Models.Profile] --ignore-skipped
 */
final class RattachmentsCircularityDetectorDirective extends AbstractDirective
{
    private const LINE_LENGTH = 70;

    public function getSignature(): string
    {
        return 'rattachments:circularity 
                {rattachables*}#"List of rattachable models to check (e.g., [App.Models.User, App.Models.Doctor])" 
                {targets*}#"List of target models to check (e.g., [App.Models.Profile, App.Models.Hospital])"
                {--ignore-skipped}#"Do not display skipped items (same class, not implementing interface)"';
    }

    public function getDescription(): string
    {
        return 'Detect circularity violations between rattachables and targets.';
    }

    public function getAliases(): StringTypedCollection
    {
        return StringTypedCollection::from(['rc', 'rattachments:check-circularity']);
    }

    protected function execute(): ExitCode
    {
        $this->info('🔄 Checking circularity violations...');
        $this->newLine();

        $rattachables = $this->getVariadic('rattachables');
        $targets = $this->getVariadic('targets');
        $ignoreSkipped = $this->getFlag('ignore-skipped');

        if (empty($rattachables) || empty($targets)) {
            $this->error('❌ You must specify both rattachables and targets.');
            $this->newLine();
            $this->info('Usage: ./bin/app rattachments:circularity [Rattachables] [Targets]');

            return ExitCode::INVALID_ARGUMENT;
        }

        $rattachables = $this->normalizeAndUnique($rattachables);
        $targets = $this->normalizeAndUnique($targets);

        $this->info('📋 Rattachables: '.implode(', ', $rattachables));
        $this->info('📋 Targets: '.implode(', ', $targets));
        $this->newLine();

        $violations = $this->detectViolations($rattachables, $targets, $ignoreSkipped);

        if ($violations->isEmpty()) {
            $this->info('✅ No circularity violations detected.');
            $this->newLine();

            return ExitCode::SUCCESS;
        }

        $this->displayViolations($violations, $ignoreSkipped);

        $this->newLine();
        $this->info('✅ Circularity check completed');

        return ExitCode::SUCCESS;
    }

    private function normalizeAndUnique(array $items): array
    {
        $items = array_map(fn ($item) => str_replace('.', '\\', $item), $items);
        $items = array_unique($items);

        return array_values($items);
    }

    private function detectViolations(array $rattachables, array $targets, bool $ignoreSkipped): Collection
    {
        $violations = collect();

        foreach ($rattachables as $rattachableClass) {
            if (! class_exists($rattachableClass)) {
                $violations->push([
                    'type' => 'error',
                    'message' => "Class not found: {$rattachableClass}",
                ]);

                continue;
            }

            $rattachable = new $rattachableClass;

            if (! $rattachable instanceof RattachmentInterface) {
                if (! $ignoreSkipped) {
                    $violations->push([
                        'type' => 'skip',
                        'message' => "{$rattachableClass} does not implement RattachmentInterface. Skipped.",
                    ]);
                }

                continue;
            }

            foreach ($targets as $targetClass) {
                if (! class_exists($targetClass)) {
                    $violations->push([
                        'type' => 'error',
                        'message' => "Class not found: {$targetClass}",
                    ]);

                    continue;
                }

                // Skip si même classe
                if ($rattachableClass === $targetClass) {
                    if (! $ignoreSkipped) {
                        $violations->push([
                            'type' => 'skip',
                            'message' => "Skipped: {$rattachableClass} → {$targetClass} (same class)",
                        ]);
                    }

                    continue;
                }

                $target = new $targetClass;

                if (! $target instanceof RattachmentInterface) {
                    if (! $ignoreSkipped) {
                        $violations->push([
                            'type' => 'skip',
                            'message' => "{$targetClass} does not implement RattachmentInterface. Skipped.",
                        ]);
                    }

                    continue;
                }

                $this->checkPair($rattachable, $target, $violations);
            }
        }

        return $violations;
    }

    private function checkPair(
        Model&RattachmentInterface $rattachable,
        Model&RattachmentInterface $target,
        Collection &$violations
    ): void {
        $rattachableClass = $rattachable->getMorphClass();
        $targetClass = $target->getMorphClass();

        // ✅ Fusionner allowedTargets() + uniqueTargets()
        $rattachableAllowed = $this->getEffectiveAllowedTargets($rattachable);
        $targetAllowed = $this->getEffectiveAllowedTargets($target);

        // Vérifier si le rattachable autorise le target
        if (! isset($rattachableAllowed[$targetClass])) {
            return;
        }

        $rattachableRoles = $rattachableAllowed[$targetClass];

        // Vérifier si le target autorise le rattachable
        if (! isset($targetAllowed[$rattachableClass])) {
            return;
        }

        $targetRoles = $targetAllowed[$rattachableClass];

        // Trouver les rôles en commun
        $commonRoles = $this->findCommonRoles($rattachableRoles, $targetRoles);

        foreach ($commonRoles as $role) {
            $violations->push([
                'type' => 'circularity',
                'rattachable' => $rattachableClass,
                'target' => $targetClass,
                'role' => $role,
                'message' => sprintf(
                    '🔴 Circular relationship: %s → %s with role "%s" and %s → %s with same role.',
                    $rattachableClass,
                    $targetClass,
                    $role,
                    $targetClass,
                    $rattachableClass
                ),
            ]);

            // Vérifier aussi les uniqueTargets
            $this->checkUniqueCircularity($rattachable, $target, $role, $violations);
        }
    }

    private function checkUniqueCircularity(
        Model&RattachmentInterface $rattachable,
        Model&RattachmentInterface $target,
        string $role,
        Collection &$violations
    ): void {
        $rattachableClass = $rattachable->getMorphClass();
        $targetClass = $target->getMorphClass();

        $rattachableUnique = method_exists($rattachable, 'uniqueTargets')
            ? $rattachable->uniqueTargets()
            : [];

        $targetUnique = method_exists($target, 'uniqueTargets')
            ? $target->uniqueTargets()
            : [];

        // Vérifier si le rattachable a uniqueTargets sur le target
        $hasRattachableUnique = isset($rattachableUnique[$targetClass]);
        $hasTargetUnique = isset($targetUnique[$rattachableClass]);

        if (! $hasRattachableUnique || ! $hasTargetUnique) {
            return;
        }

        $rattachableUniqueRoles = $rattachableUnique[$targetClass];
        $targetUniqueRoles = $targetUnique[$rattachableClass];

        $rattachableHasRole = empty($rattachableUniqueRoles) || in_array($role, $rattachableUniqueRoles, true);
        $targetHasRole = empty($targetUniqueRoles) || in_array($role, $targetUniqueRoles, true);

        if ($rattachableHasRole && $targetHasRole) {
            $violations->push([
                'type' => 'unique_circularity',
                'rattachable' => $rattachableClass,
                'target' => $targetClass,
                'role' => $role,
                'message' => sprintf(
                    '🔴 Circular unique constraint: %s → %s with role "%s" and %s → %s with same role.',
                    $rattachableClass,
                    $targetClass,
                    $role,
                    $targetClass,
                    $rattachableClass
                ),
            ]);
        }
    }

    /**
     * Fusionne allowedTargets() et uniqueTargets() pour la détection de circularité.
     *
     * @return array<string, array<int, string>>
     */
    private function getEffectiveAllowedTargets(RattachmentInterface $model): array
    {
        $allowed = $model->allowedTargets();
        $unique = $model->uniqueTargets();

        $result = $allowed;

        foreach ($unique as $targetClass => $roles) {
            if (! isset($result[$targetClass])) {
                $result[$targetClass] = [];
            }

            foreach ($roles as $role) {
                if (! in_array($role, $result[$targetClass], true)) {
                    $result[$targetClass][] = $role;
                }
            }
        }

        return $result;
    }

    private function findCommonRoles(array $rattachableRoles, array $targetRoles): array
    {
        $rattachableValues = array_map(
            fn ($r) => $r instanceof \BackedEnum ? $r->value : (string) $r,
            $rattachableRoles
        );

        $targetValues = array_map(
            fn ($r) => $r instanceof \BackedEnum ? $r->value : (string) $r,
            $targetRoles
        );

        return array_values(array_intersect($rattachableValues, $targetValues));
    }

    private function displayViolations(Collection $violations, bool $ignoreSkipped): void
    {
        $this->renderSectionHeader('🚨 VIOLATIONS DETECTED');

        $circularities = $violations->filter(fn ($v) => $v['type'] === 'circularity');
        $uniqueCircularities = $violations->filter(fn ($v) => $v['type'] === 'unique_circularity');
        $skips = $violations->filter(fn ($v) => $v['type'] === 'skip');
        $errors = $violations->filter(fn ($v) => $v['type'] === 'error');

        if ($circularities->isNotEmpty()) {
            $this->line('   🔄 Circular relationships:');
            $this->newLine();

            foreach ($circularities as $violation) {
                $this->error('   '.$violation['message']);
                $this->newLine();
            }
        }

        if ($uniqueCircularities->isNotEmpty()) {
            $this->line('   🔒 Circular unique constraints:');
            $this->newLine();

            foreach ($uniqueCircularities as $violation) {
                $this->error('   '.$violation['message']);
                $this->newLine();
            }
        }

        // Afficher les skips uniquement si ignore-skipped n'est pas actif
        if (! $ignoreSkipped && $skips->isNotEmpty()) {
            $this->line('   ⏭️  Skipped:');
            $this->newLine();

            foreach ($skips as $violation) {
                $this->warn('   '.$violation['message']);
                $this->newLine();
            }
        }

        if ($errors->isNotEmpty()) {
            $this->line('   ❌ Errors:');
            $this->newLine();

            foreach ($errors as $violation) {
                $this->error('   '.$violation['message']);
                $this->newLine();
            }
        }

        $totalViolations = $circularities->count() + $uniqueCircularities->count();
        $this->newLine();

        if ($ignoreSkipped && $skips->isNotEmpty()) {
            $this->info('ℹ️  Skipped items hidden (use without --ignore-skipped to see them)');
            $this->newLine();
        }

        $this->error("⚠️  Total violations found: {$totalViolations}");
    }

    private function renderSectionHeader(string $title): void
    {
        $this->line('═'.str_repeat('═', self::LINE_LENGTH));
        $this->line("  {$title}");
        $this->line('═'.str_repeat('═', self::LINE_LENGTH));
        $this->newLine();
    }
}
