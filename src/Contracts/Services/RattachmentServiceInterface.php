<?php

declare(strict_types=1);

namespace AndyDefer\LaravelRattachments\Contracts\Services;

use AndyDefer\DomainStructures\Utils\StrictDataObject;
use AndyDefer\LaravelRattachments\Contracts\RattachmentInterface;
use AndyDefer\Repository\Contracts\EnumerableInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

/**
 * Service interface for managing polymorphic attachments between Eloquent models.
 *
 * Provides a complete API for attaching, detaching, and querying relationships
 * between any two models with roles and metadata.
 */
interface RattachmentServiceInterface
{
    /**
     * Attaches a model to another model with a role and optional metadata.
     *
     * @param  Model&RattachmentInterface  $rattachable  The model to attach (e.g., User, Doctor)
     * @param  Model&RattachmentInterface  $target  The target model to attach to (e.g., Hospital, Pharmacy)
     * @param  EnumerableInterface  $role  The role of the attachment (required)
     * @param  array<string, mixed>  $metadata  Additional metadata for the attachment
     * @return Model The created rattachment model
     *
     * @throws \RuntimeException If the attachment already exists or constraints are violated
     */
    public function attach(Model&RattachmentInterface $rattachable, Model&RattachmentInterface $target, EnumerableInterface $role, array $metadata = []): Model;

    /**
     * Attaches multiple models to a single target with a role and optional metadata.
     *
     * @param  Collection<int, Model&RattachmentInterface>  $rattachables  Collection of models to attach
     * @param  Model&RattachmentInterface  $target  The target model
     * @param  EnumerableInterface  $role  The role for all attachments (required)
     * @param  array<string, mixed>  $metadata  Additional metadata for all attachments
     * @return Collection<int, Model> Collection of created rattachment models
     *
     * @throws \RuntimeException If any attachment already exists or constraints are violated
     */
    public function attachMultiple(Collection $rattachables, Model&RattachmentInterface $target, EnumerableInterface $role, array $metadata = []): Collection;

    /**
     * Attaches a single model to multiple targets with a role and optional metadata.
     *
     * @param  Model&RattachmentInterface  $rattachable  The model to attach
     * @param  Collection<int, Model&RattachmentInterface>  $targets  Collection of target models
     * @param  EnumerableInterface  $role  The role for all attachments (required)
     * @param  array<string, mixed>  $metadata  Additional metadata for all attachments
     * @return Collection<int, Model> Collection of created rattachment models
     *
     * @throws \RuntimeException If any attachment already exists or constraints are violated
     */
    public function attachToMultiple(Model&RattachmentInterface $rattachable, Collection $targets, EnumerableInterface $role, array $metadata = []): Collection;

    /**
     * Detaches a model from another model.
     *
     * @param  Model&RattachmentInterface  $rattachable  The model being detached
     * @param  Model&RattachmentInterface  $target  The target model to detach from
     *
     * @throws \RuntimeException If the attachment does not exist
     */
    public function detach(Model&RattachmentInterface $rattachable, Model&RattachmentInterface $target): void;

    /**
     * Detaches multiple models from a target model.
     *
     * @param  Collection<int, Model&RattachmentInterface>  $rattachables  Collection of models to detach
     * @param  Model&RattachmentInterface  $target  The target model
     */
    public function detachMultiple(Collection $rattachables, Model&RattachmentInterface $target): void;

    /**
     * Detaches a model from multiple targets.
     *
     * @param  Model&RattachmentInterface  $rattachable  The model to detach
     * @param  Collection<int, Model&RattachmentInterface>  $targets  Collection of target models
     */
    public function detachFromMultiple(Model&RattachmentInterface $rattachable, Collection $targets): void;

    /**
     * Detaches a model from all its attachments (both as rattachable and target).
     *
     * @param  Model&RattachmentInterface  $model  The model to detach from all attachments
     */
    public function detachAll(Model&RattachmentInterface $model): void;

