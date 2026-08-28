<?php

declare(strict_types=1);

namespace AndyDefer\LaravelRattachments\Contracts;

use AndyDefer\LaravelRattachments\Contracts\Hooks\AttachmentHookInterface;
use AndyDefer\Repository\Contracts\EnumerableInterface;
use Illuminate\Database\Eloquent\Model;

/**
 * Interface for models that define attachment constraints.
 *
 * Extends AttachmentHookInterface to allow models to hook into the
 * attachment lifecycle events.
 */
interface RattachmentInterface extends AttachmentHookInterface
{
    /**
     * Define allowed targets for this model.
     *
     * @return array<string, array<int, EnumerableInterface>>
     *
     * @example
     * return [
     *     Hospital::class => [Role::DOCTOR, Role::STAFF],
     *     Pharmacy::class => [Role::PHARMACIST],
     * ];
     */
    public function allowedTargets(): array;

    /**
     * Define unique targets with optional role restrictions.
     *
     * - If a target class is listed with an empty array: ONE attachment per type (any role)
     * - If a target class is listed with specific roles: ONE attachment per type AND role
     *
     * @return array<string, array<int, EnumerableInterface>> Array of FQCNs with optional role restrictions
     *
     * @example
     * // Only one DoctorProfile per User (any role)
     * return [
     *     DoctorProfile::class => [],
     * ];
     *
     * // A doctor can have many Hospital::DOCTOR, but only one Hospital::CHIEF
     * return [
     *     Hospital::class => [Role::CHIEF],
     * ];
     *
     * // A doctor can have many Specialty::SPECIALIST, but only one Specialty::PRIMARY
     * return [
     *     Specialty::class => [Role::PRIMARY],
     * ];
     *
     * // A User can have many Friend::FRIEND, but only one Friend::BEST_FRIEND
     * return [
     *     User::class => [FriendRole::BEST_FRIEND],
     * ];
     */
    public function uniqueTargets(): array;

    /**
     * Define disallowed targets with optional role restrictions.
     *
     * - If a target class is listed with an empty array: ALL attachments to this target are blocked.
     * - If a target class is listed with specific roles: only attachments with those roles are blocked.
     * - This overrides allowedTargets().
     *
     * @return array<string, array<int, EnumerableInterface>> Array of FQCNs with optional role restrictions
     *
     * @example
     * // Block all attachments to Specialty
     * return [
     *     Specialty::class => [],
     * ];
     *
     * // Block only specific roles
     * return [
     *     User::class => [Role::STAFF],        // STAFF blocked, other roles allowed
     *     Specialty::class => [Role::CONSULTANT], // CONSULTANT blocked
     * ];
     */
    public function disallowedTargets(): array;
}
