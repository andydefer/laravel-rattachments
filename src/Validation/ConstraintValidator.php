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

/**
 * Centralized validator for attachment constraints.
 *
 * This class handles all constraint validation logic for attachments,
 * including allowed targets, disallowed targets, unique targets, and role validation.
 */
final class ConstraintValidator implements ConstraintValidatorInterface
{
    public function __construct(
        private readonly RattachmentRepository $repository,
    ) {}

    /**
     * {@inheritDoc}
     */
    public function validateConstraints(Model $rattachable, Model $target, EnumerableInterface $role): void
    {
        if (! $rattachable instanceof RattachmentInterface) {
            throw new RuntimeException(sprintf(
                'Model %s must implement %s to be attachable.',
                get_class($rattachable),
                RattachmentInterface::class
            ));
        }

        if (! $target instanceof RattachmentInterface) {
            throw new RuntimeException(sprintf(
                'Model %s must implement %s to be a target.',
                get_class($target),
                RattachmentInterface::class
            ));
        }

        $targetClass = $target->getMorphClass();

        $this->validateDisallowedTargets($rattachable, $targetClass, $role);
        $this->validateAllowedTargets($rattachable, $targetClass, $role);
    }

    /**
     * {@inheritDoc}
     */
    public function validateUniqueConstraints(Model $rattachable, Model $target, EnumerableInterface $role): void
    {
        if (! $rattachable instanceof RattachmentInterface) {
            return;
        }

        $uniqueTargets = method_exists($rattachable, 'uniqueTargets')
            ? $rattachable->uniqueTargets()
            : [];

        $targetClass = $target->getMorphClass();

        if (! array_key_exists($targetClass, $uniqueTargets)) {
            return;
        }

        $uniqueRoles = $uniqueTargets[$targetClass];

        // Construire le filtre
        $filterData = [
            'rattachable_type' => $rattachable->getMorphClass(),
            'rattachable_id' => $rattachable->getKey(),
            'target_type' => $targetClass,
        ];

        // Si des rôles sont spécifiés, vérifier UNIQUEMENT pour ces rôles
        if (! empty($uniqueRoles)) {
            // Vérifier si ce rôle est dans la liste des rôles uniques
            if (! $this->isRoleInArray($role, $uniqueRoles)) {
                return; // Ce rôle n'est pas concerné par la contrainte unique
            }

            // Filtrer par rôle également
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

    /**
     * {@inheritDoc}
     */
    public function validateRoleValue(
        string $rattachableClass,
        string $targetClass,
        string $roleValue
    ): void {
        $this->validateRoleAgainstConstraints($rattachableClass, $targetClass, $roleValue);
    }

    /**
     * {@inheritDoc}
     */
    public function resolveRole(
        string $rattachableClass,
        string $targetClass,
        string $roleValue
    ): EnumerableInterface {
        $this->ensureRattachableClassExists($rattachableClass);

        $rattachable = new $rattachableClass;

        $this->ensureRattachableImplementsInterface($rattachable, $rattachableClass);

        $allowedTargets = $rattachable->allowedTargets();

        $this->ensureTargetIsAllowed($allowedTargets, $targetClass, $rattachableClass);

        foreach ($allowedTargets[$targetClass] as $roleEnum) {
            if ($roleEnum instanceof EnumerableInterface && $roleEnum->getValue() === $roleValue) {
                return $roleEnum;
            }
        }

        return UnknownRole::from($roleValue);
    }

    /**
     * Checks if a role is in an array of roles.
     *
     * @param  EnumerableInterface  $role  The role to check
     * @param  array<int, EnumerableInterface>  $roles  Array of roles
     * @return bool True if the role is in the array
     */
    private function isRoleInArray(EnumerableInterface $role, array $roles): bool
    {
        foreach ($roles as $r) {
            if ($r instanceof EnumerableInterface && $r->getValue() === $role->getValue()) {
                return true;
            }
        }

        return false;
    }

    /**
     * Validates disallowed targets with role restrictions.
     *
     * @param  Model  $rattachable  The rattachable model
     * @param  string  $targetClass  The target class
     * @param  EnumerableInterface  $role  The role
     *
     * @throws RuntimeException If target or role is disallowed
     */
    private function validateDisallowedTargets(
        Model $rattachable,
        string $targetClass,
        EnumerableInterface $role
    ): void {
        $disallowed = method_exists($rattachable, 'disallowedTargets')
            ? $rattachable->disallowedTargets()
            : [];

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

    /**
     * Validates allowed targets and roles.
     *
     * @param  Model  $rattachable  The rattachable model
     * @param  string  $targetClass  The target class
     * @param  EnumerableInterface  $role  The role
     *
     * @throws RuntimeException If target or role is not allowed
     */
    private function validateAllowedTargets(
        Model $rattachable,
        string $targetClass,
        EnumerableInterface $role
    ): void {
        $allowed = $rattachable->allowedTargets();

        if (! isset($allowed[$targetClass])) {
            $allowedTargets = array_keys($allowed);
            throw new RuntimeException(sprintf(
                '%s cannot be attached to %s. Allowed targets: %s',
                $rattachable->getMorphClass(),
                $targetClass,
                ! empty($allowedTargets) ? implode(', ', $allowedTargets) : 'none'
            ));
        }

        $allowedRoles = $allowed[$targetClass];
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

    /**
     * Validates that a role is allowed by the rattachable constraints.
     *
     * @param  string  $rattachableClass  The rattachable class name
     * @param  string  $targetClass  The target class name
     * @param  string  $roleValue  The role value to validate
     *
     * @throws RuntimeException If the role is not allowed
     */
    private function validateRoleAgainstConstraints(
        string $rattachableClass,
        string $targetClass,
        string $roleValue
    ): void {
        $this->ensureRattachableClassExists($rattachableClass);

        $rattachable = new $rattachableClass;

        $this->ensureRattachableImplementsInterface($rattachable, $rattachableClass);

        $allowedTargets = $rattachable->allowedTargets();

        $this->ensureTargetIsAllowed($allowedTargets, $targetClass, $rattachableClass);

        $isValid = false;
        foreach ($allowedTargets[$targetClass] as $roleEnum) {
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
                        $allowedTargets[$targetClass]
                    ))
                )
            );
        }
    }

    /**
     * Ensures the rattachable class exists.
     *
     * @param  string  $rattachableClass  The class name to check
     *
     * @throws RuntimeException If the class does not exist
     */
    private function ensureRattachableClassExists(string $rattachableClass): void
    {
        if (! class_exists($rattachableClass)) {
            throw new RuntimeException(
                sprintf('Rattachable class %s does not exist.', $rattachableClass)
            );
        }
    }

    /**
     * Ensures the rattachable model implements the required interface.
     *
     * @param  Model  $rattachable  The rattachable instance
     * @param  string  $rattachableClass  The class name
     *
     * @throws RuntimeException If the interface is not implemented
     */
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

    /**
     * Ensures the target is allowed for the rattachable.
     *
     * @param  array  $allowedTargets  The allowed targets configuration
     * @param  string  $targetClass  The target class
     * @param  string  $rattachableClass  The rattachable class
     *
     * @throws RuntimeException If the target is not allowed
     */
    private function ensureTargetIsAllowed(
        array $allowedTargets,
        string $targetClass,
        string $rattachableClass
    ): void {
        if (! isset($allowedTargets[$targetClass])) {
            throw new RuntimeException(
                sprintf(
                    'Target %s is not allowed for %s.',
                    $targetClass,
                    $rattachableClass
                )
            );
        }
    }
}
