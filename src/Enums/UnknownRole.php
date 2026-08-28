<?php

declare(strict_types=1);

namespace AndyDefer\LaravelRattachments\Enums;

use AndyDefer\Repository\Contracts\EnumerableInterface;

/**
 * Represents a role that is no longer valid or recognized.
 *
 * This class acts as a fallback when a stored role value no longer exists
 * in the current constraints. It allows the system to remain backward-compatible
 * with old data while signaling that the role should be reviewed.
 *
 * @example
 * // When a role is removed from allowedTargets()
 * $role = UnknownRole::from('doctor');
 * $role->getValue(); // 'doctor'
 * $role->isUnknown(); // true
 */
final class UnknownRole implements EnumerableInterface
{
    private function __construct(public readonly string|int $value) {}

    /**
     * Creates a new UnknownRole instance.
     *
     * @param  string|int  $value  The raw role value
     */
    public static function from(string|int $value): self
    {
        return new self($value);
    }

    /**
     * Returns the raw value of the unknown role.
     */
    public function getValue(): string|int
    {
        return $this->value;
    }

    /**
     * Returns whether this role is unknown (always true).
     */
    public function isUnknown(): bool
    {
        return true;
    }

    /**
     * Returns all available cases (empty for UnknownRole).
     *
     * @return array<static>
     */
    public static function cases(): array
    {
        return [];
    }

    /**
     * Try to get a case by its value (always returns null for UnknownRole).
     */
    public static function tryFrom(string|int $value): ?static
    {
        return null;
    }
}
