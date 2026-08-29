<?php

declare(strict_types=1);

namespace AndyDefer\LaravelRattachments\Tests\Integration\Directives;

use AndyDefer\Directive\Enums\ExitCode;
use AndyDefer\Directive\Services\DirectiveTestingService;
use AndyDefer\LaravelRattachments\Directives\RattachmentsInspectDirective;
use AndyDefer\LaravelRattachments\Services\RattachmentService;
use AndyDefer\LaravelRattachments\Tests\Fixtures\Enums\Role;
use AndyDefer\LaravelRattachments\Tests\Fixtures\Models\TestCheckPoint;
use AndyDefer\LaravelRattachments\Tests\Fixtures\Models\TestConstrainedUser;
use AndyDefer\LaravelRattachments\Tests\Fixtures\Models\TestDisallowedUser;
use AndyDefer\LaravelRattachments\Tests\Fixtures\Models\TestHospital;
use AndyDefer\LaravelRattachments\Tests\Fixtures\Models\TestPost;
use AndyDefer\LaravelRattachments\Tests\Fixtures\Models\TestSpecializedUser;
use AndyDefer\LaravelRattachments\Tests\Fixtures\Models\TestSpecialty;
use AndyDefer\LaravelRattachments\Tests\Fixtures\Models\TestUser;
use AndyDefer\LaravelRattachments\Tests\IntegrationTestCase;
use Illuminate\Support\Facades\Schema;

final class RattachmentsInspectDirectiveTest extends IntegrationTestCase
{
    private DirectiveTestingService $service;

    private RattachmentService $rattachmentService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = new DirectiveTestingService(
            application: $this->app,
            sourcePaths: []
        );

        $this->service->getKernel()->addDirective(RattachmentsInspectDirective::class);

        $this->rattachmentService = $this->app->make(RattachmentService::class);

