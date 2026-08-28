<?php

declare(strict_types=1);

namespace AndyDefer\LaravelRattachments\Contracts\Services;

interface ConstraintDiscoveryServiceInterface
{
    /**
     * Discover models implementing RattachmentInterface.
     *
     * @param  array<int, string>  $sources  Directories to scan
     * @return array<string, array{allowedTargets: array, uniqueTargets: array}>
     */
    public function discoverConstrainedModels(array $sources): array;
}
