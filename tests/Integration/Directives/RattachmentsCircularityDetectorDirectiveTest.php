<?php

declare(strict_types=1);

namespace AndyDefer\LaravelRattachments\Tests\Integration\Directives;

use AndyDefer\Directive\Enums\ExitCode;
use AndyDefer\Directive\Services\DirectiveTestingService;
use AndyDefer\LaravelRattachments\Directives\RattachmentsCircularityDetectorDirective;
use AndyDefer\LaravelRattachments\Tests\IntegrationTestCase;

final class RattachmentsCircularityDetectorDirectiveTest extends IntegrationTestCase
{
    private DirectiveTestingService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = new DirectiveTestingService(
            application: $this->app,
            sourcePaths: []
        );

        $this->service->getKernel()->addDirective(RattachmentsCircularityDetectorDirective::class);
    }

    protected function tearDown(): void
    {
        $this->service->destroy();
        parent::tearDown();
    }

    public function test_detects_circularity_between_user_and_hospital(): void
    {
        $response = $this->service->run(
            'rattachments:circularity [AndyDefer.LaravelRattachments.Tests.Fixtures.Models.TestCircularUser] [AndyDefer.LaravelRattachments.Tests.Fixtures.Models.TestCircularHospital]'
        );

        $this->assertSame(ExitCode::SUCCESS, $response->exit_code);
        $this->assertStringContainsString('🔄 Checking circularity violations...', $response->output);
        $this->assertStringContainsString('TestCircularUser', $response->output);
        $this->assertStringContainsString('TestCircularHospital', $response->output);
        $this->assertStringContainsString('Circular relationship', $response->output);
        $this->assertStringContainsString('role "chief"', $response->output);
        $this->assertStringContainsString('Total violations found: 1', $response->output);
    }

    public function test_detects_circularity_between_user_and_specialty(): void
    {
        $response = $this->service->run(
            'rattachments:circularity [AndyDefer.LaravelRattachments.Tests.Fixtures.Models.TestCircularUser] [AndyDefer.LaravelRattachments.Tests.Fixtures.Models.TestCircularSpecialty]'
        );

        $this->assertSame(ExitCode::SUCCESS, $response->exit_code);
        $this->assertStringContainsString('Circular relationship', $response->output);
        $this->assertStringContainsString('role "primary"', $response->output);
        $this->assertStringContainsString('Total violations found: 1', $response->output);
    }

    public function test_detects_circular_unique_constraint_between_user_and_hospital(): void
    {
        $response = $this->service->run(
            'rattachments:circularity [AndyDefer.LaravelRattachments.Tests.Fixtures.Models.TestCircularUser] [AndyDefer.LaravelRattachments.Tests.Fixtures.Models.TestCircularHospital]'
        );

        $this->assertSame(ExitCode::SUCCESS, $response->exit_code);
        $this->assertStringContainsString('Circular relationship', $response->output);
        $this->assertStringContainsString('role "chief"', $response->output);
        $this->assertStringContainsString('Total violations found: 1', $response->output);
    }

    public function test_skips_same_class_auto_attachment(): void
    {
        $response = $this->service->run(
            'rattachments:circularity [AndyDefer.LaravelRattachments.Tests.Fixtures.Models.TestCircularUser] [AndyDefer.LaravelRattachments.Tests.Fixtures.Models.TestCircularUser]'
        );

        $this->assertSame(ExitCode::SUCCESS, $response->exit_code);
        $this->assertStringContainsString('Skipped:', $response->output);
        $this->assertStringContainsString('same class', $response->output);
        $this->assertStringContainsString('Total violations found: 0', $response->output);
    }

    public function test_ignore_skipped_flag_hides_skipped_items(): void
    {
        $response = $this->service->run(
            'rattachments:circularity [AndyDefer.LaravelRattachments.Tests.Fixtures.Models.TestCircularUser] [AndyDefer.LaravelRattachments.Tests.Fixtures.Models.TestCircularUser] --ignore-skipped'
        );

        $this->assertSame(ExitCode::SUCCESS, $response->exit_code);
        $this->assertStringNotContainsString('Skipped:', $response->output);
        $this->assertStringNotContainsString('same class', $response->output);
        $this->assertStringContainsString('No circularity violations detected', $response->output);
    }

    public function test_ignore_skipped_flag_hides_not_implementing_interface_skips(): void
    {
        $response = $this->service->run(
            'rattachments:circularity [AndyDefer.LaravelRattachments.Tests.Fixtures.Models.TestPlainUser] [AndyDefer.LaravelRattachments.Tests.Fixtures.Models.TestCircularUser] --ignore-skipped'
        );

        $this->assertSame(ExitCode::SUCCESS, $response->exit_code);
        $this->assertStringNotContainsString('does not implement RattachmentInterface. Skipped.', $response->output);
        $this->assertStringContainsString('No circularity violations detected', $response->output);
    }

    public function test_ignore_skipped_flag_shows_circularities_when_present(): void
    {
        $response = $this->service->run(
            'rattachments:circularity [AndyDefer.LaravelRattachments.Tests.Fixtures.Models.TestCircularUser] [AndyDefer.LaravelRattachments.Tests.Fixtures.Models.TestCircularHospital] --ignore-skipped'
        );

        $this->assertSame(ExitCode::SUCCESS, $response->exit_code);
        $this->assertStringContainsString('Circular relationship', $response->output);
        $this->assertStringContainsString('role "chief"', $response->output);
        $this->assertStringContainsString('Total violations found: 1', $response->output);
    }

    public function test_ignore_skipped_flag_with_multiple_models_hides_skips_shows_circularities(): void
    {
        $response = $this->service->run(
            'rattachments:circularity [AndyDefer.LaravelRattachments.Tests.Fixtures.Models.TestCircularUser, AndyDefer.LaravelRattachments.Tests.Fixtures.Models.TestCircularHospital] [AndyDefer.LaravelRattachments.Tests.Fixtures.Models.TestCircularHospital, AndyDefer.LaravelRattachments.Tests.Fixtures.Models.TestCircularSpecialty] --ignore-skipped'
        );

        $this->assertSame(ExitCode::SUCCESS, $response->exit_code);

        // Les circularités sont toujours affichées
        $this->assertStringContainsString('Circular relationship', $response->output);
        $this->assertStringContainsString('chief', $response->output);
        $this->assertStringContainsString('primary', $response->output);

        // Les skips ne sont pas affichés
        $this->assertStringNotContainsString('Skipped:', $response->output);
        $this->assertStringNotContainsString('same class', $response->output);

        $this->assertStringContainsString('Total violations found: 2', $response->output);
    }

    public function test_handles_models_not_implementing_interface(): void
    {
        $response = $this->service->run(
            'rattachments:circularity [AndyDefer.LaravelRattachments.Tests.Fixtures.Models.TestPlainUser] [AndyDefer.LaravelRattachments.Tests.Fixtures.Models.TestCircularUser]'
        );

        $this->assertSame(ExitCode::SUCCESS, $response->exit_code);
        $this->assertStringContainsString('does not implement RattachmentInterface. Skipped.', $response->output);
        $this->assertStringContainsString('Total violations found: 0', $response->output);
    }

    public function test_handles_invalid_class_names(): void
    {
        $response = $this->service->run(
            'rattachments:circularity [Invalid.Models.NonExistent] [AndyDefer.LaravelRattachments.Tests.Fixtures.Models.TestCircularUser]'
        );

        $this->assertSame(ExitCode::SUCCESS, $response->exit_code);
        $this->assertStringContainsString('Class not found: Invalid\Models\NonExistent', $response->output);
        $this->assertStringContainsString('Total violations found: 0', $response->output);
    }

    public function test_handles_dot_notation_class_names(): void
    {
        $response = $this->service->run(
            'rattachments:circularity [AndyDefer.LaravelRattachments.Tests.Fixtures.Models.TestCircularUser] [AndyDefer.LaravelRattachments.Tests.Fixtures.Models.TestCircularHospital]'
        );

        $this->assertSame(ExitCode::SUCCESS, $response->exit_code);
        $this->assertStringContainsString('TestCircularUser', $response->output);
        $this->assertStringContainsString('TestCircularHospital', $response->output);
        $this->assertStringContainsString('Circular relationship', $response->output);
    }

    public function test_skips_models_without_allowed_targets(): void
    {
        $response = $this->service->run(
            'rattachments:circularity [AndyDefer.LaravelRattachments.Tests.Fixtures.Models.TestConstrainedUser] [AndyDefer.LaravelRattachments.Tests.Fixtures.Models.TestCircularUser]'
        );

        $this->assertSame(ExitCode::SUCCESS, $response->exit_code);
        $this->assertStringContainsString('No circularity violations detected', $response->output);
    }

    public function test_detects_multiple_circularities_with_multiple_models(): void
    {
        $response = $this->service->run(
            'rattachments:circularity [AndyDefer.LaravelRattachments.Tests.Fixtures.Models.TestCircularUser, AndyDefer.LaravelRattachments.Tests.Fixtures.Models.TestCircularHospital] [AndyDefer.LaravelRattachments.Tests.Fixtures.Models.TestCircularHospital, AndyDefer.LaravelRattachments.Tests.Fixtures.Models.TestCircularSpecialty]'
        );

        $this->assertSame(ExitCode::SUCCESS, $response->exit_code);

        // Vérifier la présence des modèles dans les listes
        $this->assertStringContainsString('TestCircularUser', $response->output);
        $this->assertStringContainsString('TestCircularHospital', $response->output);
        $this->assertStringContainsString('TestCircularSpecialty', $response->output);

        // Vérifier les circularités détectées
        $this->assertStringContainsString('TestCircularUser', $response->output);
        $this->assertStringContainsString('TestCircularHospital', $response->output);
        $this->assertStringContainsString('chief', $response->output);
        $this->assertStringContainsString('primary', $response->output);

        // Vérifier le skip (same class)
        $this->assertStringContainsString('Skipped', $response->output);

        // Vérifier le total
        $this->assertStringContainsString('Total violations found: 2', $response->output);
    }

    public function test_requires_both_arguments(): void
    {
        $response = $this->service->run('rattachments:circularity');

        $this->assertSame(ExitCode::INVALID_ARGUMENT, $response->exit_code);
        $this->assertStringContainsString('You must specify both rattachables and targets', $response->output);
    }

    public function test_requires_rattachables_argument(): void
    {
        $response = $this->service->run('rattachments:circularity [] [AndyDefer.LaravelRattachments.Tests.Fixtures.Models.TestCircularUser]');

        $this->assertSame(ExitCode::INVALID_ARGUMENT, $response->exit_code);
        $this->assertStringContainsString('You must specify both rattachables and targets', $response->output);
    }

    public function test_requires_targets_argument(): void
    {
        $response = $this->service->run('rattachments:circularity [AndyDefer.LaravelRattachments.Tests.Fixtures.Models.TestCircularUser] []');

        $this->assertSame(ExitCode::INVALID_ARGUMENT, $response->exit_code);
        $this->assertStringContainsString('You must specify both rattachables and targets', $response->output);
    }

    public function test_alias_rc_works(): void
    {
        $response = $this->service->run(
            'rc [AndyDefer.LaravelRattachments.Tests.Fixtures.Models.TestCircularUser] [AndyDefer.LaravelRattachments.Tests.Fixtures.Models.TestCircularHospital]'
        );

        $this->assertSame(ExitCode::SUCCESS, $response->exit_code);
        $this->assertStringContainsString('Circular relationship', $response->output);
    }

    public function test_alias_rattachments_check_circularity_works(): void
    {
        $response = $this->service->run(
            'rattachments:check-circularity [AndyDefer.LaravelRattachments.Tests.Fixtures.Models.TestCircularUser] [AndyDefer.LaravelRattachments.Tests.Fixtures.Models.TestCircularHospital]'
        );

        $this->assertSame(ExitCode::SUCCESS, $response->exit_code);
        $this->assertStringContainsString('Circular relationship', $response->output);
    }

    public function test_dedupes_duplicate_classes_in_rattachables(): void
    {
        $response = $this->service->run(
            'rattachments:circularity [AndyDefer.LaravelRattachments.Tests.Fixtures.Models.TestCircularUser, AndyDefer.LaravelRattachments.Tests.Fixtures.Models.TestCircularUser] [AndyDefer.LaravelRattachments.Tests.Fixtures.Models.TestCircularHospital]'
        );

        $this->assertSame(ExitCode::SUCCESS, $response->exit_code);
        $this->assertStringContainsString('TestCircularUser', $response->output);
        $this->assertStringContainsString('TestCircularHospital', $response->output);
        $this->assertStringContainsString('Circular relationship', $response->output);
    }

    public function test_dedupes_duplicate_classes_in_targets(): void
    {
        $response = $this->service->run(
            'rattachments:circularity [AndyDefer.LaravelRattachments.Tests.Fixtures.Models.TestCircularUser] [AndyDefer.LaravelRattachments.Tests.Fixtures.Models.TestCircularHospital, AndyDefer.LaravelRattachments.Tests.Fixtures.Models.TestCircularHospital]'
        );

        $this->assertSame(ExitCode::SUCCESS, $response->exit_code);
        $this->assertStringContainsString('TestCircularUser', $response->output);
        $this->assertStringContainsString('TestCircularHospital', $response->output);
        $this->assertStringContainsString('Circular relationship', $response->output);
    }

    public function test_handles_mixed_valid_and_invalid_classes(): void
    {
        $response = $this->service->run(
            'rattachments:circularity [AndyDefer.LaravelRattachments.Tests.Fixtures.Models.TestCircularUser, Invalid.Models.NonExistent] [AndyDefer.LaravelRattachments.Tests.Fixtures.Models.TestCircularHospital]'
        );

        $this->assertSame(ExitCode::SUCCESS, $response->exit_code);
        $this->assertStringContainsString('TestCircularUser', $response->output);
        $this->assertStringContainsString('Invalid\Models\NonExistent', $response->output);
        $this->assertStringContainsString('Class not found', $response->output);
        $this->assertStringContainsString('Circular relationship', $response->output);
        $this->assertStringContainsString('Total violations found: 1', $response->output);
    }
}
