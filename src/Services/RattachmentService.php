<?php

declare(strict_types=1);

namespace AndyDefer\LaravelRattachments\Services;

use AndyDefer\DomainStructures\Utils\StrictDataObject;
use AndyDefer\LaravelRattachments\Contracts\RattachmentConstraintsInterface;
use AndyDefer\LaravelRattachments\Contracts\Services\RattachmentServiceInterface;
use AndyDefer\LaravelRattachments\Records\RattachmentFilterRecord;
use AndyDefer\LaravelRattachments\Records\RattachmentRecord;
use AndyDefer\LaravelRattachments\Repositories\RattachmentRepository;
use AndyDefer\Repository\Contracts\EnumerableInterface;
use AndyDefer\Repository\Records\FindByRecord;
use AndyDefer\Repository\Records\PaginateRecord;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use RuntimeException;

/**
 * Service for managing polymorphic attachments between Eloquent models.
 *
 * This service orchestrates all attachment operations including creation, update,
 * deletion, and querying with support for roles, metadata, and constraints.
 */
final class RattachmentService implements RattachmentServiceInterface
{
    public function __construct(
        private readonly RattachmentRepository $repository,
    ) {}

    /**
     * Validates that the attachment respects the constraints defined by the rattachable model.
     *
     * Checks both allowed targets and allowed roles.
     *
     * @param  Model  $rattachable  The model being attached
     * @param  Model  $target  The target model
     * @param  EnumerableInterface|null  $role  The role (nullable)
     *
     * @throws RuntimeException If constraints are violated
     */
    private function validateConstraints(Model $rattachable, Model $target, ?EnumerableInterface $role): void
    {
        if (! $rattachable instanceof RattachmentConstraintsInterface) {
            return;
        }

        $allowed = $rattachable->allowedTargets();
        $targetClass = $target->getMorphClass();

        if (! isset($allowed[$targetClass])) {
            $allowedTargets = array_keys($allowed);

            throw new RuntimeException(sprintf(
                '%s cannot be attached to %s. Allowed targets: %s',
                $rattachable->getMorphClass(),
                $target->getMorphClass(),
                ! empty($allowedTargets) ? implode(', ', $allowedTargets) : 'none'
            ));
        }

        if ($role === null) {
            return;
        }

        $allowedRoles = $allowed[$targetClass];
        if (! in_array($role, $allowedRoles, true)) {
            $allowedValues = array_map(fn ($r) => $r->getValue(), $allowedRoles);

            throw new RuntimeException(sprintf(
                'Role "%s" is not allowed for %s -> %s. Allowed roles: %s',
                $role->getValue(),
                $rattachable->getMorphClass(),
                $target->getMorphClass(),
                ! empty($allowedValues) ? implode(', ', $allowedValues) : 'none'
            ));
        }
    }

    /**
     * Validates that the attachment does not violate unique target constraints.
     *
     * A model can only have ONE attachment per unique target type.
     *
     * @param  Model  $rattachable  The model being attached
     * @param  Model  $target  The target model
     *
     * @throws RuntimeException If unique constraint is violated
     */
    private function validateUniqueConstraints(Model $rattachable, Model $target): void
    {
        if (! $rattachable instanceof RattachmentConstraintsInterface) {
            return;
        }

        $uniqueTargets = $rattachable->uniqueTargets();
        $targetClass = $target->getMorphClass();

        if (! in_array($targetClass, $uniqueTargets, true)) {
            return;
        }

        $filter = RattachmentFilterRecord::from([
            'rattachable_type' => $rattachable->getMorphClass(),
            'rattachable_id' => $rattachable->getKey(),
            'target_type' => $targetClass,
        ]);

        $findByRecord = new FindByRecord(
            filters: $filter,
            limit: 1,
        );

        $existing = $this->repository->findBy($findByRecord);

        if ($existing->isNotEmpty()) {
            throw new RuntimeException(sprintf(
                '%s already has a unique attachment to %s. Only one %s is allowed.',
                $rattachable->getMorphClass(),
                $targetClass,
                class_basename($targetClass)
            ));
        }
    }