    /**
     * Checks if a model is attached to another model.
     *
     * @param  Model&RattachmentInterface  $rattachable  The model being checked
     * @param  Model&RattachmentInterface  $target  The target model
     * @return bool True if attached, false otherwise
     */
    public function isAttached(Model&RattachmentInterface $rattachable, Model&RattachmentInterface $target): bool;

    /**
     * Checks if any model is attached to a target with a specific role.
     *
     * @param  Model&RattachmentInterface  $target  The target model
     * @param  EnumerableInterface  $role  The role to check
     * @return bool True if any attachment with the role exists
     */
    public function hasRoleAttached(Model&RattachmentInterface $target, EnumerableInterface $role): bool;

    /**
     * Retrieves all models attached to a target model.
     *
     * @param  Model&RattachmentInterface  $target  The target model
     * @return Collection<int, Model&RattachmentInterface> Collection of attached models
     */
    public function getRattachables(Model&RattachmentInterface $target): Collection;

    /**
     * Retrieves all targets of a specific type and role attached to a model.
     *
     * @param  Model&RattachmentInterface  $rattachable  The model
     * @param  string  $targetClass  The target class FQCN
     * @param  EnumerableInterface  $role  The role to filter by
     * @return Collection<int, Model&RattachmentInterface> Collection of target models
     */
    public function getTargetsByTypeAndRole(Model&RattachmentInterface $rattachable, string $targetClass, EnumerableInterface $role): Collection;

    /**
     * Retrieves all targets of a specific type with multiple roles attached to a model.
     *
     * @param  Model&RattachmentInterface  $rattachable  The model
     * @param  string  $targetClass  The target class FQCN
     * @param  array<int, EnumerableInterface>  $roles  Array of roles to filter by
     * @return Collection<int, Model&RattachmentInterface> Collection of target models
     */
    public function getTargetsByTypeAndRoles(Model&RattachmentInterface $rattachable, string $targetClass, array $roles): Collection;

    /**
     * Retrieves all targets of multiple types with multiple roles attached to a model.
     *
     * @param  Model&RattachmentInterface  $rattachable  The model
     * @param  array<int, string>  $targetClasses  Array of target class FQCNs
     * @param  array<int, EnumerableInterface>  $roles  Array of roles to filter by
     * @return Collection<int, Model&RattachmentInterface> Collection of target models
     */
    public function getTargetsByTypesAndRoles(Model&RattachmentInterface $rattachable, array $targetClasses, array $roles): Collection;

    /**
     * Retrieves all models attached to a target model with pagination.
     *
     * @param  Model&RattachmentInterface  $target  The target model
     * @param  int  $perPage  Items per page
     * @param  int  $page  Page number
     * @return LengthAwarePaginator Paginated results
     */
    public function getRattachablesPaginated(Model&RattachmentInterface $target, int $perPage = 15, int $page = 1): LengthAwarePaginator;

    /**
     * Retrieves all targets attached to a model.
     *
     * @param  Model&RattachmentInterface  $rattachable  The attached model
     * @return Collection<int, Model&RattachmentInterface> Collection of target models
     */
    public function getTargets(Model&RattachmentInterface $rattachable): Collection;

    /**
     * Retrieves all targets attached to a model with pagination.
     *
     * @param  Model&RattachmentInterface  $rattachable  The attached model
     * @param  int  $perPage  Items per page
     * @param  int  $page  Page number
     * @return LengthAwarePaginator Paginated results
     */
    public function getTargetsPaginated(Model&RattachmentInterface $rattachable, int $perPage = 15, int $page = 1): LengthAwarePaginator;

    /**
     * Retrieves all models attached to a target model with a specific role.
     *
     * @param  Model&RattachmentInterface  $target  The target model
     * @param  EnumerableInterface  $role  The role to filter by
     * @return Collection<int, Model&RattachmentInterface> Collection of attached models with the given role
     */
    public function getRattachablesByRole(Model&RattachmentInterface $target, EnumerableInterface $role): Collection;

