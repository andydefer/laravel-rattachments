<?php

declare(strict_types=1);

namespace AndyDefer\LaravelRattachments\Contracts;

use AndyDefer\Repository\Contracts\EnumerableInterface;
use Illuminate\Database\Eloquent\Model;

/**
 * Interface for models that define attachment constraints.
 */
interface RattachmentConstraintsInterface
{
    /**
     * Define allowed targets for this model.
     *
     * @return array<string, array<int, EnumerableInterface>>
     *
     * @example
     * return [
     *     Hospital::class => [Role::DOCTOR, Role::STAFF],
     *     Pharmacy::class => [Role::PHARMACIST],
     * ];
     */
    public function allowedTargets(): array;
}