    /**
     * {@inheritDoc}
     */
    public function attach(Model $rattachable, Model $target, ?EnumerableInterface $role = null, array $metadata = []): Model
    {
        $this->validateConstraints($rattachable, $target, $role);
        $this->validateUniqueConstraints($rattachable, $target);

        if ($this->isAttached($rattachable, $target)) {
            throw new RuntimeException(sprintf(
                '%s %s is already attached to %s %s',
                $rattachable->getMorphClass(),
                $rattachable->getKey(),
                $target->getMorphClass(),
                $target->getKey()
            ));
        }

        $record = RattachmentRecord::from([
            'rattachable_type' => $rattachable->getMorphClass(),
            'rattachable_id' => $rattachable->getKey(),
            'target_type' => $target->getMorphClass(),
            'target_id' => $target->getKey(),
            'role' => $role,
            'metadata' => ! empty($metadata) ? $metadata : null,
        ]);

        return $this->repository->create($record);
    }

    /**
     * {@inheritDoc}
     */
    public function attachMultiple(Collection $rattachables, Model $target, ?EnumerableInterface $role = null, array $metadata = []): Collection
    {
        $results = new Collection;

        foreach ($rattachables as $rattachable) {
            $results->add($this->attach($rattachable, $target, $role, $metadata));
        }

        return $results;
    }

    /**
     * {@inheritDoc}
     */
    public function attachToMultiple(Model $rattachable, Collection $targets, ?EnumerableInterface $role = null, array $metadata = []): Collection
    {
        $results = new Collection;

        foreach ($targets as $target) {
            $results->add($this->attach($rattachable, $target, $role, $metadata));
        }

        return $results;
    }

    /**
     * {@inheritDoc}
     */
    public function detach(Model $rattachable, Model $target): void
    {
        $existing = $this->findExisting($rattachable, $target);

        if (! $existing) {
            throw new RuntimeException(sprintf(
                '%s %s is not attached to %s %s',
                $rattachable->getMorphClass(),
                $rattachable->getKey(),
                $target->getMorphClass(),
                $target->getKey()
            ));
        }

        $this->repository->delete($existing->id);
    }

    /**
     * {@inheritDoc}
     */
    public function detachMultiple(Collection $rattachables, Model $target): void
    {
        foreach ($rattachables as $rattachable) {
            $this->detach($rattachable, $target);
        }
    }

    /**
     * {@inheritDoc}
     */
    public function detachFromMultiple(Model $rattachable, Collection $targets): void
    {
        foreach ($targets as $target) {
            $this->detach($rattachable, $target);
        }
    }

    /**
     * {@inheritDoc}
     */
    public function detachAll(Model $model): void
    {
        $this->repository->deleteBulk(
            RattachmentFilterRecord::from([
                'rattachable_type' => $model->getMorphClass(),
                'rattachable_id' => $model->getKey(),
            ])
        );

        $this->repository->deleteBulk(
            RattachmentFilterRecord::from([
                'target_type' => $model->getMorphClass(),
                'target_id' => $model->getKey(),
            ])
        );
    }

    /**
     * Finds an existing attachment between two models.
     *
     * @param  Model  $rattachable  The rattachable model
     * @param  Model  $target  The target model
     * @return Model|null The attachment model or null if not found
     */
    private function findExisting(Model $rattachable, Model $target): ?Model
    {
        $filter = RattachmentFilterRecord::from([
            'rattachable_type' => $rattachable->getMorphClass(),
            'rattachable_id' => $rattachable->getKey(),
            'target_type' => $target->getMorphClass(),
            'target_id' => $target->getKey(),
        ]);

        $findByRecord = new FindByRecord(
            filters: $filter,
            limit: 1,
        );

        $collection = $this->repository->findBy($findByRecord);

        return $collection->first();
    }

