<?php

declare(strict_types=1);

namespace AndyDefer\LaravelRattachments\Tests\Fixtures\CodeSnippets;

final class ConstraintModelSnippets
{
    public const CONSTRAINED_MODEL = <<<'PHP'
<?php

namespace App\Models;

use AndyDefer\LaravelRattachments\Contracts\RattachmentConstraintsInterface;
use AndyDefer\LaravelRattachments\Tests\Fixtures\Enums\Role;
use Illuminate\Database\Eloquent\Model;

final class ConstrainedUser extends Model implements RattachmentConstraintsInterface
{
    public function allowedTargets(): array
    {
        return [
            Post::class => [Role::DOCTOR, Role::ADMIN],
        ];
    }

    public function uniqueTargets(): array
    {
        return [Post::class];
    }
}
PHP;

    public const UNCONSTRAINED_MODEL = <<<'PHP'
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

final class UnconstrainedUser extends Model
{
    protected $fillable = ['name', 'email'];
}
PHP;

    public const CONSTRAINED_MODEL_WITH_ALIAS = <<<'PHP'
<?php

namespace App\Models;

use AndyDefer\LaravelRattachments\Contracts\RattachmentConstraintsInterface as Constraints;
use AndyDefer\LaravelRattachments\Tests\Fixtures\Enums\Role;
use Illuminate\Database\Eloquent\Model;

final class ConstrainedUser extends Model implements Constraints
{
    public function allowedTargets(): array
    {
        return [
            Post::class => [Role::DOCTOR, Role::ADMIN],
        ];
    }
}
PHP;

    public const ABSTRACT_CONSTRAINED_MODEL = <<<'PHP'
<?php

namespace App\Models;

use AndyDefer\LaravelRattachments\Contracts\RattachmentConstraintsInterface;
use AndyDefer\LaravelRattachments\Tests\Fixtures\Enums\Role;
use Illuminate\Database\Eloquent\Model;

abstract class AbstractConstrainedModel extends Model implements RattachmentConstraintsInterface
{
    public function allowedTargets(): array
    {
        return [];
    }
}
PHP;

    public const MULTIPLE_CONSTRAINED_MODELS = <<<'PHP'
<?php

namespace App\Models;

use AndyDefer\LaravelRattachments\Contracts\RattachmentConstraintsInterface;
use AndyDefer\LaravelRattachments\Tests\Fixtures\Enums\Role;
use Illuminate\Database\Eloquent\Model;

final class ConstrainedUser extends Model implements RattachmentConstraintsInterface
{
    public function allowedTargets(): array
    {
        return [];
    }
}

final class ConstrainedPost extends Model implements RattachmentConstraintsInterface
{
    public function allowedTargets(): array
    {
        return [];
    }
}
PHP;

    public const NESTED_NAMESPACE_CONSTRAINED_MODEL = <<<'PHP'
<?php

namespace App\Models\Users;

use AndyDefer\LaravelRattachments\Contracts\RattachmentConstraintsInterface;
use AndyDefer\LaravelRattachments\Tests\Fixtures\Enums\Role;
use Illuminate\Database\Eloquent\Model;

final class ConstrainedUser extends Model implements RattachmentConstraintsInterface
{
    public function allowedTargets(): array
    {
        return [];
    }
}
PHP;
}
