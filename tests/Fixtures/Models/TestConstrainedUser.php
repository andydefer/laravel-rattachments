<?php

declare(strict_types=1);

namespace AndyDefer\LaravelRattachments\Tests\Fixtures\Models;

use AndyDefer\LaravelRattachments\Contracts\RattachmentConstraintsInterface;
use AndyDefer\LaravelRattachments\Enums\Role;
use Illuminate\Database\Eloquent\Model;

final class TestConstrainedUser extends Model implements RattachmentConstraintsInterface
{
    protected $table = 'test_constrained_users';

    protected $fillable = [
        'name',
        'email',
    ];

    public function allowedTargets(): array
    {
        return [
            TestPost::class => [Role::DOCTOR, Role::ADMIN],
        ];
    }
}
