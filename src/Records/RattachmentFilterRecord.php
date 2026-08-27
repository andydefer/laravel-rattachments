<?php

declare(strict_types=1);

namespace AndyDefer\LaravelRattachments\Records;

use AndyDefer\DomainStructures\Abstracts\AbstractRecord;

final class RattachmentFilterRecord extends AbstractRecord
{
    public function __construct(
        public readonly ?string $rattachable_type = null,
        public readonly ?int $rattachable_id = null,
        public readonly ?string $target_type = null,
        public readonly ?int $target_id = null,
        public readonly ?string $role = null,
    ) {}
}