    /**
     * Retrieves all models attached to a target model with a specific role and pagination.
     *
     * @param  Model&RattachmentInterface  $target  The target model
     * @param  EnumerableInterface  $role  The role to filter by
     * @param  int  $perPage  Items per page
     * @param  int  $page  Page number
     * @return LengthAwarePaginator Paginated results
     */
    public function getRattachablesByRolePaginated(Model&RattachmentInterface $target, EnumerableInterface $role, int $perPage = 15, int $page = 1): LengthAwarePaginator;

    /**
     * Retrieves all targets of a specific type attached to a model.
     *
     * @param  Model&RattachmentInterface  $rattachable  The model
     * @param  string  $targetClass  The target class FQCN
     * @return Collection<int, Model&RattachmentInterface> Collection of target models
     */
    public function getTargetsByType(Model&RattachmentInterface $rattachable, string $targetClass): Collection;

    /**
     * Retrieves all targets of a specific type attached to a model with pagination.
     *
     * @param  Model&RattachmentInterface  $rattachable  The model
     * @param  string  $targetClass  The target class FQCN
     * @param  int  $perPage  Items per page
     * @param  int  $page  Page number
     * @return LengthAwarePaginator Paginated results
     */
    public function getTargetsByTypePaginated(Model&RattachmentInterface $rattachable, string $targetClass, int $perPage = 15, int $page = 1): LengthAwarePaginator;

    /**
     * Retrieves all targets attached to a model with a specific role.
     *
     * @param  Model&RattachmentInterface  $rattachable  The attached model
     * @param  EnumerableInterface  $role  The role to filter by
     * @return Collection<int, Model&RattachmentInterface> Collection of target models with the given role
     */
    public function getTargetsByRole(Model&RattachmentInterface $rattachable, EnumerableInterface $role): Collection;

    /**
     * Retrieves all targets attached to a model with a specific role and pagination.
     *
     * @param  Model&RattachmentInterface  $rattachable  The attached model
     * @param  EnumerableInterface  $role  The role to filter by
     * @param  int  $perPage  Items per page
     * @param  int  $page  Page number
     * @return LengthAwarePaginator Paginated results
     */
    public function getTargetsByRolePaginated(Model&RattachmentInterface $rattachable, EnumerableInterface $role, int $perPage = 15, int $page = 1): LengthAwarePaginator;

    /**
     * Counts all models attached to a target model.
     *
     * @param  Model&RattachmentInterface  $target  The target model
     * @return int Total number of attachments
     */
    public function countRattachables(Model&RattachmentInterface $target): int;

    /**
     * Counts all targets attached to a model.
     *
     * @param  Model&RattachmentInterface  $rattachable  The attached model
     * @return int Total number of targets
     */
    public function countTargets(Model&RattachmentInterface $rattachable): int;

    /**
     * Counts models attached to a target with a specific role.
     *
     * @param  Model&RattachmentInterface  $target  The target model
     * @param  EnumerableInterface  $role  The role to filter by
     * @return int Number of attachments with the given role
     */
    public function countRattachablesByRole(Model&RattachmentInterface $target, EnumerableInterface $role): int;

    /**
     * Counts targets attached to a model with a specific role.
     *
     * @param  Model&RattachmentInterface  $rattachable  The attached model
     * @param  EnumerableInterface  $role  The role to filter by
     * @return int Number of targets with the given role
     */
    public function countTargetsByRole(Model&RattachmentInterface $rattachable, EnumerableInterface $role): int;

    /**
     * Retrieves all distinct roles for a target model.
     *
     * @param  Model&RattachmentInterface  $target  The target model
     * @return Collection<int, EnumerableInterface> Collection of distinct role enums
     */
    public function getDistinctRolesForTarget(Model&RattachmentInterface $target): Collection;

    /**
     * Retrieves all distinct roles for a rattachable model.
     *
     * @param  Model&RattachmentInterface  $rattachable  The attached model
     * @return Collection<int, EnumerableInterface> Collection of distinct role enums
     */
    public function getDistinctRolesForRattachable(Model&RattachmentInterface $rattachable): Collection;

    /**
     * Updates the role of an existing attachment.
     *
     * @param  Model&RattachmentInterface  $rattachable  The attached model
     * @param  Model&RattachmentInterface  $target  The target model
     * @param  EnumerableInterface  $role  The new role
     *
     * @throws \RuntimeException If the attachment does not exist or constraints are violated
     */
    public function updateRole(Model&RattachmentInterface $rattachable, Model&RattachmentInterface $target, EnumerableInterface $role): void;

