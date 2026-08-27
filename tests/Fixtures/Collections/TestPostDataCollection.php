<?php

declare(strict_types=1);

namespace AndyDefer\LaravelRattachments\Tests\Fixtures\Collections;

use AndyDefer\DomainStructures\Abstracts\AbstractTypedCollection;
use AndyDefer\LaravelRattachments\Tests\Fixtures\Data\TestPostData;

final class TestPostDataCollection extends AbstractTypedCollection
{
    public function __construct()
    {
        parent::__construct(TestPostData::class);
    }
}
