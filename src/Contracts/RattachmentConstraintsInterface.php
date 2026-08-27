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

    /**
     * Define unique targets for this model.
     * A model can only have ONE attachment to each target type listed here.
     *
     * @return array<int, string> Array of FQCNs
     *
     * @example
     * return [
     *     User::class,  // A Hospital can have only ONE User (director)
     *     Pharmacy::class, // A Hospital can have only ONE Pharmacy (main supplier)
     * ];
     */
    public function uniqueTargets(): array;
}
