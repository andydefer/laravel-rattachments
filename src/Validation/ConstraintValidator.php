<?php

declare(strict_types=1);

namespace AndyDefer\LaravelRattachments\Validation;

use AndyDefer\LaravelRattachments\Contracts\RattachmentInterface;
use AndyDefer\LaravelRattachments\Contracts\Validation\ConstraintValidatorInterface;
use AndyDefer\LaravelRattachments\Enums\UnknownRole;
use AndyDefer\LaravelRattachments\Records\RattachmentFilterRecord;
use AndyDefer\LaravelRattachments\Repositories\RattachmentRepository;
use AndyDefer\Repository\Contracts\EnumerableInterface;
use AndyDefer\Repository\Records\FindByRecord;
use Illuminate\Database\Eloquent\Model;
use RuntimeException;

final class ConstraintValidator implements ConstraintValidatorInterface
{
    public function __construct(
        private readonly RattachmentRepository $repository,
    ) {}

    public function validateConstraints(
        Model&RattachmentInterface $rattachable,
        Model&RattachmentInterface $target,
        EnumerableInterface $role
    ): void {
        $this->validateSelfAttachment($rattachable, $target);

        $targetClass = $target->getMorphClass();

        $this->validateDisallowedTargets($rattachable, $targetClass, $role);
        $this->validateAllowedTargets($rattachable, $targetClass, $role);
        $this->validateCircularity($rattachable, $target, $role);
    }

    public function validateUniqueConstraints(
        Model&RattachmentInterface $rattachable,
        Model&RattachmentInterface $target,
        EnumerableInterface $role
    ): void {
        $this->validateUniqueCircularity($rattachable, $target, $role);

        $uniqueTargets = $rattachable->uniqueTargets();

        $targetClass = $target->getMorphClass();

        if (! array_key_exists($targetClass, $uniqueTargets)) {
            return;
        }

        $uniqueRoles = $uniqueTargets[$targetClass];

        $filterData = [
            'rattachable_type' => $rattachable->getMorphClass(),
            'rattachable_id' => $rattachable->getKey(),
            'target_type' => $targetClass,
        ];

        if (! empty($uniqueRoles)) {
            if (! $this->isRoleInArray($role, $uniqueRoles)) {
                return;
            }
            $filterData['role'] = $role->getValue();
        }

        $filter = RattachmentFilterRecord::from($filterData);

        $findByRecord = new FindByRecord(
            filters: $filter,
            limit: 1,
        );

        $exists = $this->repository->findBy($findByRecord)->isNotEmpty();

        if ($exists) {
            if (empty($uniqueRoles)) {
                throw new RuntimeException(sprintf(
                    '%s already has a unique attachment to %s. Only one %s is allowed.',
                    $rattachable->getMorphClass(),
                    $targetClass,
                    class_basename($targetClass)
                ));
            }

            $roleLabels = implode(', ', array_map(
                fn ($r) => $r instanceof EnumerableInterface ? $r->getValue() : (string) $r,
                $uniqueRoles
            ));

            throw new RuntimeException(sprintf(
                '%s already has a unique attachment to %s with role "%s". Only one %s with role %s is allowed.',
                $rattachable->getMorphClass(),
                $targetClass,
                $role->getValue(),
                class_basename($targetClass),
                $roleLabels
            ));
        }
    }

    public function validateRoleValue(
        string $rattachableClass,
        string $targetClass,
        string $roleValue
    ): void {
        $this->validateRoleAgainstConstraints($rattachableClass, $targetClass, $roleValue);
    }

    public function resolveRole(
        string $rattachableClass,
        string $targetClass,
        string $roleValue
    ): EnumerableInterface {
        $this->ensureRattachableClassExists($rattachableClass);

        $rattachable = new $rattachableClass;

        $this->ensureRattachableImplementsInterface($rattachable, $rattachableClass);

        $effectiveAllowed = $this->getEffectiveAllowedTargets($rattachable);

        $this->ensureTargetIsAllowed($effectiveAllowed, $targetClass, $rattachableClass);

        foreach ($effectiveAllowed[$targetClass] as $roleEnum) {
            if ($roleEnum instanceof EnumerableInterface && $roleEnum->getValue() === $roleValue) {
                return $roleEnum;
            }
        }

        return UnknownRole::from($roleValue);
    }

