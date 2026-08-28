<?php

declare(strict_types=1);

namespace AndyDefer\LaravelRattachments\Contracts\Hooks;

use AndyDefer\DomainStructures\Utils\StrictDataObject;
use AndyDefer\LaravelRattachments\Enums\HookPosition;
use AndyDefer\Repository\Contracts\EnumerableInterface;
use Illuminate\Database\Eloquent\Model;

/**
 * Interface for attachment lifecycle hooks.
 *
 * Implement this interface to hook into the attachment lifecycle events.
 * Each method receives the attachment context and can perform side effects
 * before/after operations. All methods return void.
 */
interface AttachmentHookInterface
{
    /**
     * Hook called before an attachment is created.
     *
     * @param  Model  $other  The other model in the attachment (target if this model is rattachable, rattachable if this model is target)
     * @param  EnumerableInterface  $role  The role for the attachment
     * @param  array<string, mixed>  $metadata  The metadata for the attachment
     * @param  HookPosition  $position  The position of this model (rattachable or target)
     */
    public function beforeAttach(
        Model $other,
        EnumerableInterface $role,
        array $metadata,
        HookPosition $position
    ): void;

    /**
     * Hook called after an attachment is created.
     *
     * @param  Model  $other  The other model in the attachment
     * @param  EnumerableInterface  $role  The role for the attachment
     * @param  Model  $attachment  The created attachment model
     * @param  HookPosition  $position  The position of this model (rattachable or target)
     */
    public function afterAttach(
        Model $other,
        EnumerableInterface $role,
        Model $attachment,
        HookPosition $position
    ): void;

    /**
     * Hook called before an attachment is detached.
     *
     * @param  Model  $other  The other model in the attachment
     * @param  Model  $attachment  The attachment model being deleted
     * @param  HookPosition  $position  The position of this model (rattachable or target)
     */
    public function beforeDetach(
        Model $other,
        Model $attachment,
        HookPosition $position
    ): void;

    /**
     * Hook called after an attachment is detached.
     *
     * @param  Model  $other  The other model in the attachment
     * @param  Model  $attachment  The attachment model that was deleted
     * @param  HookPosition  $position  The position of this model (rattachable or target)
     */
    public function afterDetach(
        Model $other,
        Model $attachment,
        HookPosition $position
    ): void;

    /**
     * Hook called before an attachment role is updated.
     *
     * @param  Model  $other  The other model in the attachment
     * @param  Model  $attachment  The attachment model being updated
     * @param  EnumerableInterface  $oldRole  The old role
     * @param  EnumerableInterface  $newRole  The new role
     * @param  HookPosition  $position  The position of this model (rattachable or target)
     */
    public function beforeUpdateRole(
        Model $other,
        Model $attachment,
        EnumerableInterface $oldRole,
        EnumerableInterface $newRole,
        HookPosition $position
    ): void;

    /**
     * Hook called after an attachment role is updated.
     *
     * @param  Model  $other  The other model in the attachment
     * @param  Model  $attachment  The updated attachment model
     * @param  EnumerableInterface  $oldRole  The old role
     * @param  EnumerableInterface  $newRole  The new role
     * @param  HookPosition  $position  The position of this model (rattachable or target)
     */
    public function afterUpdateRole(
        Model $other,
        Model $attachment,
        EnumerableInterface $oldRole,
        EnumerableInterface $newRole,
        HookPosition $position
    ): void;

    /**
     * Hook called before metadata is updated.
     *
     * @param  Model  $other  The other model in the attachment
     * @param  Model  $attachment  The attachment model being updated
     * @param  StrictDataObject  $oldMetadata  The old metadata
     * @param  StrictDataObject  $newMetadata  The new metadata
     * @param  HookPosition  $position  The position of this model (rattachable or target)
     */
    public function beforeUpdateMetadata(
        Model $other,
        Model $attachment,
        StrictDataObject $oldMetadata,
        StrictDataObject $newMetadata,
        HookPosition $position
    ): void;

    /**
     * Hook called after metadata is updated.
     *
     * @param  Model  $other  The other model in the attachment
     * @param  Model  $attachment  The updated attachment model
     * @param  StrictDataObject  $oldMetadata  The old metadata
     * @param  StrictDataObject  $newMetadata  The new metadata
     * @param  HookPosition  $position  The position of this model (rattachable or target)
     */
    public function afterUpdateMetadata(
        Model $other,
        Model $attachment,
        StrictDataObject $oldMetadata,
        StrictDataObject $newMetadata,
        HookPosition $position
    ): void;

    /**
     * Hook called before detaching all attachments.
     */
    public function beforeDetachAll(): void;

    /**
     * Hook called after detaching all attachments.
     */
    public function afterDetachAll(): void;
}
