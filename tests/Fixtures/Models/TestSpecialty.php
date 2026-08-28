<?php

declare(strict_types=1);

namespace AndyDefer\LaravelRattachments\Tests\Fixtures\Models;

use AndyDefer\LaravelRattachments\Contracts\RattachmentConstraintsInterface;
use Illuminate\Database\Eloquent\Model;

final class TestSpecialty extends Model implements RattachmentConstraintsInterface
{
    protected $table = 'test_specialties';

    protected $fillable = [
        'name',
        'code',
    ];

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
