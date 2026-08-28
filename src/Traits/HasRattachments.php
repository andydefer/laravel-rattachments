<?php

declare(strict_types=1);

namespace AndyDefer\LaravelRattachments\Traits;

use AndyDefer\LaravelRattachments\Services\RattachmentService;
use AndyDefer\Repository\Contracts\EnumerableInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

/**
 * Trait for Eloquent models that can have polymorphic attachments.
 *
 * Provides a fluent API for managing attachments directly on the model,
 * including reading, querying, creating, updating, and deleting attachments.
 *
 * @example
 * class User extends Model
 * {
 *     use HasRattachments;
 * }
 *
 * // Reading
 * $hospitals = $user->getTargetsByRole(Role::DOCTOR);
 * $count = $user->countTargets();
 *
 * // Writing
 * $user->attachTo($hospital, Role::DOCTOR);
 * $user->detachFrom($hospital);
 */
trait HasRattachments
{
    /**
     * Gets the Rattachment service instance.
     *
     * @return RattachmentService The service instance
     */
    protected function getRattachmentService(): RattachmentService
    {
        return app(RattachmentService::class);
    }

    /**
     * Attaches this model to another model with a role and optional metadata.
     *
     * @param  Model  $target  The target model to attach to
     * @param  EnumerableInterface  $role  The role for the attachment
     * @param  array<string, mixed>  $metadata  Additional metadata for the attachment
     * @return Model The created rattachment model
     *
     * @throws \RuntimeException If the attachment already exists or constraints are violated
     */
    public function attachTo(Model $target, EnumerableInterface $role, array $metadata = []): Model
    {
        return $this->getRattachmentService()->attach($this, $target, $role, $metadata);
    }

    /**
     * Attaches this model to multiple targets with a role and optional metadata.
     *
     * @param  Collection<int, Model>  $targets  Collection of target models
     * @param  EnumerableInterface  $role  The role for all attachments
     * @param  array<string, mixed>  $metadata  Additional metadata for all attachments
     * @return Collection<int, Model> Collection of created rattachment models
     *
     * @throws \RuntimeException If any attachment already exists or constraints are violated
     */
    public function attachToMultiple(Collection $targets, EnumerableInterface $role, array $metadata = []): Collection
    {
        return $this->getRattachmentService()->attachToMultiple($this, $targets, $role, $metadata);
    }

    /**
     * Detaches this model from another model.
     *
     * @param  Model  $target  The target model to detach from
     *
     * @throws \RuntimeException If the attachment does not exist
     */
    public function detachFrom(Model $target): void
    {
        $this->getRattachmentService()->detach($this, $target);
    }

    /**
     * Detaches this model from multiple targets.
     *
     * @param  Collection<int, Model>  $targets  Collection of target models
     */
    public function detachFromMultiple(Collection $targets): void
    {
        $this->getRattachmentService()->detachFromMultiple($this, $targets);
    }

    /**
     * Detaches this model from all its attachments (both as rattachable and target).
     */
    public function detachAll(): void
    {
        $this->getRattachmentService()->detachAll($this);
    }

    /**
     * Synchronizes attachments for this model.
     *
     * Creates new attachments, updates existing ones, and removes attachments
     * that are no longer present in the target list.
     *
     * @param  array<array{target: Model, role: EnumerableInterface, metadata?: array<string, mixed>}>  $targets
     *                                                                                                            Array of targets with roles and optional metadata
     * @return Collection<int, Model> Collection of created/updated attachment models
     *
     * @throws \RuntimeException If any target is invalid or constraints are violated
     */
    public function syncAttachments(array $targets): Collection
    {
        return $this->getRattachmentService()->syncAttachments($this, $targets);
    }

    /**
     * Attaches multiple models to this model (as target) with a role and optional metadata.
     *
     * @param  Collection<int, Model>  $rattachables  Collection of models to attach
     * @param  EnumerableInterface  $role  The role for all attachments
     * @param  array<string, mixed>  $metadata  Additional metadata for all attachments
     * @return Collection<int, Model> Collection of created rattachment models
     *
     * @throws \RuntimeException If any attachment already exists or constraints are violated
     */
    public function attachMany(Collection $rattachables, EnumerableInterface $role, array $metadata = []): Collection
    {
        return $this->getRattachmentService()->attachMultiple($rattachables, $this, $role, $metadata);
    }

    /**
     * Detaches multiple models from this model (as target).
     *
     * @param  Collection<int, Model>  $rattachables  Collection of models to detach
     */
    public function detachMany(Collection $rattachables): void
    {
        $this->getRattachmentService()->detachMultiple($rattachables, $this);
    }

    /**
     * Updates the role for a specific target.
     *
     * @param  Model  $target  The target model
     * @param  EnumerableInterface  $role  The new role
     *
     * @throws \RuntimeException If the attachment does not exist or constraints are violated
     */
    public function updateRoleFor(Model $target, EnumerableInterface $role): void
    {
        $this->getRattachmentService()->updateRole($this, $target, $role);
    }

