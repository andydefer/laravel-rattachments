<?php

declare(strict_types=1);

namespace AndyDefer\LaravelRattachments\Contracts\Validation;

use AndyDefer\LaravelRattachments\Contracts\RattachmentInterface;
use AndyDefer\Repository\Contracts\EnumerableInterface;
use Illuminate\Database\Eloquent\Model;

/**
 * Contract for validating attachment constraints.
 */
interface ConstraintValidatorInterface
{
    /**
     * Validates that the attachment respects the constraints defined by the rattachable model.
     *
     * @param  Model&RattachmentInterface  $rattachable  The model being attached
     * @param  Model&RattachmentInterface  $target  The target model
     * @param  EnumerableInterface  $role  The role
     *
     * @throws \RuntimeException If constraints are violated
     */
    public function validateConstraints(
        Model&RattachmentInterface $rattachable,
        Model&RattachmentInterface $target,
        EnumerableInterface $role
    ): void;

    /**
     * Validates that the attachment does not violate unique target constraints.
     *
     * @param  Model&RattachmentInterface  $rattachable  The model being attached
     * @param  Model&RattachmentInterface  $target  The target model
     * @param  EnumerableInterface  $role  The role
     *
     * @throws \RuntimeException If unique constraint is violated
     */
    public function validateUniqueConstraints(
        Model&RattachmentInterface $rattachable,
        Model&RattachmentInterface $target,
        EnumerableInterface $role
    ): void;

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
