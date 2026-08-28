<?php

declare(strict_types=1);

namespace AndyDefer\LaravelRattachments\Tests\Fixtures\Models;

use AndyDefer\LaravelRattachments\Contracts\RattachmentConstraintsInterface;
use AndyDefer\LaravelRattachments\Tests\Fixtures\Enums\Role;
use AndyDefer\LaravelRattachments\Tests\Fixtures\Enums\TestUserRole;
use AndyDefer\LaravelRattachments\Tests\Fixtures\Enums\TestUserStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TestUser extends Model implements RattachmentConstraintsInterface
{
    protected $table = 'test_users';

    protected $fillable = [
        'name',
        'email',
        'status',
        'role',
        'age',
        'metadata',
    ];

    protected $casts = [
        'status' => TestUserStatus::class,
        'role' => TestUserRole::class,
        'metadata' => 'array',
    ];

    public function posts(): HasMany
    {
        return $this->hasMany(TestPost::class, 'user_id');
    }

    public function allowedTargets(): array
    {
        return [
            TestPost::class => [Role::DOCTOR, Role::ADMIN],
            TestUser::class => [Role::ADMIN, Role::STAFF],
        ];
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