    /**
     * {@inheritDoc}
     */
    public function isAttached(Model $rattachable, Model $target): bool
    {
        $filter = RattachmentFilterRecord::from([
            'rattachable_type' => $rattachable->getMorphClass(),
            'rattachable_id' => $rattachable->getKey(),
            'target_type' => $target->getMorphClass(),
            'target_id' => $target->getKey(),
        ]);

        return $this->repository->exists($filter);
    }

    /**
     * {@inheritDoc}
     */
    public function hasRoleAttached(Model $target, EnumerableInterface $role): bool
    {
        $filter = RattachmentFilterRecord::from([
            'target_type' => $target->getMorphClass(),
            'target_id' => $target->getKey(),
            'role' => $role,
        ]);

        return $this->repository->exists($filter);
    }

    /**
     * {@inheritDoc}
     */
    public function getRattachables(Model $target): Collection
    {
        $filter = RattachmentFilterRecord::from([
            'target_type' => $target->getMorphClass(),
            'target_id' => $target->getKey(),
        ]);

        $findByRecord = new FindByRecord(filters: $filter);

        $rattachments = $this->repository->findBy($findByRecord);

        return $rattachments->map(fn ($rattachment) => $rattachment->rattachable);
    }

    /**
     * {@inheritDoc}
     */
    public function getRattachablesPaginated(Model $target, int $perPage = 15, int $page = 1): LengthAwarePaginator
    {
        $filter = RattachmentFilterRecord::from([
            'target_type' => $target->getMorphClass(),
            'target_id' => $target->getKey(),
        ]);

        $paginateRecord = new PaginateRecord(
            perPage: $perPage,
            page: $page,
            filters: $filter,
        );

        return $this->repository->paginate($paginateRecord);
    }

    /**
     * {@inheritDoc}
     */
    public function getTargets(Model $rattachable): Collection
    {
        $filter = RattachmentFilterRecord::from([
            'rattachable_type' => $rattachable->getMorphClass(),
            'rattachable_id' => $rattachable->getKey(),
        ]);

        $findByRecord = new FindByRecord(filters: $filter);

        $rattachments = $this->repository->findBy($findByRecord);

        return $rattachments->map(fn ($rattachment) => $rattachment->target);
    }

    /**
     * {@inheritDoc}
     */
    public function getTargetsPaginated(Model $rattachable, int $perPage = 15, int $page = 1): LengthAwarePaginator
    {
        $filter = RattachmentFilterRecord::from([
            'rattachable_type' => $rattachable->getMorphClass(),
            'rattachable_id' => $rattachable->getKey(),
        ]);

        $paginateRecord = new PaginateRecord(
            perPage: $perPage,
            page: $page,
            filters: $filter,
        );

        return $this->repository->paginate($paginateRecord);
    }

    /**
     * {@inheritDoc}
     */
    public function getRattachablesByRole(Model $target, EnumerableInterface $role): Collection
    {
        $filter = RattachmentFilterRecord::from([
            'target_type' => $target->getMorphClass(),
            'target_id' => $target->getKey(),
            'role' => $role,
        ]);

        $findByRecord = new FindByRecord(filters: $filter);

        $rattachments = $this->repository->findBy($findByRecord);

        return $rattachments->map(fn ($rattachment) => $rattachment->rattachable);
    }

    /**
     * {@inheritDoc}
     */
    public function getRattachablesByRolePaginated(Model $target, EnumerableInterface $role, int $perPage = 15, int $page = 1): LengthAwarePaginator
    {
        $filter = RattachmentFilterRecord::from([
            'target_type' => $target->getMorphClass(),
            'target_id' => $target->getKey(),
            'role' => $role,
        ]);

        $paginateRecord = new PaginateRecord(
            perPage: $perPage,
            page: $page,
            filters: $filter,
        );

        return $this->repository->paginate($paginateRecord);
    }

