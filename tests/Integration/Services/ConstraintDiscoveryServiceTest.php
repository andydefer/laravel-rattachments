<?php

declare(strict_types=1);

namespace AndyDefer\LaravelRattachments\Tests\Integration\Services;

use AndyDefer\LaravelRattachments\Contracts\RattachmentInterface;
use AndyDefer\LaravelRattachments\Contracts\Services\ConstraintDiscoveryServiceInterface;
use AndyDefer\LaravelRattachments\Services\ConstraintDiscoveryService;
use AndyDefer\LaravelRattachments\Tests\Fixtures\Models\TestConstrainedUser;
use AndyDefer\LaravelRattachments\Tests\Fixtures\Models\TestPlainUser;
use AndyDefer\LaravelRattachments\Tests\Fixtures\Models\TestPost;
use AndyDefer\LaravelRattachments\Tests\IntegrationTestCase;
use AndyDefer\PhpServices\Contracts\FileSystemInterface;
use PhpParser\ParserFactory;

final class ConstraintDiscoveryServiceTest extends IntegrationTestCase
{
    private ConstraintDiscoveryServiceInterface $discoveryService;

    protected function setUp(): void
    {
        parent::setUp();

        $fileSystem = $this->app->make(FileSystemInterface::class);
        $parser = (new ParserFactory)->createForNewestSupportedVersion();

        $this->discoveryService = new ConstraintDiscoveryService($fileSystem, $parser);
    }

    public function test_discover_constrained_models_from_single_source(): void
    {
        $source = 'tests/Fixtures/Models';

        $models = $this->discoveryService->discoverConstrainedModels([$source]);

        $this->assertNotEmpty($models);
        $this->assertArrayHasKey(TestConstrainedUser::class, $models);
    }

    public function test_discover_constrained_models_returns_constraints(): void
    {
        $source = 'tests/Fixtures/Models';

        $models = $this->discoveryService->discoverConstrainedModels([$source]);

        $this->assertArrayHasKey(TestConstrainedUser::class, $models);

        $constraints = $models[TestConstrainedUser::class];
        $this->assertArrayHasKey('allowedTargets', $constraints);
        $this->assertArrayHasKey('uniqueTargets', $constraints);

        $this->assertArrayHasKey(TestPost::class, $constraints['allowedTargets']);

        $this->assertArrayHasKey(TestPost::class, $constraints['uniqueTargets']);
        $this->assertEmpty($constraints['uniqueTargets'][TestPost::class]);
    }

    public function test_discover_constrained_models_ignores_non_constrained_models(): void
    {
        $source = 'tests/Fixtures/Models';

        $models = $this->discoveryService->discoverConstrainedModels([$source]);

        $this->assertArrayNotHasKey(TestPlainUser::class, $models);
    }

    public function test_discover_constrained_models_from_multiple_sources(): void
    {
        $sources = [
            'tests/Fixtures/Models',
        ];

        $models = $this->discoveryService->discoverConstrainedModels($sources);

        $this->assertNotEmpty($models);
        $this->assertArrayHasKey(TestConstrainedUser::class, $models);
    }

    public function test_discover_constrained_models_returns_unique_results(): void
    {
        $sources = [
            'tests/Fixtures/Models',
            'tests/Fixtures/Models',
        ];

        $models = $this->discoveryService->discoverConstrainedModels($sources);

        $this->assertCount(11, $models);
        $this->assertArrayHasKey(TestConstrainedUser::class, $models);
    }

    public function test_discover_constrained_models_with_invalid_source_returns_empty(): void
    {
        $sources = ['invalid/path'];

        $models = $this->discoveryService->discoverConstrainedModels($sources);

        $this->assertEmpty($models);
    }

    public function test_discover_constrained_models_with_source_using_dot_notation(): void
    {
        $source = 'tests.Fixtures.Models';

        $models = $this->discoveryService->discoverConstrainedModels([$source]);

        $this->assertNotEmpty($models);
        $this->assertArrayHasKey(TestConstrainedUser::class, $models);
    }

    public function test_discover_constrained_models_with_deep_directory_structure(): void
    {
        $source = 'tests/Fixtures';

        $models = $this->discoveryService->discoverConstrainedModels([$source]);

        $this->assertNotEmpty($models);
        $this->assertArrayHasKey(TestConstrainedUser::class, $models);
    }

    public function test_discover_constrained_models_handles_models_without_interface(): void
    {
        $source = 'tests/Fixtures/Models';

        $models = $this->discoveryService->discoverConstrainedModels([$source]);

        $constrainedClasses = array_keys($models);

        foreach ($constrainedClasses as $class) {
            $reflection = new \ReflectionClass($class);
            $this->assertTrue(
                $reflection->implementsInterface(RattachmentInterface::class)
            );
        }
    }
}
