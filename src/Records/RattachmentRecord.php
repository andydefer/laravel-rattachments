<?php

declare(strict_types=1);

namespace AndyDefer\LaravelRattachments\Records;

use AndyDefer\DomainStructures\Abstracts\AbstractRecord;
use AndyDefer\DomainStructures\Utils\StrictDataObject;
use AndyDefer\PhpVo\ValueObjects\DateTimeVO;

final class RattachmentRecord extends AbstractRecord
{
    public function __construct(
        public readonly ?int $id = null,
        public readonly ?string $rattachable_type = null,
        public readonly ?int $rattachable_id = null,
        public readonly ?string $target_type = null,
        public readonly ?int $target_id = null,
        public readonly ?string $role = null,
        public readonly ?StrictDataObject $metadata = null,
        public readonly ?DateTimeVO $created_at = null,
        public readonly ?DateTimeVO $updated_at = null,
    ) {}
}
