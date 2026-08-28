<?php

declare(strict_types=1);

namespace AndyDefer\LaravelRattachments\Tests\Fixtures\Models;

use AndyDefer\LaravelRattachments\Contracts\RattachmentInterface;
use AndyDefer\LaravelRattachments\Tests\Fixtures\Enums\Role;
use AndyDefer\LaravelRattachments\Traits\HasRattachments;
use Illuminate\Database\Eloquent\Model;

/**
 * Test model with specific unique constraints per role.
 *
 * - A TestSpecializedUser can have only ONE Hospital with CHIEF role
 * - A TestSpecializedUser can have only ONE Specialty with PRIMARY role
 * - A TestSpecializedUser can have only ONE User with BEST_FRIEND role
 * - A TestSpecializedUser can have many Hospital with DOCTOR role
 * - A TestSpecializedUser can have many Specialty with SECONDARY role
 * - A TestSpecializedUser can have many User with FRIEND role
 */
final class TestSpecializedUser extends Model implements RattachmentInterface
{
    use HasRattachments;

    protected $table = 'test_specialized_users';

    protected $fillable = [
        'name',
        'email',
    ];

    public function allowedTargets(): array
    {
        return [
            TestPost::class => [Role::DOCTOR, Role::ADMIN, Role::STAFF],
            TestUser::class => [Role::ADMIN, Role::STAFF, Role::FRIEND, Role::BEST_FRIEND],
            TestHospital::class => [Role::DOCTOR, Role::CHIEF, Role::ADMIN],
            TestSpecialty::class => [Role::PRIMARY, Role::SECONDARY, Role::SPECIALIST],
        ];
    }

    public function disallowedTargets(): array
    {
        return [];
    }

    public function uniqueTargets(): array
    {
        return [
            // Un seul Hospital avec le rôle CHIEF
            TestHospital::class => [Role::CHIEF],

            // Un seul Specialty avec le rôle PRIMARY
            TestSpecialty::class => [Role::PRIMARY],

            // Un seul User avec le rôle BEST_FRIEND
            TestUser::class => [Role::BEST_FRIEND],
        ];
    }
}