    /**
     * {@inheritDoc}
     */
    public function getTargetsByRole(Model $rattachable, EnumerableInterface $role): Collection
    {
        $filter = RattachmentFilterRecord::from([
            'rattachable_type' => $rattachable->getMorphClass(),
            'rattachable_id' => $rattachable->getKey(),
            'role' => $role,
        ]);

        $findByRecord = new FindByRecord(filters: $filter);

        $rattachments = $this->repository->findBy($findByRecord);

        return $rattachments->map(fn ($rattachment) => $rattachment->target);
    }

    /**
     * {@inheritDoc}
     */
    public function getTargetsByRolePaginated(Model $rattachable, EnumerableInterface $role, int $perPage = 15, int $page = 1): LengthAwarePaginator
    {
        $filter = RattachmentFilterRecord::from([
            'rattachable_type' => $rattachable->getMorphClass(),
            'rattachable_id' => $rattachable->getKey(),
            'role' => $role,
        ]);

        $paginateRecord = new PaginateRecord(
            perPage: $perPage,
            page: $page,
            filters: $filter,
        );

        return $this->repository->paginate($paginateRecord);
    }

    /**
     * {@inheritDoc}
     */
    public function countRattachables(Model $target): int
    {
        $filter = RattachmentFilterRecord::from([
            'target_type' => $target->getMorphClass(),
            'target_id' => $target->getKey(),
        ]);

        return $this->repository->count($filter);
    }

    /**
     * {@inheritDoc}
     */
    public function countTargets(Model $rattachable): int
    {
        $filter = RattachmentFilterRecord::from([
            'rattachable_type' => $rattachable->getMorphClass(),
            'rattachable_id' => $rattachable->getKey(),
        ]);

        return $this->repository->count($filter);
    }

    /**
     * {@inheritDoc}
     */
    public function countRattachablesByRole(Model $target, EnumerableInterface $role): int
    {
        $filter = RattachmentFilterRecord::from([
            'target_type' => $target->getMorphClass(),
            'target_id' => $target->getKey(),
            'role' => $role,
        ]);

        return $this->repository->count($filter);
    }

    /**
     * {@inheritDoc}
     */
    public function countTargetsByRole(Model $rattachable, EnumerableInterface $role): int
    {
        $filter = RattachmentFilterRecord::from([
            'rattachable_type' => $rattachable->getMorphClass(),
            'rattachable_id' => $rattachable->getKey(),
            'role' => $role,
        ]);

        return $this->repository->count($filter);
    }

    /**
     * {@inheritDoc}
     */
    public function getDistinctRolesForTarget(Model $target): Collection
    {
        $filter = RattachmentFilterRecord::from([
            'target_type' => $target->getMorphClass(),
            'target_id' => $target->getKey(),
        ]);

        $findByRecord = new FindByRecord(filters: $filter);

        $rattachments = $this->repository->findBy($findByRecord);

        return $rattachments->pluck('role')->unique()->values();
    }

    /**
     * {@inheritDoc}
     */
    public function getDistinctRolesForRattachable(Model $rattachable): Collection
    {
        $filter = RattachmentFilterRecord::from([
            'rattachable_type' => $rattachable->getMorphClass(),
            'rattachable_id' => $rattachable->getKey(),
        ]);

        $findByRecord = new FindByRecord(filters: $filter);

        $rattachments = $this->repository->findBy($findByRecord);

        return $rattachments->pluck('role')->unique()->values();
    }

    /**
     * {@inheritDoc}
     */
    public function getTargetsByType(Model $rattachable, string $targetClass): Collection
    {
        $filter = RattachmentFilterRecord::from([
            'rattachable_type' => $rattachable->getMorphClass(),
            'rattachable_id' => $rattachable->getKey(),
            'target_type' => $targetClass,
        ]);

        $findByRecord = new FindByRecord(filters: $filter);

        $rattachments = $this->repository->findBy($findByRecord);

        return $rattachments->map(fn ($rattachment) => $rattachment->target);
    }

