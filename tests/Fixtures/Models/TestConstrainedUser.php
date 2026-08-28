<?php

declare(strict_types=1);

namespace AndyDefer\LaravelRattachments\Tests\Fixtures\Models;

use AndyDefer\LaravelRattachments\Contracts\RattachmentInterface;
use AndyDefer\LaravelRattachments\Tests\Fixtures\Enums\Role;
use AndyDefer\LaravelRattachments\Traits\HasRattachments;
use Illuminate\Database\Eloquent\Model;

final class TestConstrainedUser extends Model implements RattachmentInterface
{
    use HasRattachments;

    protected $table = 'test_constrained_users';

    protected $fillable = [
        'name',
        'email',
    ];

    public function allowedTargets(): array
    {
        return [
            TestPost::class => [Role::DOCTOR, Role::ADMIN],
            TestUser::class => [Role::DOCTOR, Role::ADMIN],
        ];
    }

    public function disallowedTargets(): array
    {
        return [];
    }

    public function uniqueTargets(): array
    {
        return [
            TestPost::class => [],
        ];
    }
}