    // ================================================================
    // NOUVELLE MÉTHODE
    // ================================================================

    /**
     * Fusionne allowedTargets() et uniqueTargets() par target et par rôle.
     * uniqueTargets() autorise implicitement les rôles qu'elle déclare.
     *
     * @return array<string, array<int, EnumerableInterface>>
     */
    private function getEffectiveAllowedTargets(RattachmentInterface $rattachable): array
    {
        $allowed = $rattachable->allowedTargets();
        $unique = $rattachable->uniqueTargets();

        $result = $allowed;

        foreach ($unique as $targetClass => $roles) {
            if (! isset($result[$targetClass])) {
                $result[$targetClass] = [];
            }

            foreach ($roles as $role) {
                if (! $this->isRoleInArray($role, $result[$targetClass])) {
                    $result[$targetClass][] = $role;
                }
            }
        }

        return $result;
    }

    // ================================================================
    // MÉTHODES MODIFIÉES
    // ================================================================

    private function validateAllowedTargets(
        Model&RattachmentInterface $rattachable,
        string $targetClass,
        EnumerableInterface $role
    ): void {
        $effectiveAllowed = $this->getEffectiveAllowedTargets($rattachable);

        if (! isset($effectiveAllowed[$targetClass])) {
            $allowedTargets = array_keys($effectiveAllowed);
            throw new RuntimeException(sprintf(
                '%s cannot be attached to %s. Allowed targets: %s',
                $rattachable->getMorphClass(),
                $targetClass,
                ! empty($allowedTargets) ? implode(', ', $allowedTargets) : 'none'
            ));
        }

        $allowedRoles = $effectiveAllowed[$targetClass];
        if (! $this->isRoleInArray($role, $allowedRoles)) {
            $allowedValues = array_map(fn ($r) => $r->getValue(), $allowedRoles);
            throw new RuntimeException(sprintf(
                'Role "%s" is not allowed for %s -> %s. Allowed roles: %s',
                $role->getValue(),
                $rattachable->getMorphClass(),
                $targetClass,
                ! empty($allowedValues) ? implode(', ', $allowedValues) : 'none'
            ));
        }
    }

    private function validateRoleAgainstConstraints(
        string $rattachableClass,
        string $targetClass,
        string $roleValue
    ): void {
        $this->ensureRattachableClassExists($rattachableClass);

        $rattachable = new $rattachableClass;

        $this->ensureRattachableImplementsInterface($rattachable, $rattachableClass);

        $effectiveAllowed = $this->getEffectiveAllowedTargets($rattachable);

        $this->ensureTargetIsAllowed($effectiveAllowed, $targetClass, $rattachableClass);

        $isValid = false;
        foreach ($effectiveAllowed[$targetClass] as $roleEnum) {
            if ($roleEnum instanceof EnumerableInterface && $roleEnum->getValue() === $roleValue) {
                $isValid = true;
                break;
            }
        }

        if (! $isValid) {
            throw new RuntimeException(
                sprintf(
                    'Role "%s" is not allowed for %s -> %s. Allowed roles: %s',
                    $roleValue,
                    $rattachableClass,
                    $targetClass,
                    implode(', ', array_map(
                        fn ($role) => $role instanceof EnumerableInterface ? $role->getValue() : (string) $role,
                        $effectiveAllowed[$targetClass]
                    ))
                )
            );
        }
    }

    // ================================================================
    // MÉTHODES INCHANGÉES
    // ================================================================

    private function isRoleInArray(EnumerableInterface $role, array $roles): bool
    {
        foreach ($roles as $r) {
            if ($r instanceof EnumerableInterface && $r->getValue() === $role->getValue()) {
                return true;
            }
        }

        return false;
    }

