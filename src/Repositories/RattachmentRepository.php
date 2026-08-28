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

        $query->when($filters->rattachable_type, fn ($q, $value) => $q->where('rattachable_type', $value)
        );

        $query->when($filters->rattachable_id, fn ($q, $value) => $q->where('rattachable_id', $value)
        );

        $query->when($filters->target_type, fn ($q, $value) => $q->where('target_type', $value)
        );

        $query->when($filters->target_id, fn ($q, $value) => $q->where('target_id', $value)
        );

        $query->when($filters->role, fn ($q, $value) => $q->where('role', $value)
        );
    }
}
