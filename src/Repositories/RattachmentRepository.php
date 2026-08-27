<?php

declare(strict_types=1);

namespace AndyDefer\LaravelRattachments\Repositories;

use AndyDefer\DomainStructures\Abstracts\AbstractRecord;
use AndyDefer\LaravelRattachments\Contracts\Repositories\RattachmentRepositoryInterface;
use AndyDefer\LaravelRattachments\Models\Rattachment;
use AndyDefer\LaravelRattachments\Records\RattachmentFilterRecord;
use AndyDefer\LaravelRattachments\Records\RattachmentRecord;
use AndyDefer\Repository\AbstractRepository;
use Illuminate\Database\Eloquent\Builder;

final class RattachmentRepository extends AbstractRepository implements RattachmentRepositoryInterface
{
    public function __construct()
    {
        parent::__construct(
            modelClass: Rattachment::class,
            recordClass: RattachmentRecord::class,
        );
    }

    protected function applyFilters(Builder $query, AbstractRecord $filters): void
    {
        if (! $filters instanceof RattachmentFilterRecord) {
            return;
        }

        if ($filters->rattachable_type !== null) {
            $query->where('rattachable_type', $filters->rattachable_type);
        }

        if ($filters->rattachable_id !== null) {
            $query->where('rattachable_id', $filters->rattachable_id);
        }

        if ($filters->target_type !== null) {
            $query->where('target_type', $filters->target_type);
        }

        if ($filters->target_id !== null) {
            $query->where('target_id', $filters->target_id);
        }

        if ($filters->role !== null) {
            $query->where('role', $filters->role);
        }
    }
}
