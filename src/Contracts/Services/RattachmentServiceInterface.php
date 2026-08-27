<?php

declare(strict_types=1);

namespace AndyDefer\LaravelRattachments\Contracts\Services;

use AndyDefer\Repository\Contracts\EnumerableInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use RuntimeException;

/**
 * Interface RattachmentServiceInterface
 *
 * Service interface for managing polymorphic attachments (rattachments).
 * Provides methods for attaching, detaching, and querying relationships
 * between any two models with roles and metadata.
 */
interface RattachmentServiceInterface
{
    /**
     * Attach a model to another model with a role and optional metadata.
     *
     * @param  Model  $rattachable  The model being attached (e.g., User, Doctor)
     * @param  Model  $target  The target model to attach to (e.g., Hospital, Pharmacy)
     * @param  EnumerableInterface  $role  The role of the attachment (e.g., 'doctor', 'pharmacist')
     * @param  array  $metadata  Additional metadata for the attachment
     * @return Model The created rattachment model
     *
     * @throws RuntimeException If the attachment already exists
     */
    public function attach(Model $rattachable, Model $target, EnumerableInterface $role, array $metadata = []): Model;

    /**
     * Attach multiple models to a target model with the same role and metadata.
     *
     * @param  Collection<int, Model>  $rattachables  Collection of models to attach
     * @param  Model  $target  The target model
     * @param  EnumerableInterface  $role  The role for all attachments
     * @param  array  $metadata  Additional metadata for all attachments
     * @return Collection<int, Model> Collection of created rattachment models
     *
     * @throws RuntimeException If any attachment already exists
     */
    public function attachMultiple(Collection $rattachables, Model $target, EnumerableInterface $role, array $metadata = []): Collection;

    /**
     * Attach a model to multiple targets with the same role and metadata.
     *
     * @param  Model  $rattachable  The model to attach
     * @param  Collection<int, Model>  $targets  Collection of target models
     * @param  EnumerableInterface  $role  The role for all attachments
     * @param  array  $metadata  Additional metadata for all attachments
     * @return Collection<int, Model> Collection of created rattachment models
     *
     * @throws RuntimeException If any attachment already exists
     */
    public function attachToMultiple(Model $rattachable, Collection $targets, EnumerableInterface $role, array $metadata = []): Collection;

    /**
     * Detach a model from another model.
     *
     * @param  Model  $rattachable  The model being detached
     * @param  Model  $target  The target model to detach from
     *
     * @throws RuntimeException If the attachment does not exist
     */
    public function detach(Model $rattachable, Model $target): void;

    /**
     * Detach multiple models from a target model.
     *
     * @param  Collection<int, Model>  $rattachables  Collection of models to detach
     * @param  Model  $target  The target model
     */
    public function detachMultiple(Collection $rattachables, Model $target): void;

    /**
     * Detach a model from multiple targets.
     *
     * @param  Model  $rattachable  The model to detach
     * @param  Collection<int, Model>  $targets  Collection of target models
     */
    public function detachFromMultiple(Model $rattachable, Collection $targets): void;

    /**
     * Detach a model from all its attachments (both as rattachable and target).
     *
     * @param  Model  $model  The model to detach from all attachments
     */
    public function detachAll(Model $model): void;

    /**
     * Check if a model is attached to another model.
     *
     * @param  Model  $rattachable  The model being checked
     * @param  Model  $target  The target model
     * @return bool True if attached, false otherwise
     */
    public function isAttached(Model $rattachable, Model $target): bool;

    /**
     * Check if any model is attached to a target with a specific role.
     *
     * @param  Model  $target  The target model
     * @param  EnumerableInterface  $role  The role to check
     * @return bool True if any attachment with the role exists
     */
    public function hasRoleAttached(Model $target, EnumerableInterface $role): bool;

    /**
     * Get all models attached to a target model.
     *
     * @param  Model  $target  The target model (e.g., Hospital)
     * @return Collection<int, Model> Collection of attached models
     */
    public function getRattachables(Model $target): Collection;

    /**
     * Get all models attached to a target model with pagination.
     *
     * @param  Model  $target  The target model
     * @param  int  $perPage  Items per page
     * @param  int  $page  Page number
     * @return LengthAwarePaginator Paginated results
     */
    public function getRattachablesPaginated(Model $target, int $perPage = 15, int $page = 1): LengthAwarePaginator;

    /**
     * Get all targets a model is attached to.
     *
     * @param  Model  $rattachable  The attached model (e.g., User)
     * @return Collection<int, Model> Collection of target models
     */
    public function getTargets(Model $rattachable): Collection;

    /**
     * Get all targets a model is attached to with pagination.
     *
     * @param  Model  $rattachable  The attached model
     * @param  int  $perPage  Items per page
     * @param  int  $page  Page number
     * @return LengthAwarePaginator Paginated results
     */
    public function getTargetsPaginated(Model $rattachable, int $perPage = 15, int $page = 1): LengthAwarePaginator;

    /**
     * Get all models attached to a target model with a specific role.
     *
     * @param  Model  $target  The target model
     * @param  EnumerableInterface  $role  The role to filter by
     * @return Collection<int, Model> Collection of attached models with the given role
     */
    public function getRattachablesByRole(Model $target, EnumerableInterface $role): Collection;

    /**
     * Get all models attached to a target model with a specific role and pagination.
     *
     * @param  Model  $target  The target model
     * @param  EnumerableInterface  $role  The role to filter by
     * @param  int  $perPage  Items per page
     * @param  int  $page  Page number
     * @return LengthAwarePaginator Paginated results
     */
    public function getRattachablesByRolePaginated(Model $target, EnumerableInterface $role, int $perPage = 15, int $page = 1): LengthAwarePaginator;