    private function validateDisallowedTargets(
        Model&RattachmentInterface $rattachable,
        string $targetClass,
        EnumerableInterface $role
    ): void {
        $disallowed = $rattachable->disallowedTargets();

        if (! array_key_exists($targetClass, $disallowed)) {
            return;
        }

        $disallowedRoles = $disallowed[$targetClass];

        if (empty($disallowedRoles)) {
            throw new RuntimeException(sprintf(
                '%s cannot be attached to %s. This target is disallowed.',
                $rattachable->getMorphClass(),
                $targetClass
            ));
        }

        if ($this->isRoleInArray($role, $disallowedRoles)) {
            $disallowedValues = array_map(fn ($r) => $r->getValue(), $disallowedRoles);
            throw new RuntimeException(sprintf(
                'Role "%s" is disallowed for %s -> %s. Disallowed roles: %s',
                $role->getValue(),
                $rattachable->getMorphClass(),
                $targetClass,
                implode(', ', $disallowedValues)
            ));
        }
    }

    private function ensureRattachableClassExists(string $rattachableClass): void
    {
        if (! class_exists($rattachableClass)) {
            throw new RuntimeException(
                sprintf('Rattachable class %s does not exist.', $rattachableClass)
            );
        }
    }

    private function ensureRattachableImplementsInterface(
        Model $rattachable,
        string $rattachableClass
    ): void {
        if (! $rattachable instanceof RattachmentInterface) {
            throw new RuntimeException(
                sprintf(
                    'Rattachable model %s must implement %s.',
                    $rattachableClass,
                    RattachmentInterface::class
                )
            );
        }
    }

    private function ensureTargetIsAllowed(
        array $effectiveAllowed,
        string $targetClass,
        string $rattachableClass
    ): void {
        if (! isset($effectiveAllowed[$targetClass])) {
            throw new RuntimeException(
                sprintf(
                    'Target %s is not allowed for %s.',
                    $targetClass,
                    $rattachableClass
                )
            );
        }
    }

    private function validateSelfAttachment(
        Model&RattachmentInterface $rattachable,
        Model&RattachmentInterface $target
    ): void {
        if ($rattachable->getMorphClass() === $target->getMorphClass()
            && $rattachable->getKey() === $target->getKey()) {
            throw new RuntimeException(sprintf(
                'Cannot attach a model to itself. %s %s cannot be attached to itself.',
                $rattachable->getMorphClass(),
                $rattachable->getKey()
            ));
        }
    }

    private function validateCircularity(
        Model&RattachmentInterface $rattachable,
        Model&RattachmentInterface $target,
        EnumerableInterface $role
    ): void {
        if ($rattachable->getMorphClass() === $target->getMorphClass()) {
            return;
        }

        // ✅ Utiliser la fusion allowedTargets() + uniqueTargets() du target
        $targetAllowed = $this->getEffectiveAllowedTargets($target);

        $rattachableClass = $rattachable->getMorphClass();
        $targetClass = $target->getMorphClass();

        if (! isset($targetAllowed[$rattachableClass])) {
            return;
        }

        $targetAllowedRoles = $targetAllowed[$rattachableClass];

        if (in_array($role, $targetAllowedRoles, true)) {
            throw new RuntimeException(sprintf(
                'Circular relationship detected: %s → %s with role "%s" and %s → %s with the same role. '
                .'To avoid circular references, define the relationship in only one direction.',
                $rattachableClass,
                $targetClass,
                $role->getValue(),
                $targetClass,
                $rattachableClass
            ));
        }
    }

    private function validateUniqueCircularity(
        Model&RattachmentInterface $rattachable,
        Model&RattachmentInterface $target,
        EnumerableInterface $role
    ): void {
        if ($rattachable->getMorphClass() === $target->getMorphClass()) {
            return;
        }

        $targetUniqueTargets = $target->uniqueTargets();

        $rattachableClass = $rattachable->getMorphClass();
        $targetClass = $target->getMorphClass();

        if (! isset($targetUniqueTargets[$rattachableClass])) {
            return;
        }

        $targetUniqueRoles = $targetUniqueTargets[$rattachableClass];

        if (empty($targetUniqueRoles) || in_array($role, $targetUniqueRoles, true)) {
            throw new RuntimeException(sprintf(
                'Circular unique constraint detected: %s → %s with role "%s" and %s → %s with the same role. '
                .'This creates a circular dependency. Define the unique constraint in only one direction.',
                $rattachableClass,
                $targetClass,
                $role->getValue(),
                $targetClass,
                $rattachableClass
            ));
        }
    }
}
