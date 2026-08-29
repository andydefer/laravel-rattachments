<?php

declare(strict_types=1);

namespace AndyDefer\LaravelRattachments\Tests\Fixtures\Models;

use AndyDefer\LaravelRattachments\Contracts\RattachmentInterface;
use AndyDefer\LaravelRattachments\Tests\Fixtures\Enums\Role;
use AndyDefer\LaravelRattachments\Traits\HasRattachments;
use Illuminate\Database\Eloquent\Model;

final class TestCircularSpecialty extends Model implements RattachmentInterface
{
    use HasRattachments;

    protected $table = 'test_circular_specialties';

    protected $fillable = [
        'name',
        'code',
    ];

    public function allowedTargets(): array
    {
        return [
            // Circularité avec TestCircularUser avec PRIMARY
            TestCircularUser::class => [Role::PRIMARY],
            // Pas de circularité (rôles différents)
            TestCircularHospital::class => [Role::DOCTOR],
        ];
    }

    public function uniqueTargets(): array
    {
        // ✅ Pas de circularité unique
        return [];
    }

    public function disallowedTargets(): array
    {
        return [];
    }
}