    /**
     * {@inheritDoc}
     */
    public function getTargetsByTypePaginated(Model $rattachable, string $targetClass, int $perPage = 15, int $page = 1): LengthAwarePaginator
    {
        $filter = RattachmentFilterRecord::from([
            'rattachable_type' => $rattachable->getMorphClass(),
            'rattachable_id' => $rattachable->getKey(),
            'target_type' => $targetClass,
        ]);

        $paginateRecord = new PaginateRecord(
            perPage: $perPage,
            page: $page,
            filters: $filter,
        );

        return $this->repository->paginate($paginateRecord);
    }

    /**
     * {@inheritDoc}
     */
    public function updateRole(Model $rattachable, Model $target, EnumerableInterface $role): void
    {
        $this->validateConstraints($rattachable, $target, $role);

        $existing = $this->findExisting($rattachable, $target);

        if (! $existing) {
            throw new RuntimeException(sprintf(
                '%s %s is not attached to %s %s',
                $rattachable->getMorphClass(),
                $rattachable->getKey(),
                $target->getMorphClass(),
                $target->getKey()
            ));
        }

        $this->repository->update($existing->id, RattachmentRecord::from(['role' => $role]));
    }

    /**
     * {@inheritDoc}
     */
    public function updateRoleForMultiple(Collection $rattachables, Model $target, EnumerableInterface $role): void
    {
        foreach ($rattachables as $rattachable) {
            $this->updateRole($rattachable, $target, $role);
        }
    }

    /**
     * {@inheritDoc}
     */
    public function updateMetadata(Model $rattachable, Model $target, array $metadata): void
    {
        $existing = $this->findExisting($rattachable, $target);

        if (! $existing) {
            throw new RuntimeException(sprintf(
                '%s %s is not attached to %s %s',
                $rattachable->getMorphClass(),
                $rattachable->getKey(),
                $target->getMorphClass(),
                $target->getKey()
            ));
        }

        $this->repository->update($existing->id, RattachmentRecord::from(['metadata' => $metadata]));
    }

    /**
     * {@inheritDoc}
     */
    public function mergeMetadata(Model $rattachable, Model $target, array $metadata): void
    {
        $existing = $this->findExisting($rattachable, $target);

        if (! $existing) {
            throw new RuntimeException(sprintf(
                '%s %s is not attached to %s %s',
                $rattachable->getMorphClass(),
                $rattachable->getKey(),
                $target->getMorphClass(),
                $target->getKey()
            ));
        }

        $currentMetadata = $existing->metadata ?? new StrictDataObject;
        $mergedMetadata = $currentMetadata->merge($metadata)->toArray();

        $this->repository->update($existing->id, RattachmentRecord::from(['metadata' => $mergedMetadata]));
    }

    /**
     * {@inheritDoc}
     */
    public function getAttachment(Model $rattachable, Model $target): ?Model
    {
        return $this->findExisting($rattachable, $target);
    }

    /**
     * {@inheritDoc}
     */
    public function hasAttachmentsBetween(Model $rattachable, Model $target): bool
    {
        $filter = RattachmentFilterRecord::from([
            'rattachable_type' => $rattachable->getMorphClass(),
            'rattachable_id' => $rattachable->getKey(),
            'target_type' => $target->getMorphClass(),
            'target_id' => $target->getKey(),
        ]);

        return $this->repository->exists($filter);
    }

    /**
     * {@inheritDoc}
     */
    public function hasAttachmentsBetweenTypes(string $rattachableType, string $targetType): bool
    {
        $filter = RattachmentFilterRecord::from([
            'rattachable_type' => $rattachableType,
            'target_type' => $targetType,
        ]);

        return $this->repository->exists($filter);
    }

    /**
     * {@inheritDoc}
     */
    public function getAttachmentsBetweenTypes(string $rattachableType, string $targetType): Collection
    {
        $filter = RattachmentFilterRecord::from([
            'rattachable_type' => $rattachableType,
            'target_type' => $targetType,
        ]);

        $findByRecord = new FindByRecord(filters: $filter);

        return $this->repository->findBy($findByRecord);
    }

