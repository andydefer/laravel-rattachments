<?php

declare(strict_types=1);

namespace AndyDefer\LaravelRattachments\Tests\Fixtures\Models;

use AndyDefer\LaravelRattachments\Contracts\RattachmentInterface;
use AndyDefer\LaravelRattachments\Tests\Fixtures\Enums\Role;
use AndyDefer\LaravelRattachments\Traits\HasRattachments;
use Illuminate\Database\Eloquent\Model;

/**
 * Test model that creates circular relationships for testing circularity detection.
 *
 * This model is designed to test the circularity detection in both allowedTargets and uniqueTargets.
 */
final class TestCircularUser extends Model implements RattachmentInterface
{
    use HasRattachments;

    protected $table = 'test_circular_users';

    protected $fillable = [
        'name',
        'email',
    ];

    public function allowedTargets(): array
    {
        return [
            // Circularité avec TestCircularHospital avec le même rôle CHIEF
            TestCircularHospital::class => [Role::CHIEF, Role::DOCTOR],
            // Circularité avec TestCircularSpecialty avec le même rôle PRIMARY
            TestCircularSpecialty::class => [Role::PRIMARY],
        ];
    }

    public function uniqueTargets(): array
    {
        return [
            // Circularité unique avec TestCircularHospital avec le même rôle CHIEF
            TestCircularHospital::class => [Role::CHIEF],
            // Circularité unique avec TestCircularUser (self-relation) - NE DOIT PAS ÊTRE BLOQUÉ
            TestCircularUser::class => [Role::PARTNER],
        ];
    }

    public function disallowedTargets(): array
    {
        return [];
    }
}