    /**
     * Get all targets a model is attached to with a specific role.
     *
     * @param  Model  $rattachable  The attached model
     * @param  EnumerableInterface  $role  The role to filter by
     * @return Collection<int, Model> Collection of target models with the given role
     */
    public function getTargetsByRole(Model $rattachable, EnumerableInterface $role): Collection;

    /**
     * Get all targets a model is attached to with a specific role and pagination.
     *
     * @param  Model  $rattachable  The attached model
     * @param  EnumerableInterface  $role  The role to filter by
     * @param  int  $perPage  Items per page
     * @param  int  $page  Page number
     * @return LengthAwarePaginator Paginated results
     */
    public function getTargetsByRolePaginated(Model $rattachable, EnumerableInterface $role, int $perPage = 15, int $page = 1): LengthAwarePaginator;

    /**
     * Count all models attached to a target model.
     *
     * @param  Model  $target  The target model
     * @return int Total number of attachments
     */
    public function countRattachables(Model $target): int;

    /**
     * Count all targets a model is attached to.
     *
     * @param  Model  $rattachable  The attached model
     * @return int Total number of targets
     */
    public function countTargets(Model $rattachable): int;

    /**
     * Count models attached to a target with a specific role.
     *
     * @param  Model  $target  The target model
     * @param  EnumerableInterface  $role  The role to filter by
     * @return int Number of attachments with the given role
     */
    public function countRattachablesByRole(Model $target, EnumerableInterface $role): int;

    /**
     * Count targets a model is attached to with a specific role.
     *
     * @param  Model  $rattachable  The attached model
     * @param  EnumerableInterface  $role  The role to filter by
     * @return int Number of targets with the given role
     */
    public function countTargetsByRole(Model $rattachable, EnumerableInterface $role): int;

    /**
     * Get all distinct roles for a target model.
     *
     * @param  Model  $target  The target model
     * @return Collection<int, string|EnumerableInterface> Collection of distinct role values
     */
    public function getDistinctRolesForTarget(Model $target): Collection;

    /**
     * Get all distinct roles for a rattachable model.
     *
     * @param  Model  $rattachable  The attached model
     * @return Collection<int, string|EnumerableInterface> Collection of distinct role values
     */
    public function getDistinctRolesForRattachable(Model $rattachable): Collection;

    /**
     * Update the role of an existing attachment.
     *
     * @param  Model  $rattachable  The attached model
     * @param  Model  $target  The target model
     * @param  EnumerableInterface  $role  The new role
     *
     * @throws RuntimeException If the attachment does not exist
     */
    public function updateRole(Model $rattachable, Model $target, EnumerableInterface $role): void;

    /**
     * Update the role of multiple attachments.
     *
     * @param  Collection<int, Model>  $rattachables  Collection of attached models
     * @param  Model  $target  The target model
     * @param  EnumerableInterface  $role  The new role
     */
    public function updateRoleForMultiple(Collection $rattachables, Model $target, EnumerableInterface $role): void;

    /**
     * Update the metadata of an existing attachment.
     *
     * @param  Model  $rattachable  The attached model
     * @param  Model  $target  The target model
     * @param  array  $metadata  The new metadata
     *
     * @throws RuntimeException If the attachment does not exist
     */
    public function updateMetadata(Model $rattachable, Model $target, array $metadata): void;

    /**
     * Merge metadata into an existing attachment.
     *
     * @param  Model  $rattachable  The attached model
     * @param  Model  $target  The target model
     * @param  array  $metadata  The metadata to merge
     *
     * @throws RuntimeException If the attachment does not exist
     */
    public function mergeMetadata(Model $rattachable, Model $target, array $metadata): void;

    /**
     * Get a specific attachment between two models.
     *
     * @param  Model  $rattachable  The attached model
     * @param  Model  $target  The target model
     * @return Model|null The attachment model or null if not found
     */
    public function getAttachment(Model $rattachable, Model $target): ?Model;

    /**
     * Check if an attachment exists between two specific models.
     *
     * @param  Model  $rattachable  The rattachable model
     * @param  Model  $target  The target model
     * @return bool True if attachment exists
     */
    public function hasAttachmentsBetween(Model $rattachable, Model $target): bool;

    /**
     * Check if attachments exist between two model types.
     *
     * @param  string  $rattachableType  The morph class of the rattachable model
     * @param  string  $targetType  The morph class of the target model
     * @return bool True if any attachment exists
     */
    public function hasAttachmentsBetweenTypes(string $rattachableType, string $targetType): bool;

    /**
     * Get all attachments between two model types.
     *
     * @param  string  $rattachableType  The morph class of the rattachable model
     * @param  string  $targetType  The morph class of the target model
     * @return Collection<int, Model> Collection of attachment models
     */
    public function getAttachmentsBetweenTypes(string $rattachableType, string $targetType): Collection;

    /**
     * Delete all attachments between two model types.
     *
     * @param  string  $rattachableType  The morph class of the rattachable model
     * @param  string  $targetType  The morph class of the target model
     * @return int Number of deleted attachments
     */
    public function deleteAllAttachmentsBetweenTypes(string $rattachableType, string $targetType): int;

    /**
     * Sync attachments for a model with a given set of targets and roles.
     *
     * @param  Model  $rattachable  The attached model
     * @param  array  $targets  Array of targets with roles and metadata
     *                          Example: [['target' => $hospital, 'role' => 'doctor', 'metadata' => []]]
     * @return Collection<int, Model> Collection of created/updated attachment models
     */
    public function syncAttachments(Model $rattachable, array $targets): Collection;
}
