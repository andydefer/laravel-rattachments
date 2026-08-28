<?php

declare(strict_types=1);

namespace AndyDefer\LaravelRattachments\Enums;

/**
 * Position of the model in the attachment operation.
 *
 * Indicates whether the model is the source (rattachable) or the destination (target)
 * in an attachment relationship.
 */
enum HookPosition: string
{
    /**
     * The model is the source that attaches another model.
     */
    case RATTACHABLE = 'rattachable';

    /**
     * The model is the destination that is being attached.
     */
    case TARGET = 'target';
}
