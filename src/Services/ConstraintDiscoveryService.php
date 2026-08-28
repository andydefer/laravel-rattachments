<?php

declare(strict_types=1);

namespace AndyDefer\LaravelRattachments\Services;

use AndyDefer\Directive\Helpers\Paths;
use AndyDefer\LaravelRattachments\Contracts\RattachmentInterface;
use AndyDefer\LaravelRattachments\Contracts\Services\ConstraintDiscoveryServiceInterface;
use AndyDefer\LaravelRattachments\Services\Visitors\ConstraintModelVisitor;
use AndyDefer\PhpServices\Contracts\FileSystemInterface;
use PhpParser\Error;
use PhpParser\NodeTraverser;
use PhpParser\Parser;

final class ConstraintDiscoveryService implements ConstraintDiscoveryServiceInterface
{
    private const MAX_SCAN_DEPTH = 4;

    public function __construct(
        private readonly FileSystemInterface $fileSystem,
        private readonly Parser $parser,
    ) {}

    public function discoverConstrainedModels(array $sources): array
    {
        $models = [];
        $result = [];

        foreach ($sources as $source) {
            $directory = $this->resolvePath($source);

            if (! $this->fileSystem->isDirectory($directory)) {
                continue;
            }

            $models = array_merge($models, $this->scanDirectory($directory));
        }

        $models = array_unique($models);

        foreach ($models as $modelClass) {
            if (! class_exists($modelClass)) {
                continue;
            }

            $reflection = new \ReflectionClass($modelClass);
            if (! $reflection->implementsInterface(RattachmentInterface::class)) {
                continue;
            }

            try {
                /** @var RattachmentInterface $instance */
                $instance = new $modelClass;
                $allowedTargets = $instance->allowedTargets();
                $uniqueTargets = method_exists($instance, 'uniqueTargets')
                    ? $instance->uniqueTargets()
                    : [];

                $result[$modelClass] = [
                    'allowedTargets' => $allowedTargets,
                    'uniqueTargets' => $uniqueTargets,
                ];
            } catch (\Exception $e) {
                continue;
            }
        }

        return $result;
    }

    private function resolvePath(string $source): string
    {
        $projectRoot = Paths::projectRoot();

        if (str_starts_with($source, '%')) {
            return $this->resolveRelativePath($source, $projectRoot);
        }

        $path = str_replace('.', DIRECTORY_SEPARATOR, $source);

        return $projectRoot.DIRECTORY_SEPARATOR.$path;
    }

    private function resolveRelativePath(string $source, string $projectRoot): string
    {
        $count = 0;
        $temp = $source;

        while (str_starts_with($temp, '%')) {
            $count++;
            $temp = substr($temp, 1);
        }

        $relativePath = str_replace('.', DIRECTORY_SEPARATOR, $temp);
        $prefix = str_repeat('..'.DIRECTORY_SEPARATOR, $count);

        return $projectRoot.DIRECTORY_SEPARATOR.$prefix.$relativePath;
    }

    private function scanDirectory(string $directory, int $maxDepth = self::MAX_SCAN_DEPTH): array
    {
        $models = [];

        if (! $this->fileSystem->isDirectory($directory)) {
            return $models;
        }

        $this->scanDirectoryRecursive($directory, $models, 0, $maxDepth);

        return $models;
    }

    private function scanDirectoryRecursive(string $directory, array &$models, int $currentDepth, int $maxDepth): void
    {
        if ($currentDepth > $maxDepth) {
            return;
        }

        $files = $this->fileSystem->glob($directory.'/*.php');

        foreach ($files as $file) {
            if (! $this->fileSystem->isFile($file)) {
                continue;
            }

            try {
                $content = $this->fileSystem->get($file);
                $found = $this->extractModelsFromFile($content);
                $models = array_merge($models, $found);
            } catch (\Throwable $e) {
                continue;
            }
        }

        $subDirectories = $this->fileSystem->glob($directory.'/*', GLOB_ONLYDIR);

        foreach ($subDirectories as $subDirectory) {
            $this->scanDirectoryRecursive($subDirectory, $models, $currentDepth + 1, $maxDepth);
        }
    }

    private function extractModelsFromFile(string $content): array
    {
        try {
            $ast = $this->parser->parse($content);

            if ($ast === null) {
                return [];
            }

            $visitor = new ConstraintModelVisitor;
            $traverser = new NodeTraverser;
            $traverser->addVisitor($visitor);
            $traverser->traverse($ast);

            return $visitor->getModels();
        } catch (Error $e) {
            return [];
        }
    }
}
