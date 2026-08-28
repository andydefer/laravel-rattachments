<?php

declare(strict_types=1);

namespace AndyDefer\LaravelRattachments\Tests\Integration\Directives;

use AndyDefer\Directive\Enums\ExitCode;
use AndyDefer\Directive\Services\DirectiveTestingService;
use AndyDefer\LaravelRattachments\Directives\RattachmentsInspectDirective;
use AndyDefer\LaravelRattachments\Enums\Role;
use AndyDefer\LaravelRattachments\Services\RattachmentService;
use AndyDefer\LaravelRattachments\Tests\Fixtures\Models\TestCheckPoint;
use AndyDefer\LaravelRattachments\Tests\Fixtures\Models\TestConstrainedUser;
use AndyDefer\LaravelRattachments\Tests\Fixtures\Models\TestDisallowedUser;
use AndyDefer\LaravelRattachments\Tests\Fixtures\Models\TestPost;
use AndyDefer\LaravelRattachments\Tests\Fixtures\Models\TestUser;
use AndyDefer\LaravelRattachments\Tests\IntegrationTestCase;
use AndyDefer\Repository\Configs\RepositoryConfig;
use AndyDefer\Repository\Contracts\Configs\RepositoryConfigInterface;
use Illuminate\Support\Facades\Schema;

final class RattachmentsInspectDirectiveTest extends IntegrationTestCase
{
    private DirectiveTestingService $service;

    private RattachmentService $rattachmentService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->configureEnumCasts();

        $this->service = new DirectiveTestingService(
            application: $this->app,
            sourcePaths: []
        );

        $this->service->getKernel()->addDirective(RattachmentsInspectDirective::class);

        $this->rattachmentService = $this->app->make(RattachmentService::class);

