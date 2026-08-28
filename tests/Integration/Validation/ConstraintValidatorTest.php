<?php

declare(strict_types=1);

namespace AndyDefer\LaravelRattachments\Tests\Integration\Validation;

use AndyDefer\LaravelRattachments\Enums\UnknownRole;
use AndyDefer\LaravelRattachments\Services\RattachmentService;
use AndyDefer\LaravelRattachments\Tests\Fixtures\Enums\Role;
use AndyDefer\LaravelRattachments\Tests\Fixtures\Models\TestConstrainedUser;
use AndyDefer\LaravelRattachments\Tests\Fixtures\Models\TestDisallowedUser;
use AndyDefer\LaravelRattachments\Tests\Fixtures\Models\TestPlainUser;
use AndyDefer\LaravelRattachments\Tests\Fixtures\Models\TestPost;
use AndyDefer\LaravelRattachments\Tests\Fixtures\Models\TestUser;
use AndyDefer\LaravelRattachments\Tests\IntegrationTestCase;
use AndyDefer\LaravelRattachments\Validation\ConstraintValidator;
use AndyDefer\Repository\Contracts\EnumerableInterface;
use RuntimeException;

final class ConstraintValidatorTest extends IntegrationTestCase
{
    private ConstraintValidator $validator;

    private RattachmentService $service;

    private TestUser $user;

    private TestPost $post;

    protected function setUp(): void
    {
        parent::setUp();

        $this->validator = $this->app->make(ConstraintValidator::class);
        $this->service = $this->app->make(RattachmentService::class);

        $this->user = TestUser::create([
            'name' => 'John Doe',
            'email' => 'john@example.com',
        ]);

        $this->post = TestPost::create([
            'user_id' => $this->user->id,
            'title' => 'Test Post',
            'body' => 'Test content',
        ]);
    }

    public function test_validate_constraints_passes_for_valid_attachment(): void
    {
        $rattachable = TestUser::create([
            'name' => 'Constrained User',
            'email' => 'constrained@example.com',
        ]);

        $post = TestPost::create([
            'user_id' => $rattachable->id,
            'title' => 'Another Post',
            'body' => 'Another content',
        ]);

        $this->validator->validateConstraints($rattachable, $post, Role::DOCTOR);

        $this->assertTrue(true);
    }

