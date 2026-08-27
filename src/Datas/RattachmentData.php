<?php

declare(strict_types=1);

namespace AndyDefer\LaravelRattachments\Datas;

use AndyDefer\DomainStructures\Abstracts\AbstractData;
use AndyDefer\DomainStructures\Traits\Hydratable;
use AndyDefer\DomainStructures\Utils\StrictDataObject;
use AndyDefer\PhpVo\ValueObjects\DateTimeVO;

final class RattachmentData extends AbstractData
{
    use Hydratable;

    public function __construct(
        public readonly ?int $id,
        public readonly string $rattachable_type,
        public readonly int $rattachable_id,
        public readonly string $target_type,
        public readonly int $target_id,
        public readonly ?string $role,
        public readonly ?StrictDataObject $metadata,
        public readonly ?DateTimeVO $created_at,
        public readonly ?DateTimeVO $updated_at,
    ) {}
}
