<?php

declare(strict_types=1);

namespace AndyDefer\LaravelRattachments\Tests\Fixtures\Models;

use AndyDefer\LaravelRattachments\Contracts\RattachmentInterface;
use AndyDefer\LaravelRattachments\Tests\Fixtures\Enums\Role;
use AndyDefer\LaravelRattachments\Traits\HasRattachments;
use Illuminate\Database\Eloquent\Model;

/**
 * Test model that creates circular relationships for testing circularity detection.
 */
final class TestCircularHospital extends Model implements RattachmentInterface
{
    use HasRattachments;

    protected $table = 'test_circular_hospitals';

    protected $fillable = [
        'name',
        'address',
    ];

    public function allowedTargets(): array
    {
        return [
            // Circularité avec TestCircularUser avec le même rôle CHIEF
            TestCircularUser::class => [Role::CHIEF, Role::ADMIN],
            // Pas de circularité (rôles différents)
            TestCircularSpecialty::class => [Role::SPECIALIST],
        ];
    }

    public function uniqueTargets(): array
    {
        return [
            // Circularité unique avec TestCircularUser avec le même rôle CHIEF
            TestCircularUser::class => [Role::CHIEF],
            // Pas de circularité (rôle différent)
            TestCircularSpecialty::class => [Role::SPECIALIST],
        ];
    }

    public function disallowedTargets(): array
    {
        return [];
    }
}
