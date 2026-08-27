<?php

declare(strict_types=1);

namespace AndyDefer\LaravelRattachments\Contracts\Repositories;

use AndyDefer\LaravelRattachments\Models\Rattachment;
use AndyDefer\LaravelRattachments\Records\RattachmentRecord;
use AndyDefer\Repository\AbstractRepositoryInterface;

/**
 * Interface for Rattachment repository.
 *
 * @extends AbstractRepositoryInterface<Rattachment, RattachmentRecord>
 */
interface RattachmentRepositoryInterface extends AbstractRepositoryInterface
{
    // All methods are inherited from AbstractRepositoryInterface
}
