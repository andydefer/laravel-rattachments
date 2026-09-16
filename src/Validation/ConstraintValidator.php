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

        $this->validateDisallowedTargets($rattachable, $target, $role);
        $this->validateAllowedTargets($rattachable, $target, $role);
        $this->validateCircularity($rattachable, $target, $role);
    }

    public function validateUniqueConstraints(
        Model&RattachmentInterface $rattachable,
        Model&RattachmentInterface $target,
        EnumerableInterface $role
    ): void {
        $this->validateUniqueCircularity($rattachable, $target, $role);

        $effectiveAllowed = $this->getEffectiveAllowedTargets($rattachable);

        $declaredKey = $this->findMatchingDeclaredKey($effectiveAllowed, $target);

        if ($declaredKey === null) {
            return;
        }

        $uniqueTargets = $rattachable->uniqueTargets();

        if (! $this->hasMatchingDeclaredKey($uniqueTargets, $target)) {
            return;
        }

        $uniqueRoles = $this->findMatchingUniqueRoles($uniqueTargets, $target);

        $filterData = [
            'rattachable_type' => $rattachable->getMorphClass(),
            'rattachable_id' => $rattachable->getKey(),
            'target_type' => $target->getMorphClass(),
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
                    $target->getMorphClass(),
                    class_basename($target->getMorphClass())
                ));
            }

            $roleLabels = implode(', ', array_map(
                fn ($r) => $r instanceof EnumerableInterface ? $r->getValue() : (string) $r,
                $uniqueRoles
            ));

            throw new RuntimeException(sprintf(
                '%s already has a unique attachment to %s with role "%s". Only one %s with role %s is allowed.',
                $rattachable->getMorphClass(),
                $target->getMorphClass(),
                $role->getValue(),
                class_basename($target->getMorphClass()),
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

        $roles = $this->findMatchingRolesForTargetClass($effectiveAllowed, $targetClass);

        if ($roles === null) {
            $allowedTargets = array_keys($effectiveAllowed);
            throw new RuntimeException(sprintf(
                'Target %s is not allowed for %s. Allowed targets: %s',
                $targetClass,
                $rattachableClass,
                ! empty($allowedTargets) ? implode(', ', $allowedTargets) : 'none'
            ));
        }

        foreach ($roles as $roleEnum) {
            if ($roleEnum instanceof EnumerableInterface && $roleEnum->getValue() === $roleValue) {
                return $roleEnum;
            }
        }

        return UnknownRole::from($roleValue);
    }

    // ================================================================
    // EFFECTIVE ALLOWED TARGETS (merge allowed + unique)
    // ================================================================

    /**
     * Merge allowedTargets() and uniqueTargets() by target and by role.
     * uniqueTargets() implicitly authorizes the roles it declares.
     *
     * @return array<string, array<int, EnumerableInterface>>
     */
    private function getEffectiveAllowedTargets(RattachmentInterface $rattachable): array
    {
        $allowed = $rattachable->allowedTargets();
        $unique = $rattachable->uniqueTargets();

        $result = $allowed;

        foreach ($unique as $declaredKey => $roles) {
            if (! isset($result[$declaredKey])) {
                $result[$declaredKey] = [];
            }

            foreach ($roles as $role) {
                if (! $this->isRoleInArray($role, $result[$declaredKey])) {
                    $result[$declaredKey][] = $role;
                }
            }
        }

        return $result;
    }

    /**
     * Find the declared key (class or interface) matching the given target model.
     *
     * @param  array<string, array<int, EnumerableInterface>>  $declared
     */
    private function findMatchingDeclaredKey(array $declared, Model $target): ?string
    {
        $targetClass = $target->getMorphClass();

        foreach (array_keys($declared) as $declaredKey) {
            if ($targetClass === $declaredKey || is_a($targetClass, $declaredKey, true)) {
                return $declaredKey;
            }
        }

        return null;
    }

    /**
     * Find all matching declared keys for the given target class.
     *
     * @param  array<string, array<int, EnumerableInterface>>  $declared
     * @return array<int, string>|null
     */
    private function findMatchingDeclaredKeysForClass(array $declared, string $targetClass): ?array
    {
        $matches = [];

        foreach (array_keys($declared) as $declaredKey) {
            if ($targetClass === $declaredKey || is_a($targetClass, $declaredKey, true)) {
                $matches[] = $declaredKey;
            }
        }

        return $matches === [] ? null : $matches;
    }

    /**
     * Return the roles declared for the given target, resolving through inheritance.
     *
     * @param  array<string, array<int, EnumerableInterface>>  $declared
     * @return array<int, EnumerableInterface>|null
     */
    private function findMatchingRolesForTargetClass(array $declared, string $targetClass): ?array
    {
        $matchingKeys = $this->findMatchingDeclaredKeysForClass($declared, $targetClass);

        if ($matchingKeys === null) {
            return null;
        }

        $roles = [];

        foreach ($matchingKeys as $key) {
            foreach ($declared[$key] as $role) {
                if (! $this->isRoleInArray($role, $roles)) {
                    $roles[] = $role;
                }
            }
        }

        return $roles;
    }

    /**
     * Indicate whether the given target matches at least one declared key.
     *
     * @param  array<string, array<int, EnumerableInterface>>  $declared
     */
    private function hasMatchingDeclaredKey(array $declared, Model $target): bool
    {
        return $this->findMatchingDeclaredKey($declared, $target) !== null;
    }

    /**
     * Return the merged roles declared for the given target in uniqueTargets().
     *
     * @param  array<string, array<int, EnumerableInterface>>  $uniqueTargets
     * @return array<int, EnumerableInterface>
     */
    private function findMatchingUniqueRoles(array $uniqueTargets, Model $target): array
    {
        $roles = $this->findMatchingRolesForTargetClass($uniqueTargets, $target->getMorphClass());

        return $roles ?? [];
    }

    // ================================================================
    // VALIDATION
    // ================================================================

    private function validateAllowedTargets(
        Model&RattachmentInterface $rattachable,
        Model&RattachmentInterface $target,
        EnumerableInterface $role
    ): void {
        $effectiveAllowed = $this->getEffectiveAllowedTargets($rattachable);

        $roles = $this->findMatchingRolesForTargetClass($effectiveAllowed, $target->getMorphClass());

        if ($roles === null) {
            $allowedTargets = array_keys($effectiveAllowed);
            throw new RuntimeException(sprintf(
                '%s cannot be attached to %s. Allowed targets: %s',
                $rattachable->getMorphClass(),
                $target->getMorphClass(),
                ! empty($allowedTargets) ? implode(', ', $allowedTargets) : 'none'
            ));
        }

        if (! $this->isRoleInArray($role, $roles)) {
            $allowedValues = array_map(fn ($r) => $r->getValue(), $roles);
            throw new RuntimeException(sprintf(
                'Role "%s" is not allowed for %s -> %s. Allowed roles: %s',
                $role->getValue(),
                $rattachable->getMorphClass(),
                $target->getMorphClass(),
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

        $roles = $this->findMatchingRolesForTargetClass($effectiveAllowed, $targetClass);

        if ($roles === null) {
            throw new RuntimeException(
                sprintf(
                    'Target %s is not allowed for %s.',
                    $targetClass,
                    $rattachableClass
                )
            );
        }

        $isValid = false;
        foreach ($roles as $roleEnum) {
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
                        $roles
                    ))
                )
            );
        }
    }

    private function validateDisallowedTargets(
        Model&RattachmentInterface $rattachable,
        Model&RattachmentInterface $target,
        EnumerableInterface $role
    ): void {
        $disallowed = $rattachable->disallowedTargets();

        $roles = $this->findMatchingRolesForTargetClass($disallowed, $target->getMorphClass());

        if ($roles === null) {
            return;
        }

        if (empty($roles)) {
            throw new RuntimeException(sprintf(
                '%s cannot be attached to %s. This target is disallowed.',
                $rattachable->getMorphClass(),
                $target->getMorphClass()
            ));
        }

        if ($this->isRoleInArray($role, $roles)) {
            $disallowedValues = array_map(fn ($r) => $r->getValue(), $roles);
            throw new RuntimeException(sprintf(
                'Role "%s" is disallowed for %s -> %s. Disallowed roles: %s',
                $role->getValue(),
                $rattachable->getMorphClass(),
                $target->getMorphClass(),
                implode(', ', $disallowedValues)
            ));
        }
    }

    // ================================================================
    // HELPERS
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

        $targetAllowed = $this->getEffectiveAllowedTargets($target);

        $roles = $this->findMatchingRolesForTargetClass($targetAllowed, $rattachable->getMorphClass());

        if ($roles === null) {
            return;
        }

        if ($this->isRoleInArray($role, $roles)) {
            throw new RuntimeException(sprintf(
                'Circular relationship detected: %s → %s with role "%s" and %s → %s with the same role. '
                .'To avoid circular references, define the relationship in only one direction.',
                $rattachable->getMorphClass(),
                $target->getMorphClass(),
                $role->getValue(),
                $target->getMorphClass(),
                $rattachable->getMorphClass()
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

        $targetUnique = $target->uniqueTargets();

        $roles = $this->findMatchingRolesForTargetClass($targetUnique, $rattachable->getMorphClass());

        if ($roles === null) {
            return;
        }

        if (empty($roles) || $this->isRoleInArray($role, $roles)) {
            throw new RuntimeException(sprintf(
                'Circular unique constraint detected: %s → %s with role "%s" and %s → %s with the same role. '
                .'This creates a circular dependency. Define the unique constraint in only one direction.',
                $rattachable->getMorphClass(),
                $target->getMorphClass(),
                $role->getValue(),
                $target->getMorphClass(),
                $rattachable->getMorphClass()
            ));
        }
    }
}