    /**
     * Updates the role for multiple rattachables attached to this model.
     *
     * @param  Collection<int, Model>  $rattachables  Collection of attached models
     * @param  EnumerableInterface  $role  The new role
     */
    public function updateRoleForMany(Collection $rattachables, EnumerableInterface $role): void
    {
        $this->getRattachmentService()->updateRoleForMultiple($rattachables, $this, $role);
    }

    /**
     * Updates the metadata for a specific target.
     *
     * @param  Model  $target  The target model
     * @param  array<string, mixed>  $metadata  The new metadata
     *
     * @throws \RuntimeException If the attachment does not exist
     */
    public function updateMetadataFor(Model $target, array $metadata): void
    {
        $this->getRattachmentService()->updateMetadata($this, $target, $metadata);
    }

    /**
     * Merges metadata for a specific target.
     *
     * Preserves existing metadata and adds/overwrites with new values.
     *
     * @param  Model  $target  The target model
     * @param  array<string, mixed>  $metadata  The metadata to merge
     *
     * @throws \RuntimeException If the attachment does not exist
     */
    public function mergeMetadataFor(Model $target, array $metadata): void
    {
        $this->getRattachmentService()->mergeMetadata($this, $target, $metadata);
    }

    /**
     * Checks if this model is attached to another model.
     *
     * @param  Model  $target  The target model to check
     * @return bool True if attached, false otherwise
     */
    public function isAttachedTo(Model $target): bool
    {
        return $this->getRattachmentService()->isAttached($this, $target);
    }

    /**
     * Checks if a target model has a specific role attached.
     *
     * @param  Model  $target  The target model
     * @param  EnumerableInterface  $role  The role to check
     * @return bool True if the target has the role attached
     */
    public function hasRoleAttachedTo(Model $target, EnumerableInterface $role): bool
    {
        return $this->getRattachmentService()->hasRoleAttached($target, $role);
    }

    /**
     * Retrieves a specific attachment between this model and a target.
     *
     * @param  Model  $target  The target model
     * @return Model|null The attachment model or null if not found
     */
    public function getAttachment(Model $target): ?Model
    {
        return $this->getRattachmentService()->getAttachment($this, $target);
    }

    /**
     * Checks if an attachment exists between this model and a target.
     *
     * @param  Model  $target  The target model
     * @return bool True if attachment exists
     */
    public function hasAttachmentsBetween(Model $target): bool
    {
        return $this->getRattachmentService()->hasAttachmentsBetween($this, $target);
    }

    /**
     * Retrieves all targets this model is attached to.
     *
     * @return Collection<int, Model> Collection of target models
     */
    public function getTargets(): Collection
    {
        return $this->getRattachmentService()->getTargets($this);
    }

    /**
     * Retrieves all targets this model is attached to with pagination.
     *
     * @param  int  $perPage  Items per page
     * @param  int  $page  Page number
     * @return LengthAwarePaginator Paginated results
     */
    public function getTargetsPaginated(int $perPage = 15, int $page = 1): LengthAwarePaginator
    {
        return $this->getRattachmentService()->getTargetsPaginated($this, $perPage, $page);
    }

    /**
     * Retrieves all targets attached to this model with a specific role.
     *
     * @param  EnumerableInterface  $role  The role to filter by
     * @return Collection<int, Model> Collection of target models with the given role
     */
    public function getTargetsByRole(EnumerableInterface $role): Collection
    {
        return $this->getRattachmentService()->getTargetsByRole($this, $role);
    }

    /**
     * Retrieves all targets attached to this model with a specific role and pagination.
     *
     * @param  EnumerableInterface  $role  The role to filter by
     * @param  int  $perPage  Items per page
     * @param  int  $page  Page number
     * @return LengthAwarePaginator Paginated results
     */
    public function getTargetsByRolePaginated(EnumerableInterface $role, int $perPage = 15, int $page = 1): LengthAwarePaginator
    {
        return $this->getRattachmentService()->getTargetsByRolePaginated($this, $role, $perPage, $page);
    }

    /**
     * Retrieves all targets of a specific type attached to this model.
     *
     * @param  string  $targetClass  The target class FQCN (e.g., Hospital::class)
     * @return Collection<int, Model> Collection of target models
     */
    public function getTargetsByType(string $targetClass): Collection
    {
        return $this->getRattachmentService()->getTargetsByType($this, $targetClass);
    }

    /**
     * Retrieves all targets of a specific type attached to this model with pagination.
     *
     * @param  string  $targetClass  The target class FQCN (e.g., Hospital::class)
     * @param  int  $perPage  Items per page
     * @param  int  $page  Page number
     * @return LengthAwarePaginator Paginated results
     */
    public function getTargetsByTypePaginated(string $targetClass, int $perPage = 15, int $page = 1): LengthAwarePaginator
    {
        return $this->getRattachmentService()->getTargetsByTypePaginated($this, $targetClass, $perPage, $page);
    }