        $this->createTestData();
        $this->createDisallowedTestData();
    }

    protected function tearDown(): void
    {
        $this->service->destroy();
        parent::tearDown();
    }

    private function configureEnumCasts(): void
    {
        $this->app['config']->set('repository.enum_casts', [
            'rattachments' => [
                'role' => Role::class,
            ],
        ]);

        $this->app->singleton(RepositoryConfig::class, function ($app) {
            return new RepositoryConfig($app['config']);
        });

        $this->app->bind(RepositoryConfigInterface::class, RepositoryConfig::class);
    }

    private function createTestData(): void
    {
        $user = TestUser::create([
            'name' => 'John Doe',
            'email' => 'john@example.com',
        ]);

        $post = TestPost::create([
            'user_id' => $user->id,
            'title' => 'Test Post',
            'body' => 'Test content',
        ]);

        $constrainedUser = TestConstrainedUser::create([
            'name' => 'Constrained User',
            'email' => 'constrained@example.com',
        ]);

        $this->rattachmentService->attach($user, $post, Role::DOCTOR);
        $this->rattachmentService->attach($constrainedUser, $post, Role::DOCTOR);
    }

    private function createDisallowedTestData(): void
    {
        $disallowedUser = TestDisallowedUser::create([
            'name' => 'Disallowed User',
            'email' => 'disallowed@example.com',
        ]);

        TestCheckPoint::create([
            'name' => 'Test Checkpoint',
            'location' => 'Test Location',
            'is_active' => true,
        ]);
    }

    public function test_inspect_shows_constraints_for_specific_models(): void
    {
        $response = $this->service->run(
            'rattachments:inspect [AndyDefer.LaravelRattachments.Tests.Fixtures.Models.TestConstrainedUser] --constraints'
        );

        $this->assertSame(ExitCode::SUCCESS, $response->exit_code);
        $this->assertStringContainsString('🔒 CONSTRAINTS', $response->output);
        $this->assertStringContainsString('TestConstrainedUser', $response->output);
        $this->assertStringContainsString('TestPost', $response->output);
    }

    public function test_inspect_shows_connections_for_specific_models(): void
    {
        $response = $this->service->run(
            'rattachments:inspect [AndyDefer.LaravelRattachments.Tests.Fixtures.Models.TestConstrainedUser] --connections'
        );

        $this->assertSame(ExitCode::SUCCESS, $response->exit_code);
        $this->assertStringContainsString('🔗 EXISTING CONNECTIONS', $response->output);
        $this->assertStringContainsString('TestConstrainedUser', $response->output);
        $this->assertStringContainsString('TestPost', $response->output);
    }

    public function test_inspect_shows_both_constraints_and_connections_by_default(): void
    {
        $response = $this->service->run(
            'rattachments:inspect [AndyDefer.LaravelRattachments.Tests.Fixtures.Models.TestConstrainedUser]'
        );

        $this->assertSame(ExitCode::SUCCESS, $response->exit_code);
        $this->assertStringContainsString('🔒 CONSTRAINTS', $response->output);
        $this->assertStringContainsString('🔗 EXISTING CONNECTIONS', $response->output);
    }

    public function test_inspect_without_constraints_hides_constraints(): void
    {
        $response = $this->service->run(
            'rattachments:inspect [AndyDefer.LaravelRattachments.Tests.Fixtures.Models.TestConstrainedUser] --connections'
        );

        $this->assertSame(ExitCode::SUCCESS, $response->exit_code);
        $this->assertStringNotContainsString('🔒 CONSTRAINTS', $response->output);
        $this->assertStringContainsString('🔗 EXISTING CONNECTIONS', $response->output);
    }

    public function test_inspect_without_connections_hides_connections(): void
    {
        $response = $this->service->run(
            'rattachments:inspect [AndyDefer.LaravelRattachments.Tests.Fixtures.Models.TestConstrainedUser] --constraints'
        );

        $this->assertSame(ExitCode::SUCCESS, $response->exit_code);
        $this->assertStringContainsString('🔒 CONSTRAINTS', $response->output);
        $this->assertStringNotContainsString('🔗 EXISTING CONNECTIONS', $response->output);
    }

    public function test_inspect_with_multiple_models(): void
    {
        $response = $this->service->run(
            'rattachments:inspect [AndyDefer.LaravelRattachments.Tests.Fixtures.Models.TestUser, AndyDefer.LaravelRattachments.Tests.Fixtures.Models.TestConstrainedUser] --constraints'
        );

        $this->assertSame(ExitCode::SUCCESS, $response->exit_code);
        $this->assertStringContainsString('TestConstrainedUser', $response->output);
    }

    public function test_inspect_with_model_not_implementing_interface(): void
    {
        $response = $this->service->run(
            'rattachments:inspect [AndyDefer.LaravelRattachments.Tests.Fixtures.Models.TestUser] --constraints'
        );

        $this->assertSame(ExitCode::SUCCESS, $response->exit_code);
        $this->assertStringContainsString('does not implement RattachmentConstraintsInterface', $response->output);
    }

    public function test_inspect_with_invalid_model_class(): void
    {
        $response = $this->service->run(
            'rattachments:inspect [Invalid.Models.NonExistent] --constraints'
        );

        $this->assertSame(ExitCode::SUCCESS, $response->exit_code);
        $this->assertStringContainsString('Class not found', $response->output);
    }

    public function test_inspect_with_no_models_discover_automatically(): void
    {
        $response = $this->service->run('rattachments:inspect [] --constraints');

        $this->assertSame(ExitCode::SUCCESS, $response->exit_code);
        $this->assertStringContainsString('No models specified. Discovering models from sources...', $response->output);
        $this->assertStringContainsString('No sources specified. Using default: app.Models', $response->output);
        $this->assertStringContainsString('Scanning sources: app.Models', $response->output);
        $this->assertStringContainsString('No constrained models found.', $response->output);
    }

    public function test_inspect_with_sources_discover_automatically(): void
    {
        $response = $this->service->run(
            'rattachments:inspect [] [tests.Fixtures.Models] --constraints'
        );

        $this->assertSame(ExitCode::SUCCESS, $response->exit_code);
        $this->assertStringContainsString('No models specified. Discovering models from sources...', $response->output);
        $this->assertStringContainsString('Scanning sources: tests.Fixtures.Models', $response->output);
        $this->assertStringContainsString('TestConstrainedUser', $response->output);
    }

    public function test_inspect_with_multiple_sources(): void
    {
        $response = $this->service->run(
            'rattachments:inspect [] [app.Models, tests.Fixtures.Models] --constraints'
        );

        $this->assertSame(ExitCode::SUCCESS, $response->exit_code);
        $this->assertStringContainsString('No models specified. Discovering models from sources...', $response->output);
        $this->assertStringContainsString('Scanning sources: app.Models, tests.Fixtures.Models', $response->output);
        $this->assertStringContainsString('TestConstrainedUser', $response->output);
    }

    public function test_inspect_with_alias_works(): void
    {
        $response = $this->service->run(
            'ri [AndyDefer.LaravelRattachments.Tests.Fixtures.Models.TestConstrainedUser] --constraints'
        );

        $this->assertSame(ExitCode::SUCCESS, $response->exit_code);
        $this->assertStringContainsString('🔒 CONSTRAINTS', $response->output);
    }

    public function test_inspect_with_list_alias_works(): void
    {
        $response = $this->service->run(
            'rattachments:list [AndyDefer.LaravelRattachments.Tests.Fixtures.Models.TestConstrainedUser] --constraints'
        );

        $this->assertSame(ExitCode::SUCCESS, $response->exit_code);
        $this->assertStringContainsString('🔒 CONSTRAINTS', $response->output);
    }

    public function test_inspect_shows_roles_by_connection(): void
    {
        $response = $this->service->run(
            'rattachments:inspect [AndyDefer.LaravelRattachments.Tests.Fixtures.Models.TestConstrainedUser] --connections'
        );

        $this->assertSame(ExitCode::SUCCESS, $response->exit_code);
        $this->assertStringContainsString('Roles by connection', $response->output);
        $this->assertStringContainsString('doctor', $response->output);
    }

    public function test_inspect_shows_missing_connections_suggestions(): void
    {
        $response = $this->service->run(
            'rattachments:inspect [AndyDefer.LaravelRattachments.Tests.Fixtures.Models.TestConstrainedUser] --connections'
        );

        $this->assertSame(ExitCode::SUCCESS, $response->exit_code);
        $this->assertStringContainsString('Possible missing connections', $response->output);
        $this->assertStringContainsString('TestConstrainedUser → TestUser', $response->output);
    }

    public function test_inspect_handles_missing_table_gracefully(): void
    {
        Schema::dropIfExists('rattachments');

        $response = $this->service->run(
            'rattachments:inspect [AndyDefer.LaravelRattachments.Tests.Fixtures.Models.TestConstrainedUser] --connections'
        );

        $this->assertSame(ExitCode::SUCCESS, $response->exit_code);
        $this->assertStringContainsString('Table "rattachments" does not exist', $response->output);
    }

    public function test_inspect_shows_allowed_targets_with_roles(): void
    {
        $response = $this->service->run(
            'rattachments:inspect [AndyDefer.LaravelRattachments.Tests.Fixtures.Models.TestConstrainedUser] --constraints'
        );

        $this->assertSame(ExitCode::SUCCESS, $response->exit_code);
        $this->assertStringContainsString('Allowed targets', $response->output);
        $this->assertStringContainsString('TestPost', $response->output);
        $this->assertStringContainsString('doctor', $response->output);
        $this->assertStringContainsString('admin', $response->output);
    }

    public function test_inspect_shows_unique_targets(): void
    {
        $response = $this->service->run(
            'rattachments:inspect [AndyDefer.LaravelRattachments.Tests.Fixtures.Models.TestConstrainedUser] --constraints'
        );

        $this->assertSame(ExitCode::SUCCESS, $response->exit_code);
        $this->assertStringContainsString('Unique targets', $response->output);
        $this->assertStringContainsString('one-to-one', $response->output);
    }

    public function test_inspect_counts_connections_correctly(): void
    {
        $response = $this->service->run(
            'rattachments:inspect [AndyDefer.LaravelRattachments.Tests.Fixtures.Models.TestConstrainedUser] --connections'
        );

        $this->assertSame(ExitCode::SUCCESS, $response->exit_code);
        $this->assertStringContainsString('Found 1 connection types', $response->output);
        $this->assertStringContainsString('TestConstrainedUser → TestPost', $response->output);
        $this->assertStringContainsString('1x', $response->output);
    }

    public function test_inspect_with_both_flags(): void
    {
        $response = $this->service->run(
            'rattachments:inspect [AndyDefer.LaravelRattachments.Tests.Fixtures.Models.TestConstrainedUser] --connections --constraints'
        );

        $this->assertSame(ExitCode::SUCCESS, $response->exit_code);
        $this->assertStringContainsString('🔒 CONSTRAINTS', $response->output);
        $this->assertStringContainsString('🔗 EXISTING CONNECTIONS', $response->output);
    }

    public function test_inspect_with_dot_notation_models(): void
    {
        $response = $this->service->run(
            'rattachments:inspect [AndyDefer.LaravelRattachments.Tests.Fixtures.Models.TestConstrainedUser] --constraints'
        );

        $this->assertSame(ExitCode::SUCCESS, $response->exit_code);
        $this->assertStringContainsString('TestConstrainedUser', $response->output);
    }

    public function test_inspect_handles_models_without_connections(): void
    {
        $response = $this->service->run(
            'rattachments:inspect [AndyDefer.LaravelRattachments.Tests.Fixtures.Models.TestPost] --connections'
        );

        $this->assertSame(ExitCode::SUCCESS, $response->exit_code);
        $this->assertStringContainsString('No constrained models found. Nothing to display.', $response->output);
    }

    public function test_inspect_with_models_and_sources_prioritizes_models(): void
    {
        $response = $this->service->run(
            'rattachments:inspect [AndyDefer.LaravelRattachments.Tests.Fixtures.Models.TestConstrainedUser] [app.Models, tests.Fixtures.Models] --constraints'
        );

        $this->assertSame(ExitCode::SUCCESS, $response->exit_code);
        $this->assertStringContainsString('TestConstrainedUser', $response->output);
        $this->assertStringNotContainsString('Scanning sources:', $response->output);
    }

    public function test_inspect_with_empty_sources_uses_default(): void
    {
        $response = $this->service->run('rattachments:inspect [] [] --constraints');

        $this->assertSame(ExitCode::SUCCESS, $response->exit_code);
        $this->assertStringContainsString('No models specified. Discovering models from sources...', $response->output);
        $this->assertStringContainsString('No sources specified. Using default: app.Models', $response->output);
    }

    // ============================================================
    // DISALLOWED CONSTRAINTS TESTS
    // ============================================================

    public function test_inspect_shows_disallowed_targets(): void
    {
        $response = $this->service->run(
            'rattachments:inspect [AndyDefer.LaravelRattachments.Tests.Fixtures.Models.TestDisallowedUser] --constraints'
        );

        $this->assertSame(ExitCode::SUCCESS, $response->exit_code);
        $this->assertStringContainsString('🚫 Disallowed targets', $response->output);
        $this->assertStringContainsString('TestCheckPoint', $response->output);
        $this->assertStringContainsString('All roles disallowed', $response->output);
    }

    public function test_inspect_shows_disallowed_and_allowed_targets_together(): void
    {
        $response = $this->service->run(
            'rattachments:inspect [AndyDefer.LaravelRattachments.Tests.Fixtures.Models.TestDisallowedUser] --constraints'
        );

        $this->assertSame(ExitCode::SUCCESS, $response->exit_code);
        $this->assertStringContainsString('✅ Allowed targets', $response->output);
        $this->assertStringContainsString('TestPost', $response->output);
        $this->assertStringContainsString('TestUser', $response->output);
        $this->assertStringContainsString('🚫 Disallowed targets', $response->output);
        $this->assertStringContainsString('TestCheckPoint', $response->output);
        $this->assertStringContainsString('All roles disallowed', $response->output);
    }

    public function test_inspect_with_disallowed_models_shows_constraints(): void
    {
        $response = $this->service->run(
            'rattachments:inspect [AndyDefer.LaravelRattachments.Tests.Fixtures.Models.TestDisallowedUser] --constraints'
        );

        $this->assertSame(ExitCode::SUCCESS, $response->exit_code);
        $this->assertStringContainsString('TestDisallowedUser', $response->output);
        $this->assertStringContainsString('🚫 Disallowed targets', $response->output);
        $this->assertStringContainsString('TestCheckPoint', $response->output);
        $this->assertStringContainsString('All roles disallowed', $response->output);
    }

    public function test_inspect_shows_disallowed_roles_granularly(): void
    {
        $response = $this->service->run(
            'rattachments:inspect [AndyDefer.LaravelRattachments.Tests.Fixtures.Models.TestDisallowedUser] --constraints'
        );

        $this->assertSame(ExitCode::SUCCESS, $response->exit_code);
        $this->assertStringContainsString('🚫 Disallowed targets', $response->output);
        $this->assertStringContainsString('TestPost', $response->output);
        $this->assertStringContainsString('Roles: staff', $response->output);
        $this->assertStringContainsString('TestUser', $response->output);
        $this->assertStringContainsString('Roles: admin', $response->output);
    }

    public function test_inspect_shows_disallowed_targets_with_all_roles_blocked(): void
    {
        $response = $this->service->run(
            'rattachments:inspect [AndyDefer.LaravelRattachments.Tests.Fixtures.Models.TestDisallowedUser] --constraints'
        );

        $this->assertSame(ExitCode::SUCCESS, $response->exit_code);
        $this->assertStringContainsString('🚫 Disallowed targets', $response->output);
        $this->assertStringContainsString('TestCheckPoint', $response->output);
        $this->assertStringContainsString('All roles disallowed', $response->output);
    }

    public function test_inspect_shows_conflict_when_target_in_both_allowed_and_disallowed(): void
    {
        $response = $this->service->run(
            'rattachments:inspect [AndyDefer.LaravelRattachments.Tests.Fixtures.Models.TestDisallowedUser] --constraints'
        );

        $this->assertSame(ExitCode::SUCCESS, $response->exit_code);
        $this->assertStringContainsString('CONFLICT DETECTED', $response->output);
        $this->assertStringContainsString('TestPost', $response->output);
        $this->assertStringContainsString('DISALLOW WINS', $response->output);
        $this->assertStringContainsString('TestUser', $response->output);
        $this->assertStringContainsString('DISALLOW WINS', $response->output);
    }

    public function test_inspect_shows_override_in_allowed_section(): void
    {
        $response = $this->service->run(
            'rattachments:inspect [AndyDefer.LaravelRattachments.Tests.Fixtures.Models.TestDisallowedUser] --constraints'
        );

        $this->assertSame(ExitCode::SUCCESS, $response->exit_code);
        $this->assertStringContainsString('✅ Allowed targets', $response->output);
        $this->assertStringContainsString('TestPost', $response->output);
        $this->assertStringContainsString('OVERRIDDEN BY DISALLOW', $response->output);
        $this->assertStringContainsString('TestUser', $response->output);
        $this->assertStringContainsString('OVERRIDDEN BY DISALLOW', $response->output);
    }

    public function test_inspect_shows_disallowed_for_models_with_empty_array(): void
    {
        $response = $this->service->run(
            'rattachments:inspect [AndyDefer.LaravelRattachments.Tests.Fixtures.Models.TestDisallowedUser] --constraints'
        );

        $this->assertSame(ExitCode::SUCCESS, $response->exit_code);
        $this->assertStringContainsString('🚫 Disallowed targets', $response->output);
        $this->assertStringContainsString('TestCheckPoint', $response->output);
        $this->assertStringContainsString('All roles disallowed', $response->output);

        $this->assertStringContainsString('TestPost', $response->output);
        $this->assertStringContainsString('Roles: staff', $response->output);
        $this->assertStringContainsString('TestUser', $response->output);
        $this->assertStringContainsString('Roles: admin', $response->output);
    }

    public function test_inspect_with_multiple_disallowed_models(): void
    {
        $response = $this->service->run(
            'rattachments:inspect [AndyDefer.LaravelRattachments.Tests.Fixtures.Models.TestDisallowedUser, AndyDefer.LaravelRattachments.Tests.Fixtures.Models.TestConstrainedUser] --constraints'
        );

        $this->assertSame(ExitCode::SUCCESS, $response->exit_code);
        $this->assertStringContainsString('TestDisallowedUser', $response->output);
        $this->assertStringContainsString('🚫 Disallowed targets', $response->output);
        $this->assertStringContainsString('TestConstrainedUser', $response->output);
        $this->assertStringContainsString('✅ Allowed targets', $response->output);
    }
}
