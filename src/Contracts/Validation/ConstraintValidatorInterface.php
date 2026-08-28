<?php

declare(strict_types=1);

namespace AndyDefer\LaravelRattachments\Contracts\Validation;

use AndyDefer\Repository\Contracts\EnumerableInterface;
use Illuminate\Database\Eloquent\Model;

/**
 * Contract for validating attachment constraints.
 *
 * This interface defines the contract for validating all attachment constraints,
 * including allowed targets, disallowed targets, unique targets, and role validation.
 */
interface ConstraintValidatorInterface
{
    /**
     * Validates that the attachment respects the constraints defined by the rattachable model.
     *
     * Checks disallowed targets (with role restrictions), allowed targets, and allowed roles.
     *
     * @param  Model  $rattachable  The model being attached
     * @param  Model  $target  The target model
     * @param  EnumerableInterface  $role  The role
     *
     * @throws \RuntimeException If constraints are violated
     */
    public function validateConstraints(Model $rattachable, Model $target, EnumerableInterface $role): void;

    /**
     * Validates that the attachment does not violate unique target constraints.
     *
     * A model can only have ONE attachment per unique target type.
     *
     * @param  Model  $rattachable  The model being attached
     * @param  Model  $target  The target model
     *
     * @throws \RuntimeException If unique constraint is violated
     */
    public function validateUniqueConstraints(Model $rattachable, Model $target): void;

    /**
     * Validates that a role value is allowed for a specific context.
     *
     * @param  string  $rattachableClass  The rattachable class name
     * @param  string  $targetClass  The target class name
     * @param  string  $roleValue  The role value to validate
     *
     * @throws \RuntimeException If the role is not allowed
     */
    public function validateRoleValue(
        string $rattachableClass,
        string $targetClass,
        string $roleValue
    ): void;

    /**
     * Resolves a role value to its enum instance.
     *
     * @param  string  $rattachableClass  The rattachable class name
     * @param  string  $targetClass  The target class name
     * @param  string  $roleValue  The role value to resolve
     * @return EnumerableInterface The resolved enum or UnknownRole
     */
    public function resolveRole(
        string $rattachableClass,
        string $targetClass,
        string $roleValue
    ): EnumerableInterface;
}