    /**
     * Retrieves all targets of a specific type and role attached to this model.
     *
     * @param  string  $targetClass  The target class FQCN (e.g., Hospital::class)
     * @param  EnumerableInterface  $role  The role to filter by
     * @return Collection<int, Model> Collection of target models
     */
    public function getTargetsByTypeAndRole(string $targetClass, EnumerableInterface $role): Collection
    {
        return $this->getRattachmentService()->getTargetsByTypeAndRole($this, $targetClass, $role);
    }

    /**
     * Retrieves all targets of a specific type with multiple roles attached to this model.
     *
     * @param  string  $targetClass  The target class FQCN (e.g., Hospital::class)
     * @param  array<int, EnumerableInterface>  $roles  Array of roles to filter by
     * @return Collection<int, Model> Collection of target models
     */
    public function getTargetsByTypeAndRoles(string $targetClass, array $roles): Collection
    {
        return $this->getRattachmentService()->getTargetsByTypeAndRoles($this, $targetClass, $roles);
    }

    /**
     * Retrieves all targets of multiple types with multiple roles attached to this model.
     *
     * @param  array<int, string>  $targetClasses  Array of target class FQCNs
     * @param  array<int, EnumerableInterface>  $roles  Array of roles to filter by
     * @return Collection<int, Model> Collection of target models
     */
    public function getTargetsByTypesAndRoles(array $targetClasses, array $roles): Collection
    {
        return $this->getRattachmentService()->getTargetsByTypesAndRoles($this, $targetClasses, $roles);
    }

    /**
     * Counts all targets attached to this model.
     *
     * @return int Total number of targets
     */
    public function countTargets(): int
    {
        return $this->getRattachmentService()->countTargets($this);
    }

    /**
     * Counts targets attached to this model with a specific role.
     *
     * @param  EnumerableInterface  $role  The role to filter by
     * @return int Number of targets with the given role
     */
    public function countTargetsByRole(EnumerableInterface $role): int
    {
        return $this->getRattachmentService()->countTargetsByRole($this, $role);
    }

    /**
     * Retrieves all distinct roles for this model.
     *
     * @return Collection<int, EnumerableInterface> Collection of distinct role enums
     */
    public function getDistinctRoles(): Collection
    {
        return $this->getRattachmentService()->getDistinctRolesForRattachable($this);
    }

    /**
     * Retrieves all models attached to this model (when this model is used as target).
     *
     * @return Collection<int, Model> Collection of attached models
     */
    public function getRattachables(): Collection
    {
        return $this->getRattachmentService()->getRattachables($this);
    }

    /**
     * Retrieves all models attached to this model with pagination.
     *
     * @param  int  $perPage  Items per page
     * @param  int  $page  Page number
     * @return LengthAwarePaginator Paginated results
     */
    public function getRattachablesPaginated(int $perPage = 15, int $page = 1): LengthAwarePaginator
    {
        return $this->getRattachmentService()->getRattachablesPaginated($this, $perPage, $page);
    }

    /**
     * Retrieves all models attached to this model with a specific role.
     *
     * @param  EnumerableInterface  $role  The role to filter by
     * @return Collection<int, Model> Collection of attached models with the given role
     */
    public function getRattachablesByRole(EnumerableInterface $role): Collection
    {
        return $this->getRattachmentService()->getRattachablesByRole($this, $role);
    }

    /**
     * Retrieves all models attached to this model with a specific role and pagination.
     *
     * @param  EnumerableInterface  $role  The role to filter by
     * @param  int  $perPage  Items per page
     * @param  int  $page  Page number
     * @return LengthAwarePaginator Paginated results
     */
    public function getRattachablesByRolePaginated(EnumerableInterface $role, int $perPage = 15, int $page = 1): LengthAwarePaginator
    {
        return $this->getRattachmentService()->getRattachablesByRolePaginated($this, $role, $perPage, $page);
    }

    /**
     * Counts all models attached to this model.
     *
     * @return int Total number of attached models
     */
    public function countRattachables(): int
    {
        return $this->getRattachmentService()->countRattachables($this);
    }

    /**
     * Counts models attached to this model with a specific role.
     *
     * @param  EnumerableInterface  $role  The role to filter by
     * @return int Number of attached models with the given role
     */
    public function countRattachablesByRole(EnumerableInterface $role): int
    {
        return $this->getRattachmentService()->countRattachablesByRole($this, $role);
    }

    /**
     * Retrieves all distinct roles for this model when used as target.
     *
     * @return Collection<int, EnumerableInterface> Collection of distinct role enums
     */
    public function getDistinctRolesForTarget(): Collection
    {
        return $this->getRattachmentService()->getDistinctRolesForTarget($this);
    }
}