    /**
     * {@inheritDoc}
     */
    public function deleteAllAttachmentsBetweenTypes(string $rattachableType, string $targetType): int
    {
        $filter = RattachmentFilterRecord::from([
            'rattachable_type' => $rattachableType,
            'target_type' => $targetType,
        ]);

        return $this->repository->deleteBulk($filter);
    }

    /**
     * {@inheritDoc}
     */
    public function syncAttachments(Model $rattachable, array $targets): Collection
    {
        $results = new Collection;

        $existingAttachments = $this->getTargets($rattachable);
        $existingTargetIds = $existingAttachments->pluck('id')->toArray();

        $newTargetIds = [];

        foreach ($targets as $targetData) {
            if (! isset($targetData['target'])) {
                throw new RuntimeException('Each target must have "target" key');
            }

            $target = $targetData['target'];
            $role = $targetData['role'] ?? null;
            $metadata = $targetData['metadata'] ?? [];

            $this->validateConstraints($rattachable, $target, $role);
            $this->validateUniqueConstraints($rattachable, $target);

            $newTargetIds[] = $target->getKey();

            $existing = $this->findExisting($rattachable, $target);

            if ($existing) {
                if ($role !== null) {
                    $this->updateRole($rattachable, $target, $role);
                }

                if (! empty($metadata)) {
                    $this->updateMetadata($rattachable, $target, $metadata);
                }

                $results->add($existing);
            } else {
                $attachment = $this->attach($rattachable, $target, $role, $metadata);
                $results->add($attachment);
            }
        }

        $idsToDelete = array_diff($existingTargetIds, $newTargetIds);

        foreach ($idsToDelete as $targetId) {
            $target = $existingAttachments->firstWhere('id', $targetId);
            if ($target) {
                $this->detach($rattachable, $target);
            }
        }

        return $results;
    }

    /**
     * {@inheritDoc}
     */
    public function getTargetsByTypeAndRole(Model $rattachable, string $targetClass, EnumerableInterface $role): Collection
    {
        $filter = RattachmentFilterRecord::from([
            'rattachable_type' => $rattachable->getMorphClass(),
            'rattachable_id' => $rattachable->getKey(),
            'target_type' => $targetClass,
            'role' => $role,
        ]);

        $findByRecord = new FindByRecord(filters: $filter);

        $rattachments = $this->repository->findBy($findByRecord);

        return $rattachments->map(fn ($rattachment) => $rattachment->target);
    }

    /**
     * {@inheritDoc}
     */
    public function getTargetsByTypeAndRoles(Model $rattachable, string $targetClass, array $roles): Collection
    {
        $filter = RattachmentFilterRecord::from([
            'rattachable_type' => $rattachable->getMorphClass(),
            'rattachable_id' => $rattachable->getKey(),
            'target_type' => $targetClass,
        ]);

        $findByRecord = new FindByRecord(filters: $filter);

        $rattachments = $this->repository->findBy($findByRecord);

        $roleValues = array_map(fn ($role) => $role->getValue(), $roles);

        return $rattachments
            ->filter(fn ($rattachment) => in_array($rattachment->role?->getValue(), $roleValues, true))
            ->map(fn ($rattachment) => $rattachment->target);
    }

    /**
     * {@inheritDoc}
     */
    public function getTargetsByTypesAndRoles(Model $rattachable, array $targetClasses, array $roles): Collection
    {
        $filter = RattachmentFilterRecord::from([
            'rattachable_type' => $rattachable->getMorphClass(),
            'rattachable_id' => $rattachable->getKey(),
        ]);

        $findByRecord = new FindByRecord(filters: $filter);

        $rattachments = $this->repository->findBy($findByRecord);

        $roleValues = array_map(fn ($role) => $role->getValue(), $roles);

        return $rattachments
            ->filter(
                fn ($rattachment) => in_array($rattachment->target_type, $targetClasses, true)
                    && in_array($rattachment->role?->getValue(), $roleValues, true)
            )
            ->map(fn ($rattachment) => $rattachment->target);
    }
}