        $this->createTestData();
        $this->createDisallowedTestData();
        $this->createSpecializedTestData();
    }

    protected function tearDown(): void
    {
        $this->service->destroy();
        parent::tearDown();
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

    private function createSpecializedTestData(): void
    {
        $specializedUser = TestSpecializedUser::create([
            'name' => 'Specialized User',
            'email' => 'specialized@example.com',
        ]);

        $hospital = TestHospital::create([
            'name' => 'Test Hospital',
            'address' => 'Test Address',
        ]);

        $specialty = TestSpecialty::create([
            'name' => 'Cardiology',
            'code' => 'CAR',
        ]);

        $this->rattachmentService->attach($specializedUser, $hospital, Role::CHIEF);
        $this->rattachmentService->attach($specializedUser, $specialty, Role::PRIMARY);
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
            'rattachments:inspect [AndyDefer.LaravelRattachments.Tests.Fixtures.Models.TestPlainUser] --constraints'
        );

        $this->assertSame(ExitCode::SUCCESS, $response->exit_code);
        $this->assertStringContainsString('does not implement RattachmentInterface', $response->output);
    }

    public function test_inspect_with_invalid_model_class(): void
    {
        $response = $this->service->run(
            'rattachments:inspect [Invalid.Models.NonExistent] --constraints'
        );

        $this->assertSame(ExitCode::SUCCESS, $response->exit_code);
        $this->assertStringContainsString('Class not found', $response->output);
    }

    public function test_inspect_with_no_models_returns_error(): void
    {
        $response = $this->service->run('rattachments:inspect [] --constraints');

        $this->assertSame(ExitCode::INVALID_ARGUMENT, $response->exit_code);
        $this->assertStringContainsString('You must specify at least one model to inspect', $response->output);
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

    public function test_inspect_ignore_missing_flag_hides_missing_connections(): void
    {
        $response = $this->service->run(
            'rattachments:inspect [AndyDefer.LaravelRattachments.Tests.Fixtures.Models.TestConstrainedUser] --connections --ignore-missing'
        );

        $this->assertSame(ExitCode::SUCCESS, $response->exit_code);
        $this->assertStringContainsString('🔗 EXISTING CONNECTIONS', $response->output);
        $this->assertStringContainsString('TestConstrainedUser', $response->output);
        $this->assertStringContainsString('TestPost', $response->output);
        $this->assertStringNotContainsString('Possible missing connections', $response->output);
        $this->assertStringNotContainsString('Constraint defined but no connections found', $response->output);
        $this->assertStringContainsString('Missing connections suggestions hidden', $response->output);
    }

    public function test_inspect_ignore_missing_with_both_flags(): void
    {
        $response = $this->service->run(
            'rattachments:inspect [AndyDefer.LaravelRattachments.Tests.Fixtures.Models.TestConstrainedUser] --connections --constraints --ignore-missing'
        );

        $this->assertSame(ExitCode::SUCCESS, $response->exit_code);
        $this->assertStringContainsString('🔒 CONSTRAINTS', $response->output);
        $this->assertStringContainsString('🔗 EXISTING CONNECTIONS', $response->output);
        $this->assertStringNotContainsString('Possible missing connections', $response->output);
        $this->assertStringContainsString('Missing connections suggestions hidden', $response->output);
    }

    public function test_inspect_ignore_missing_only_with_connections(): void
    {
        $response = $this->service->run(
            'rattachments:inspect [AndyDefer.LaravelRattachments.Tests.Fixtures.Models.TestConstrainedUser] --connections --ignore-missing'
        );

        $this->assertSame(ExitCode::SUCCESS, $response->exit_code);
        $this->assertStringContainsString('🔗 EXISTING CONNECTIONS', $response->output);
        $this->assertStringNotContainsString('🔒 CONSTRAINTS', $response->output);
        $this->assertStringNotContainsString('Possible missing connections', $response->output);
        $this->assertStringContainsString('Missing connections suggestions hidden', $response->output);
    }

    public function test_inspect_ignore_missing_does_not_affect_constraints(): void
    {
        $response = $this->service->run(
            'rattachments:inspect [AndyDefer.LaravelRattachments.Tests.Fixtures.Models.TestConstrainedUser] --constraints --ignore-missing'
        );

        $this->assertSame(ExitCode::SUCCESS, $response->exit_code);
        $this->assertStringContainsString('🔒 CONSTRAINTS', $response->output);
        $this->assertStringContainsString('TestConstrainedUser', $response->output);
        $this->assertStringContainsString('TestPost', $response->output);
        $this->assertStringNotContainsString('🔗 EXISTING CONNECTIONS', $response->output);
        $this->assertStringNotContainsString('Possible missing connections', $response->output);
        $this->assertStringNotContainsString('Missing connections suggestions hidden', $response->output);
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
            'rattachments:inspect [AndyDefer.LaravelRattachments.Tests.Fixtures.Models.TestPlainUser] --connections'
        );

        $this->assertSame(ExitCode::SUCCESS, $response->exit_code);
        $this->assertStringContainsString('No constrained models found. Nothing to display.', $response->output);
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

    // ============================================================
    // SPECIFIC UNIQUE CONSTRAINTS TESTS
    // ============================================================

    public function test_inspect_shows_specific_unique_targets_with_roles(): void
    {
        $response = $this->service->run(
            'rattachments:inspect [AndyDefer.LaravelRattachments.Tests.Fixtures.Models.TestSpecializedUser] --constraints'
        );

        $this->assertSame(ExitCode::SUCCESS, $response->exit_code);
        $this->assertStringContainsString('🔒 Unique targets', $response->output);
        $this->assertStringContainsString('TestHospital', $response->output);
        $this->assertStringContainsString('one-to-one (roles: chief)', $response->output);
        $this->assertStringContainsString('TestSpecialty', $response->output);
        $this->assertStringContainsString('one-to-one (roles: primary)', $response->output);
        $this->assertStringContainsString('TestUser', $response->output);
        $this->assertStringContainsString('one-to-one (roles: best_friend)', $response->output);
    }

    public function test_inspect_shows_specific_unique_connections_for_specialized_user(): void
    {
        $response = $this->service->run(
            'rattachments:inspect [AndyDefer.LaravelRattachments.Tests.Fixtures.Models.TestSpecializedUser] --connections'
        );

        $this->assertSame(ExitCode::SUCCESS, $response->exit_code);
        $this->assertStringContainsString('🔗 EXISTING CONNECTIONS', $response->output);
        $this->assertStringContainsString('TestSpecializedUser → TestHospital', $response->output);
        $this->assertStringContainsString('TestSpecializedUser → TestSpecialty', $response->output);
        $this->assertStringContainsString('1x', $response->output);
    }

    public function test_inspect_shows_roles_by_connection_for_specialized_user(): void
    {
        $response = $this->service->run(
            'rattachments:inspect [AndyDefer.LaravelRattachments.Tests.Fixtures.Models.TestSpecializedUser] --connections'
        );

        $this->assertSame(ExitCode::SUCCESS, $response->exit_code);
        $this->assertStringContainsString('📋 Roles by connection', $response->output);
        $this->assertStringContainsString('TestSpecializedUser → TestHospital', $response->output);
        $this->assertStringContainsString('chief', $response->output);
        $this->assertStringContainsString('TestSpecializedUser → TestSpecialty', $response->output);
        $this->assertStringContainsString('primary', $response->output);
    }

    public function test_inspect_shows_missing_connections_for_specialized_user(): void
    {
        $response = $this->service->run(
            'rattachments:inspect [AndyDefer.LaravelRattachments.Tests.Fixtures.Models.TestSpecializedUser] --connections'
        );

        $this->assertSame(ExitCode::SUCCESS, $response->exit_code);
        $this->assertStringContainsString('Possible missing connections', $response->output);
        $this->assertStringContainsString('TestSpecializedUser → TestUser', $response->output);
        $this->assertStringContainsString('Constraint defined but no connections found', $response->output);
    }

    public function test_inspect_ignore_missing_for_specialized_user_hides_missing(): void
    {
        $response = $this->service->run(
            'rattachments:inspect [AndyDefer.LaravelRattachments.Tests.Fixtures.Models.TestSpecializedUser] --connections --ignore-missing'
        );

        $this->assertSame(ExitCode::SUCCESS, $response->exit_code);
        $this->assertStringContainsString('🔗 EXISTING CONNECTIONS', $response->output);
        $this->assertStringContainsString('TestSpecializedUser → TestHospital', $response->output);
        $this->assertStringContainsString('TestSpecializedUser → TestSpecialty', $response->output);
        $this->assertStringNotContainsString('Possible missing connections', $response->output);
        $this->assertStringNotContainsString('TestSpecializedUser → TestUser', $response->output);
        $this->assertStringContainsString('Missing connections suggestions hidden', $response->output);
    }
}
