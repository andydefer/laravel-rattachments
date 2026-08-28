<?php

declare(strict_types=1);

namespace AndyDefer\LaravelRattachments\Tests\Fixtures\Models;

use AndyDefer\LaravelRattachments\Contracts\RattachmentInterface;
use AndyDefer\LaravelRattachments\Traits\HasRattachments;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TestPost extends Model implements RattachmentInterface
{
    use HasRattachments;

    protected $table = 'test_posts';

    protected $fillable = [
        'user_id',
        'title',
        'body',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(TestUser::class, 'user_id');
    }

    public function allowedTargets(): array
    {
        return [];
    }

    public function uniqueTargets(): array
    {
        return [];
    }

    public function disallowedTargets(): array
    {
        return [];
    }
}