    /**
     * Updates the role of multiple attachments.
     *
     * @param  Collection<int, Model&RattachmentInterface>  $rattachables  Collection of attached models
     * @param  Model&RattachmentInterface  $target  The target model
     * @param  EnumerableInterface  $role  The new role
     */
    public function updateRoleForMultiple(Collection $rattachables, Model&RattachmentInterface $target, EnumerableInterface $role): void;

    /**
     * Updates the metadata of an existing attachment.
     *
     * @param  Model&RattachmentInterface  $rattachable  The attached model
     * @param  Model&RattachmentInterface  $target  The target model
     * @param  StrictDataObject|array<string, mixed>  $metadata  The new metadata
     *
     * @throws \RuntimeException If the attachment does not exist
     */
    public function updateMetadata(Model&RattachmentInterface $rattachable, Model&RattachmentInterface $target, array|StrictDataObject $metadata): void;

    /**
     * Merges metadata into an existing attachment.
     * Preserves existing metadata and adds/overwrites with new values.
     *
     * @param  Model&RattachmentInterface  $rattachable  The attached model
     * @param  Model&RattachmentInterface  $target  The target model
     * @param  StrictDataObject|array<string, mixed>  $metadata  The metadata to merge
     *
     * @throws \RuntimeException If the attachment does not exist
     */
    public function mergeMetadata(Model&RattachmentInterface $rattachable, Model&RattachmentInterface $target, array|StrictDataObject $metadata): void;

    /**
     * Retrieves a specific attachment between two models.
     *
     * @param  Model&RattachmentInterface  $rattachable  The attached model
     * @param  Model&RattachmentInterface  $target  The target model
     * @return Model|null The attachment model or null if not found
     */
    public function getAttachment(Model&RattachmentInterface $rattachable, Model&RattachmentInterface $target): ?Model;

    /**
     * Checks if an attachment exists between two specific models.
     *
     * @param  Model&RattachmentInterface  $rattachable  The rattachable model
     * @param  Model&RattachmentInterface  $target  The target model
     * @return bool True if attachment exists
     */
    public function hasAttachmentsBetween(Model&RattachmentInterface $rattachable, Model&RattachmentInterface $target): bool;

    /**
     * Checks if attachments exist between two model types.
     *
     * @param  string  $rattachableType  The morph class of the rattachable model
     * @param  string  $targetType  The morph class of the target model
     * @return bool True if any attachment exists
     */
    public function hasAttachmentsBetweenTypes(string $rattachableType, string $targetType): bool;

    /**
     * Retrieves all attachments between two model types.
     *
     * @param  string  $rattachableType  The morph class of the rattachable model
     * @param  string  $targetType  The morph class of the target model
     * @return Collection<int, Model> Collection of attachment models
     */
    public function getAttachmentsBetweenTypes(string $rattachableType, string $targetType): Collection;

    /**
     * Deletes all attachments between two model types.
     *
     * @param  string  $rattachableType  The morph class of the rattachable model
     * @param  string  $targetType  The morph class of the target model
     * @return int Number of deleted attachments
     */
    public function deleteAllAttachmentsBetweenTypes(string $rattachableType, string $targetType): int;

    /**
     * Synchronizes attachments for a model with a given set of targets and roles.
     *
     * Creates new attachments, updates existing ones, and removes attachments
     * that are no longer present in the target list.
     *
     * @param  Model&RattachmentInterface  $rattachable  The attached model
     * @param  array<array{target: Model&RattachmentInterface, role: EnumerableInterface, metadata?: array<string, mixed>}>  $targets
     *                                                                                                                                 Array of targets with roles and optional metadata
     * @return Collection<int, Model> Collection of created/updated attachment models
     *
     * @throws \RuntimeException If any target is invalid or constraints are violated
     */
    public function syncAttachments(Model&RattachmentInterface $rattachable, array $targets): Collection;
}
