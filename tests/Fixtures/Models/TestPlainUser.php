<?php

declare(strict_types=1);

namespace AndyDefer\LaravelRattachments\Tests\Fixtures\Models;

use Illuminate\Database\Eloquent\Model;

final class TestPlainUser extends Model
{
    protected $table = 'test_plain_users';

    protected $fillable = [
        'name',
        'email',
    ];
}