    public function test_validate_constraints_throws_exception_when_rattachable_does_not_implement_interface(): void
    {
        $rattachable = TestPlainUser::create([
            'name' => 'Plain User',
            'email' => 'plain@example.com',
        ]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage(
            'Model AndyDefer\LaravelRattachments\Tests\Fixtures\Models\TestPlainUser must implement AndyDefer\LaravelRattachments\Contracts\RattachmentConstraintsInterface to be attachable.'
        );

        $this->validator->validateConstraints($rattachable, $this->post, Role::DOCTOR);
    }

    public function test_validate_constraints_throws_exception_when_target_does_not_implement_interface(): void
    {
        $rattachable = $this->user;
        $target = TestPlainUser::create([
            'name' => 'Plain Target',
            'email' => 'plain-target@example.com',
        ]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage(
            'Model AndyDefer\LaravelRattachments\Tests\Fixtures\Models\TestPlainUser must implement AndyDefer\LaravelRattachments\Contracts\RattachmentConstraintsInterface to be a target.'
        );

        $this->validator->validateConstraints($rattachable, $target, Role::DOCTOR);
    }

    public function test_validate_constraints_throws_exception_when_target_not_allowed(): void
    {
        // TestPost has empty allowedTargets → ne permet aucun target
        $rattachable = TestPost::create([
            'user_id' => 1,
            'title' => 'Rattachable Post',
            'body' => 'Content',
        ]);

        $target = TestPost::create([
            'user_id' => 1,
            'title' => 'Target Post',
            'body' => 'Content',
        ]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('cannot be attached to');

        $this->validator->validateConstraints($rattachable, $target, Role::DOCTOR);
    }

    public function test_validate_constraints_throws_exception_when_role_not_allowed(): void
    {
        $rattachable = $this->user;
        $post = TestPost::create([
            'user_id' => $rattachable->id,
            'title' => 'Another Post',
            'body' => 'Another content',
        ]);

        // TestUser allows TestPost with DOCTOR and ADMIN → STAFF n'est pas autorisé
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Role "staff" is not allowed');

        $this->validator->validateConstraints($rattachable, $post, Role::STAFF);
    }

    public function test_validate_constraints_throws_exception_when_target_disallowed_with_all_roles(): void
    {
        $rattachable = TestDisallowedUser::create([
            'name' => 'Disallowed User',
            'email' => 'disallowed@example.com',
        ]);

        $post = TestPost::create([
            'user_id' => 1,
            'title' => 'Test Post',
            'body' => 'Content',
        ]);

        // STAFF est disallowed pour TestPost dans TestDisallowedUser
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Role "staff" is disallowed');

        $this->validator->validateConstraints($rattachable, $post, Role::STAFF);
    }

    public function test_validate_unique_constraints_passes_for_first_attachment(): void
    {
        $rattachable = TestConstrainedUser::create([
            'name' => 'Constrained User',
            'email' => 'constrained@example.com',
        ]);

        $post = TestPost::create([
            'user_id' => 1,
            'title' => 'First Post',
            'body' => 'First content',
        ]);

        $this->validator->validateUniqueConstraints($rattachable, $post);

        $this->assertTrue(true);
    }

    public function test_validate_unique_constraints_throws_exception_for_second_attachment_same_type(): void
    {
        $rattachable = TestConstrainedUser::create([
            'name' => 'Constrained User',
            'email' => 'constrained@example.com',
        ]);

        $post1 = TestPost::create([
            'user_id' => 1,
            'title' => 'First Post',
            'body' => 'First content',
        ]);

        $post2 = TestPost::create([
            'user_id' => 1,
            'title' => 'Second Post',
            'body' => 'Second content',
        ]);

        // Créer un premier attachment via le service
        $this->service->attach($rattachable, $post1, Role::DOCTOR);

        // La validation unique doit lever une exception car un attachment existe déjà
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('already has a unique attachment to');

        $this->validator->validateUniqueConstraints($rattachable, $post2);
    }

    public function test_validate_unique_constraints_passes_for_different_target_types(): void
    {
        $rattachable = TestConstrainedUser::create([
            'name' => 'Constrained User',
            'email' => 'constrained@example.com',
        ]);

        $post = TestPost::create([
            'user_id' => 1,
            'title' => 'Test Post',
            'body' => 'Test content',
        ]);

        $user = TestUser::create([
            'name' => 'Another User',
            'email' => 'another@example.com',
        ]);

        $this->validator->validateUniqueConstraints($rattachable, $post);
        $this->validator->validateUniqueConstraints($rattachable, $user);

        $this->assertTrue(true);
    }

    public function test_validate_role_value_passes_for_valid_role(): void
    {
        $this->validator->validateRoleValue(
            TestUser::class,
            TestPost::class,
            'doctor'
        );

        $this->assertTrue(true);
    }

    public function test_validate_role_value_throws_exception_for_invalid_role(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Role "invalid" is not allowed');

        $this->validator->validateRoleValue(
            TestUser::class,
            TestPost::class,
            'invalid'
        );
    }

    public function test_validate_role_value_throws_exception_for_role_not_allowed_for_target(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Role "staff" is not allowed');

        $this->validator->validateRoleValue(
            TestUser::class,
            TestPost::class,
            'staff'
        );
    }

    public function test_resolve_role_returns_enum_when_valid(): void
    {
        $result = $this->validator->resolveRole(
            TestUser::class,
            TestPost::class,
            'doctor'
        );

        $this->assertInstanceOf(EnumerableInterface::class, $result);
        $this->assertSame('doctor', $result->getValue());
    }

    public function test_resolve_role_returns_unknown_role_when_role_not_found(): void
    {
        $result = $this->validator->resolveRole(
            TestUser::class,
            TestPost::class,
            'unknown'
        );

        $this->assertInstanceOf(UnknownRole::class, $result);
        $this->assertSame('unknown', $result->getValue());
    }

    public function test_resolve_role_throws_exception_when_rattachable_does_not_implement_interface(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage(
            'Rattachable model AndyDefer\LaravelRattachments\Tests\Fixtures\Models\TestPlainUser must implement AndyDefer\LaravelRattachments\Contracts\RattachmentConstraintsInterface.'
        );

        $this->validator->resolveRole(
            TestPlainUser::class,
            TestPost::class,
            'doctor'
        );
    }

    public function test_validate_disallowed_target_with_specific_role(): void
    {
        $rattachable = TestDisallowedUser::create([
            'name' => 'Disallowed User',
            'email' => 'disallowed@example.com',
        ]);

        $post = TestPost::create([
            'user_id' => 1,
            'title' => 'Test Post',
            'body' => 'Test content',
        ]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Role "staff" is disallowed');

        $this->validator->validateConstraints($rattachable, $post, Role::STAFF);
    }

    public function test_validate_disallowed_target_with_all_roles_blocked(): void
    {
        $rattachable = TestDisallowedUser::create([
            'name' => 'Disallowed User',
            'email' => 'disallowed@example.com',
        ]);

        $post = TestPost::create([
            'user_id' => 1,
            'title' => 'Test Post',
            'body' => 'Test content',
        ]);

        $rattachable2 = TestPost::create([
            'user_id' => 1,
            'title' => 'Rattachable Post',
            'body' => 'Content',
        ]);

        $target = TestPost::create([
            'user_id' => 1,
            'title' => 'Target Post',
            'body' => 'Content',
        ]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('cannot be attached to');

        $this->validator->validateConstraints($rattachable2, $target, Role::DOCTOR);
    }
}
