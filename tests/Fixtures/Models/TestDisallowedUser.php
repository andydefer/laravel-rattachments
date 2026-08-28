<?php

declare(strict_types=1);

namespace AndyDefer\LaravelRattachments\Tests\Fixtures\Models;

use AndyDefer\LaravelRattachments\Contracts\RattachmentConstraintsInterface;
use AndyDefer\LaravelRattachments\Enums\Role;
use Illuminate\Database\Eloquent\Model;

final class TestDisallowedUser extends Model implements RattachmentConstraintsInterface
{
    protected $table = 'test_disallowed_users';

    protected $fillable = [
        'name',
        'email',
    ];

    public function allowedTargets(): array
    {
        return [
            TestPost::class => [Role::DOCTOR, Role::ADMIN, Role::STAFF],
            TestUser::class => [Role::DOCTOR, Role::ADMIN],
        ];
    }

    public function uniqueTargets(): array
    {
        return [];
    }

    public function disallowedTargets(): array
    {
        return [
            // ❌ Bloque TOUT rattachement à TestCheckPoint
            TestCheckPoint::class => [],

            // ❌ Bloque uniquement le rôle STAFF pour TestPost
            TestPost::class => [Role::STAFF],

            // ❌ Bloque uniquement le rôle ADMIN pour TestUser
            TestUser::class => [Role::ADMIN],
        ];
    }
}
