<?php

declare(strict_types=1);

namespace AndyDefer\LaravelRattachments\Tests\Integration\Validation;

use AndyDefer\LaravelRattachments\Enums\UnknownRole;
use AndyDefer\LaravelRattachments\Services\RattachmentService;
use AndyDefer\LaravelRattachments\Tests\Fixtures\Enums\Role;
use AndyDefer\LaravelRattachments\Tests\Fixtures\Models\TestCircularHospital;
use AndyDefer\LaravelRattachments\Tests\Fixtures\Models\TestCircularSpecialty;
use AndyDefer\LaravelRattachments\Tests\Fixtures\Models\TestCircularUser;
use AndyDefer\LaravelRattachments\Tests\Fixtures\Models\TestConstrainedUser;
use AndyDefer\LaravelRattachments\Tests\Fixtures\Models\TestDisallowedUser;
use AndyDefer\LaravelRattachments\Tests\Fixtures\Models\TestHospital;
use AndyDefer\LaravelRattachments\Tests\Fixtures\Models\TestPlainUser;
use AndyDefer\LaravelRattachments\Tests\Fixtures\Models\TestPost;
use AndyDefer\LaravelRattachments\Tests\Fixtures\Models\TestSpecializedUser;
use AndyDefer\LaravelRattachments\Tests\Fixtures\Models\TestSpecialty;
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

    public function test_validate_constraints_throws_exception_when_target_not_allowed(): void
    {
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

        $this->validator->validateUniqueConstraints($rattachable, $post, Role::DOCTOR);

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

        $this->service->attach($rattachable, $post1, Role::DOCTOR);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('already has a unique attachment to');

        $this->validator->validateUniqueConstraints($rattachable, $post2, Role::DOCTOR);
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

        $this->validator->validateUniqueConstraints($rattachable, $post, Role::DOCTOR);
        $this->validator->validateUniqueConstraints($rattachable, $user, Role::ADMIN);

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
            'Rattachable model AndyDefer\LaravelRattachments\Tests\Fixtures\Models\TestPlainUser must implement AndyDefer\LaravelRattachments\Contracts\RattachmentInterface.'
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

    // ============================================================
    // SPECIFIC UNIQUE CONSTRAINTS TESTS WITH TestSpecializedUser
    // ============================================================

    public function test_specialized_user_can_have_one_chief_hospital(): void
    {
        $rattachable = TestSpecializedUser::create([
            'name' => 'Specialized User',
            'email' => 'specialized@example.com',
        ]);

        $hospital1 = TestHospital::create([
            'name' => 'Hospital 1',
            'address' => 'Address 1',
        ]);

        $hospital2 = TestHospital::create([
            'name' => 'Hospital 2',
            'address' => 'Address 2',
        ]);

        $this->service->attach($rattachable, $hospital1, Role::CHIEF);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('already has a unique attachment to');

        $this->validator->validateUniqueConstraints($rattachable, $hospital2, Role::CHIEF);
    }

    public function test_specialized_user_can_have_multiple_doctor_hospitals(): void
    {
        $rattachable = TestSpecializedUser::create([
            'name' => 'Specialized User',
            'email' => 'specialized@example.com',
        ]);

        $hospital1 = TestHospital::create([
            'name' => 'Hospital 1',
            'address' => 'Address 1',
        ]);

        $hospital2 = TestHospital::create([
            'name' => 'Hospital 2',
            'address' => 'Address 2',
        ]);

        $this->service->attach($rattachable, $hospital1, Role::DOCTOR);

        $this->validator->validateUniqueConstraints($rattachable, $hospital2, Role::DOCTOR);

        $this->assertTrue(true);
    }

    public function test_specialized_user_can_have_one_primary_specialty(): void
    {
        $rattachable = TestSpecializedUser::create([
            'name' => 'Specialized User',
            'email' => 'specialized@example.com',
        ]);

        $specialty1 = TestSpecialty::create([
            'name' => 'Cardiology',
            'code' => 'CAR',
        ]);

        $specialty2 = TestSpecialty::create([
            'name' => 'Neurology',
            'code' => 'NEU',
        ]);

        $this->service->attach($rattachable, $specialty1, Role::PRIMARY);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('already has a unique attachment to');

        $this->validator->validateUniqueConstraints($rattachable, $specialty2, Role::PRIMARY);
    }

    public function test_specialized_user_can_have_multiple_secondary_specialties(): void
    {
        $rattachable = TestSpecializedUser::create([
            'name' => 'Specialized User',
            'email' => 'specialized@example.com',
        ]);

        $specialty1 = TestSpecialty::create([
            'name' => 'Cardiology',
            'code' => 'CAR',
        ]);

        $specialty2 = TestSpecialty::create([
            'name' => 'Neurology',
            'code' => 'NEU',
        ]);

        $this->service->attach($rattachable, $specialty1, Role::SECONDARY);

        $this->validator->validateUniqueConstraints($rattachable, $specialty2, Role::SECONDARY);

        $this->assertTrue(true);
    }

    public function test_specialized_user_can_have_one_best_friend(): void
    {
        $rattachable = TestSpecializedUser::create([
            'name' => 'Specialized User',
            'email' => 'specialized@example.com',
        ]);

        $user1 = TestUser::create([
            'name' => 'User 1',
            'email' => 'user1@example.com',
        ]);

        $user2 = TestUser::create([
            'name' => 'User 2',
            'email' => 'user2@example.com',
        ]);

        $this->service->attach($rattachable, $user1, Role::BEST_FRIEND);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('already has a unique attachment to');

        $this->validator->validateUniqueConstraints($rattachable, $user2, Role::BEST_FRIEND);
    }

    public function test_specialized_user_can_have_multiple_friends(): void
    {
        $rattachable = TestSpecializedUser::create([
            'name' => 'Specialized User',
            'email' => 'specialized@example.com',
        ]);

        $user1 = TestUser::create([
            'name' => 'User 1',
            'email' => 'user1@example.com',
        ]);

        $user2 = TestUser::create([
            'name' => 'User 2',
            'email' => 'user2@example.com',
        ]);

        $this->service->attach($rattachable, $user1, Role::FRIEND);

        $this->validator->validateUniqueConstraints($rattachable, $user2, Role::FRIEND);

        $this->assertTrue(true);
    }

    public function test_specialized_user_can_have_chief_and_doctor_in_different_hospitals(): void
    {
        $rattachable = TestSpecializedUser::create([
            'name' => 'Specialized User',
            'email' => 'specialized@example.com',
        ]);

        $hospital1 = TestHospital::create([
            'name' => 'Hospital 1',
            'address' => 'Address 1',
        ]);

        $hospital2 = TestHospital::create([
            'name' => 'Hospital 2',
            'address' => 'Address 2',
        ]);

        $this->service->attach($rattachable, $hospital1, Role::CHIEF);

        $this->validator->validateUniqueConstraints($rattachable, $hospital2, Role::DOCTOR);

        $this->assertTrue(true);
    }

    public function test_specialized_user_can_have_primary_and_secondary_specialties(): void
    {
        $rattachable = TestSpecializedUser::create([
            'name' => 'Specialized User',
            'email' => 'specialized@example.com',
        ]);

        $specialty1 = TestSpecialty::create([
            'name' => 'Cardiology',
            'code' => 'CAR',
        ]);

        $specialty2 = TestSpecialty::create([
            'name' => 'Neurology',
            'code' => 'NEU',
        ]);

        $this->service->attach($rattachable, $specialty1, Role::PRIMARY);

        $this->validator->validateUniqueConstraints($rattachable, $specialty2, Role::SECONDARY);

        $this->assertTrue(true);
    }

    // ============================================================
    // CIRCULARITY TESTS
    // ============================================================

    public function test_circular_allowed_targets_throws_exception(): void
    {
        $circularUser = TestCircularUser::create([
            'name' => 'Circular User',
            'email' => 'circular@example.com',
        ]);

        $circularHospital = TestCircularHospital::create([
            'name' => 'Circular Hospital',
            'address' => 'Address',
        ]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Circular relationship detected');

        $this->validator->validateConstraints($circularUser, $circularHospital, Role::CHIEF);
    }

    public function test_circular_allowed_targets_passes_with_different_role(): void
    {
        $circularUser = TestCircularUser::create([
            'name' => 'Circular User',
            'email' => 'circular@example.com',
        ]);

        $circularHospital = TestCircularHospital::create([
            'name' => 'Circular Hospital',
            'address' => 'Address',
        ]);

        $this->validator->validateConstraints($circularUser, $circularHospital, Role::DOCTOR);

        $this->assertTrue(true);
    }

    public function test_circular_unique_targets_throws_exception(): void
    {
        $circularUser = TestCircularUser::create([
            'name' => 'Circular User',
            'email' => 'circular@example.com',
        ]);

        $circularHospital = TestCircularHospital::create([
            'name' => 'Circular Hospital',
            'address' => 'Address',
        ]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Circular unique constraint detected');

        $this->validator->validateUniqueConstraints($circularUser, $circularHospital, Role::CHIEF);
    }

    public function test_circular_unique_targets_passes_with_no_circularity(): void
    {
        $circularUser = TestCircularUser::create([
            'name' => 'Circular User',
            'email' => 'circular@example.com',
        ]);

        $circularSpecialty = TestCircularSpecialty::create([
            'name' => 'Cardiology',
            'code' => 'CAR',
        ]);

        // TestCircularSpecialty n'a PAS de uniqueTargets sur TestCircularUser
        // Donc pas de circularité → OK
        $this->validator->validateUniqueConstraints($circularUser, $circularSpecialty, Role::SECONDARY);

        $this->assertTrue(true);
    }
}
