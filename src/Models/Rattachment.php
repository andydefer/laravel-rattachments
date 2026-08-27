<?php

declare(strict_types=1);

namespace AndyDefer\LaravelRattachments\Models;

use AndyDefer\DomainStructures\Utils\StrictDataObject;
use AndyDefer\Repository\Casts\EnumCast;
use AndyDefer\Repository\Proxies\AttributeProxy;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

final class Rattachment extends Model
{
    protected $table = 'rattachments';

    protected $fillable = [
        'rattachable_type',
        'rattachable_id',
        'target_type',
        'target_id',
        'role',
        'metadata',
    ];

    protected $casts = [
        'role' => EnumCast::class,
        'metadata' => 'array',
    ];

    public function rattachable(): MorphTo
    {
        return $this->morphTo();
    }

    public function target(): MorphTo
    {
        return $this->morphTo();
    }

    protected function metadata(): Attribute
    {
        return AttributeProxy::nullable(
            StrictDataObject::class,
            column: 'metadata'
        );
    }
}
