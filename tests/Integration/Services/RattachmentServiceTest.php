<?php

declare(strict_types=1);

namespace AndyDefer\LaravelRattachments\Tests\Integration\Services;

use AndyDefer\DomainStructures\Utils\StrictDataObject;
use AndyDefer\LaravelRattachments\Contracts\Services\RattachmentServiceInterface;
use AndyDefer\LaravelRattachments\Enums\Role;
use AndyDefer\LaravelRattachments\Models\Rattachment;
use AndyDefer\LaravelRattachments\Repositories\RattachmentRepository;
use AndyDefer\LaravelRattachments\Services\RattachmentService;
use AndyDefer\LaravelRattachments\Tests\Fixtures\Models\TestCheckPoint;
use AndyDefer\LaravelRattachments\Tests\Fixtures\Models\TestConstrainedUser;
use AndyDefer\LaravelRattachments\Tests\Fixtures\Models\TestDisallowedUser;
use AndyDefer\LaravelRattachments\Tests\Fixtures\Models\TestPost;
use AndyDefer\LaravelRattachments\Tests\Fixtures\Models\TestUser;
use AndyDefer\LaravelRattachments\Tests\IntegrationTestCase;
use AndyDefer\Repository\Configs\RepositoryConfig;
use AndyDefer\Repository\Contracts\Configs\RepositoryConfigInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use RuntimeException;

final class RattachmentServiceTest extends IntegrationTestCase
{
    private RattachmentServiceInterface $service;

    private TestUser $user;

    private TestPost $post;

    protected function setUp(): void
    {
        parent::setUp();

        // ✅ Configurer les enum casts pour le repository
        $this->app['config']->set('repository.enum_casts', [
            'rattachments' => [
                'role' => Role::class,
            ],
        ]);

        // ✅ Rebinder RepositoryConfig
        $this->app->singleton(RepositoryConfig::class, function ($app) {
            return new RepositoryConfig($app['config']);
        });

        $this->app->bind(RepositoryConfigInterface::class, RepositoryConfig::class);

        $this->service = new RattachmentService(
            new RattachmentRepository
        );

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

    /**
     * Helper pour extraire le tableau du metadata.
     */
    private function getMetadataArray($attachment): array
    {
        if ($attachment->metadata instanceof StrictDataObject) {
            return $attachment->metadata->toArray();
        }

        return $attachment->metadata ?? [];
    }

    public function test_attach_creates_attachment(): void
    {
        $attachment = $this->service->attach(
            $this->user,
            $this->post,
            Role::DOCTOR,
            ['priority' => 'high']
        );

        $this->assertInstanceOf(Rattachment::class, $attachment);
        $this->assertEquals($this->user->getMorphClass(), $attachment->rattachable_type);
        $this->assertEquals($this->user->id, $attachment->rattachable_id);
        $this->assertEquals($this->post->getMorphClass(), $attachment->target_type);
        $this->assertEquals($this->post->id, $attachment->target_id);
        $this->assertEquals(Role::DOCTOR, $attachment->role);
        $this->assertEquals(['priority' => 'high'], $this->getMetadataArray($attachment));

        $this->assertDatabaseHas('rattachments', [
            'rattachable_type' => $this->user->getMorphClass(),
            'rattachable_id' => $this->user->id,
            'target_type' => $this->post->getMorphClass(),
            'target_id' => $this->post->id,
            'role' => 'doctor',
        ]);
    }

    public function test_attach_throws_exception_when_already_attached(): void
    {
        $this->service->attach($this->user, $this->post, Role::DOCTOR);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('is already attached to');

        $this->service->attach($this->user, $this->post, Role::ADMIN);
    }

    public function test_attach_multiple_creates_multiple_attachments(): void
    {
        $user2 = TestUser::create([
            'name' => 'Jane Doe',
            'email' => 'jane@example.com',
        ]);

        $rattachables = new Collection([$this->user, $user2]);

        $attachments = $this->service->attachMultiple(
            $rattachables,
            $this->post,
            Role::DOCTOR,
            ['department' => 'cardiology']
        );

        $this->assertCount(2, $attachments);
        $this->assertDatabaseCount('rattachments', 2);

        foreach ($attachments as $attachment) {
            $this->assertEquals($this->post->getMorphClass(), $attachment->target_type);
            $this->assertEquals($this->post->id, $attachment->target_id);
            $this->assertEquals(Role::DOCTOR, $attachment->role);
            $this->assertEquals(['department' => 'cardiology'], $this->getMetadataArray($attachment));
        }
    }

    public function test_attach_to_multiple_creates_multiple_attachments(): void
    {
        $post2 = TestPost::create([
            'user_id' => $this->user->id,
            'title' => 'Second Post',
            'body' => 'More content',
        ]);

        $targets = new Collection([$this->post, $post2]);

        $attachments = $this->service->attachToMultiple(
            $this->user,
            $targets,
            Role::ADMIN,
            ['role' => 'editor']
        );

        $this->assertCount(2, $attachments);
        $this->assertDatabaseCount('rattachments', 2);

        foreach ($attachments as $attachment) {
            $this->assertEquals($this->user->getMorphClass(), $attachment->rattachable_type);
            $this->assertEquals($this->user->id, $attachment->rattachable_id);
            $this->assertEquals(Role::ADMIN, $attachment->role);
            $this->assertEquals(['role' => 'editor'], $this->getMetadataArray($attachment));
        }
    }

    public function test_detach_removes_attachment(): void
    {
        $this->service->attach($this->user, $this->post, Role::DOCTOR);

        $this->assertDatabaseCount('rattachments', 1);

        $this->service->detach($this->user, $this->post);

        $this->assertDatabaseCount('rattachments', 0);
    }

    public function test_detach_throws_exception_when_not_attached(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('is not attached to');

        $this->service->detach($this->user, $this->post);
    }

    public function test_detach_multiple_removes_multiple_attachments(): void
    {
        $user2 = TestUser::create([
            'name' => 'Jane Doe',
            'email' => 'jane@example.com',
        ]);

        $this->service->attach($this->user, $this->post, Role::DOCTOR);
        $this->service->attach($user2, $this->post, Role::DOCTOR);

        $this->assertDatabaseCount('rattachments', 2);

        $rattachables = new Collection([$this->user, $user2]);
        $this->service->detachMultiple($rattachables, $this->post);

        $this->assertDatabaseCount('rattachments', 0);
    }

    public function test_detach_from_multiple_removes_multiple_attachments(): void
    {
        $post2 = TestPost::create([
            'user_id' => $this->user->id,
            'title' => 'Second Post',
            'body' => 'More content',
        ]);

        $this->service->attach($this->user, $this->post, Role::DOCTOR);
        $this->service->attach($this->user, $post2, Role::DOCTOR);

        $this->assertDatabaseCount('rattachments', 2);

        $targets = new Collection([$this->post, $post2]);
        $this->service->detachFromMultiple($this->user, $targets);

        $this->assertDatabaseCount('rattachments', 0);
    }

    public function test_detach_all_removes_all_attachments(): void
    {
        $post2 = TestPost::create([
            'user_id' => $this->user->id,
            'title' => 'Second Post',
            'body' => 'More content',
        ]);

        $this->service->attach($this->user, $this->post, Role::DOCTOR);
        $this->service->attach($this->user, $post2, Role::ADMIN);

        $this->assertDatabaseCount('rattachments', 2);

        $this->service->detachAll($this->user);

        $this->assertDatabaseCount('rattachments', 0);
    }

    public function test_is_attached_returns_true_when_attached(): void
    {
        $this->service->attach($this->user, $this->post, Role::DOCTOR);

        $this->assertTrue($this->service->isAttached($this->user, $this->post));
    }

    public function test_is_attached_returns_false_when_not_attached(): void
    {
        $this->assertFalse($this->service->isAttached($this->user, $this->post));
    }

    public function test_has_role_attached_returns_true_when_role_exists(): void
    {
        $this->service->attach($this->user, $this->post, Role::DOCTOR);

        $this->assertTrue($this->service->hasRoleAttached($this->post, Role::DOCTOR));
        $this->assertFalse($this->service->hasRoleAttached($this->post, Role::ADMIN));
    }

    public function test_get_rattachables_returns_collection(): void
    {
        $user2 = TestUser::create([
            'name' => 'Jane Doe',
            'email' => 'jane@example.com',
        ]);

        $this->service->attach($this->user, $this->post, Role::DOCTOR);
        $this->service->attach($user2, $this->post, Role::ADMIN);

        $rattachables = $this->service->getRattachables($this->post);

        $this->assertCount(2, $rattachables);
        $this->assertContains($this->user->id, $rattachables->pluck('id')->toArray());
        $this->assertContains($user2->id, $rattachables->pluck('id')->toArray());
    }

    public function test_get_targets_returns_collection(): void
    {
        $post2 = TestPost::create([
            'user_id' => $this->user->id,
            'title' => 'Second Post',
            'body' => 'More content',
        ]);

        $this->service->attach($this->user, $this->post, Role::DOCTOR);
        $this->service->attach($this->user, $post2, Role::ADMIN);

        $targets = $this->service->getTargets($this->user);

        $this->assertCount(2, $targets);
        $this->assertContains($this->post->id, $targets->pluck('id')->toArray());
        $this->assertContains($post2->id, $targets->pluck('id')->toArray());
    }

    public function test_get_rattachables_by_role_returns_filtered_collection(): void
    {
        $user2 = TestUser::create([
            'name' => 'Jane Doe',
            'email' => 'jane@example.com',
        ]);

        $this->service->attach($this->user, $this->post, Role::DOCTOR);
        $this->service->attach($user2, $this->post, Role::ADMIN);

        $doctors = $this->service->getRattachablesByRole($this->post, Role::DOCTOR);

        $this->assertCount(1, $doctors);
        $this->assertEquals($this->user->id, $doctors->first()->id);
    }

    public function test_get_targets_by_role_returns_filtered_collection(): void
    {
        $post2 = TestPost::create([
            'user_id' => $this->user->id,
            'title' => 'Second Post',
            'body' => 'More content',
        ]);

        $this->service->attach($this->user, $this->post, Role::DOCTOR);
        $this->service->attach($this->user, $post2, Role::ADMIN);

        $targets = $this->service->getTargetsByRole($this->user, Role::DOCTOR);

        $this->assertCount(1, $targets);
        $this->assertEquals($this->post->id, $targets->first()->id);
    }

    public function test_update_role_updates_existing_attachment(): void
    {
        $this->service->attach($this->user, $this->post, Role::DOCTOR);

        $this->service->updateRole($this->user, $this->post, Role::ADMIN);

        $attachment = $this->service->getAttachment($this->user, $this->post);
        $this->assertEquals(Role::ADMIN, $attachment->role);
    }

    public function test_update_role_throws_exception_when_not_attached(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('is not attached to');

        $this->service->updateRole($this->user, $this->post, Role::ADMIN);
    }

    public function test_update_role_for_multiple_updates_all(): void
    {
        $user2 = TestUser::create([
            'name' => 'Jane Doe',
            'email' => 'jane@example.com',
        ]);

        $this->service->attach($this->user, $this->post, Role::DOCTOR);
        $this->service->attach($user2, $this->post, Role::DOCTOR);

        $rattachables = new Collection([$this->user, $user2]);
        $this->service->updateRoleForMultiple($rattachables, $this->post, Role::ADMIN);

        $attachments = $this->service->getRattachablesByRole($this->post, Role::ADMIN);
        $this->assertCount(2, $attachments);
    }

    public function test_update_metadata_updates_existing_attachment(): void
    {
        $this->service->attach($this->user, $this->post, Role::DOCTOR, ['initial' => 'data']);

        $this->service->updateMetadata($this->user, $this->post, ['updated' => 'value']);

        $attachment = $this->service->getAttachment($this->user, $this->post);
        $this->assertEquals(['updated' => 'value'], $this->getMetadataArray($attachment));
    }

    public function test_merge_metadata_merges_with_existing(): void
    {
        $this->service->attach($this->user, $this->post, Role::DOCTOR, ['key1' => 'value1', 'key2' => 'value2']);

        $this->service->mergeMetadata($this->user, $this->post, ['key2' => 'new_value', 'key3' => 'value3']);

        $attachment = $this->service->getAttachment($this->user, $this->post);
        $this->assertEquals([
            'key1' => 'value1',
            'key2' => 'new_value',
            'key3' => 'value3',
        ], $this->getMetadataArray($attachment));
    }

    public function test_get_attachment_returns_null_when_not_found(): void
    {
        $attachment = $this->service->getAttachment($this->user, $this->post);
        $this->assertNull($attachment);
    }

    public function test_get_attachment_returns_model_when_found(): void
    {
        $this->service->attach($this->user, $this->post, Role::DOCTOR);

        $attachment = $this->service->getAttachment($this->user, $this->post);
        $this->assertNotNull($attachment);
        $this->assertEquals(Role::DOCTOR, $attachment->role);
    }

    public function test_get_targets_by_type_and_role_returns_filtered_collection(): void
    {
        $post = TestPost::create([
            'user_id' => $this->user->id,
            'title' => 'Test Post',
            'body' => 'Test content',
        ]);

        $post2 = TestPost::create([
            'user_id' => $this->user->id,
            'title' => 'Second Post',
            'body' => 'More content',
        ]);

        $this->service->attach($this->user, $post, Role::DOCTOR);
        $this->service->attach($this->user, $post2, Role::ADMIN);

        // Récupérer les TestPost avec rôle DOCTOR
        $targets = $this->service->getTargetsByTypeAndRole(
            $this->user,
            TestPost::class,
            Role::DOCTOR
        );

        $this->assertCount(1, $targets);
        $this->assertEquals($post->id, $targets->first()->id);
    }

    public function test_get_targets_by_type_and_roles_returns_filtered_collection(): void
    {
        $post = TestPost::create([
            'user_id' => $this->user->id,
            'title' => 'Test Post',
            'body' => 'Test content',
        ]);

        $post2 = TestPost::create([
            'user_id' => $this->user->id,
            'title' => 'Second Post',
            'body' => 'More content',
        ]);

        $this->service->attach($this->user, $post, Role::DOCTOR);
        $this->service->attach($this->user, $post2, Role::ADMIN);

        // Récupérer les TestPost avec rôle DOCTOR ou ADMIN
        $targets = $this->service->getTargetsByTypeAndRoles(
            $this->user,
            TestPost::class,
            [Role::DOCTOR, Role::ADMIN]
        );

        $this->assertCount(2, $targets);
    }

    public function test_get_targets_by_types_and_roles_returns_filtered_collection(): void
    {
        $post = TestPost::create([
            'user_id' => $this->user->id,
            'title' => 'Test Post',
            'body' => 'Test content',
        ]);

        $user2 = TestUser::create([
            'name' => 'Jane Doe',
            'email' => 'jane@example.com',
        ]);

        $this->service->attach($this->user, $post, Role::DOCTOR);
        $this->service->attach($this->user, $user2, Role::ADMIN);

        // Récupérer les TestPost et TestUser avec rôle DOCTOR
        $targets = $this->service->getTargetsByTypesAndRoles(
            $this->user,
            [TestPost::class, TestUser::class],
            [Role::DOCTOR]
        );

        $this->assertCount(1, $targets);
        $this->assertEquals($post->id, $targets->first()->id);
    }

    // ============================================================
    // GET TARGETS BY TYPE TESTS
    // ============================================================

    public function test_get_targets_by_type_returns_filtered_collection(): void
    {
        $post2 = TestPost::create([
            'user_id' => $this->user->id,
            'title' => 'Second Post',
            'body' => 'More content',
        ]);

        $user2 = TestUser::create([
            'name' => 'Jane Doe',
            'email' => 'jane@example.com',
        ]);

        // Attacher à différents types
        $this->service->attach($this->user, $this->post, Role::DOCTOR);
        $this->service->attach($this->user, $post2, Role::ADMIN);
        $this->service->attach($this->user, $user2, Role::STAFF);

        // Récupérer uniquement les TestPost
        $targets = $this->service->getTargetsByType($this->user, TestPost::class);

        $this->assertCount(2, $targets);

        // Vérifier que tous les targets sont des TestPost
        foreach ($targets as $target) {
            $this->assertInstanceOf(TestPost::class, $target);
        }

        // Vérifier la présence des IDs des posts
        $targetIds = $targets->pluck('id')->toArray();
        $this->assertContains($this->post->id, $targetIds);
        $this->assertContains($post2->id, $targetIds);

        // Vérifier qu'aucun TestUser n'est dans la collection
        $this->assertFalse($targets->contains(fn ($target) => $target instanceof TestUser));
    }

    public function test_get_targets_by_type_returns_empty_collection_when_no_targets(): void
    {
        // Aucun rattachement créé
        $targets = $this->service->getTargetsByType($this->user, TestPost::class);

        $this->assertCount(0, $targets);
        $this->assertInstanceOf(Collection::class, $targets);
    }

    public function test_get_targets_by_type_with_pagination_returns_paginated_results(): void
    {
        // Créer 5 posts
        for ($i = 1; $i <= 5; $i++) {
            $post = TestPost::create([
                'user_id' => $this->user->id,
                'title' => "Post {$i}",
                'body' => "Content {$i}",
            ]);
            $this->service->attach($this->user, $post, Role::DOCTOR);
        }

        // Pagination : 2 par page, page 1
        $paginator = $this->service->getTargetsByTypePaginated(
            $this->user,
            TestPost::class,
            2,
            1
        );

        $this->assertInstanceOf(LengthAwarePaginator::class, $paginator);
        $this->assertEquals(2, $paginator->perPage());
        $this->assertEquals(5, $paginator->total());
        $this->assertEquals(1, $paginator->currentPage());
        $this->assertEquals(3, $paginator->lastPage());

        // Page 2
        $paginator2 = $this->service->getTargetsByTypePaginated(
            $this->user,
            TestPost::class,
            2,
            2
        );

        $this->assertEquals(2, $paginator2->perPage());
        $this->assertEquals(2, $paginator2->currentPage());
    }

    public function test_get_targets_by_type_with_different_types_returns_correct_collection(): void
    {
        $post = TestPost::create([
            'user_id' => $this->user->id,
            'title' => 'Test Post',
            'body' => 'Test content',
        ]);

        $user2 = TestUser::create([
            'name' => 'Jane Doe',
            'email' => 'jane@example.com',
        ]);

        $this->service->attach($this->user, $post, Role::DOCTOR);
        $this->service->attach($this->user, $user2, Role::ADMIN);

        // Récupérer les TestPost
        $posts = $this->service->getTargetsByType($this->user, TestPost::class);
        $this->assertCount(1, $posts);
        $this->assertEquals($post->id, $posts->first()->id);

        // Récupérer les TestUser
        $users = $this->service->getTargetsByType($this->user, TestUser::class);
        $this->assertCount(1, $users);
        $this->assertEquals($user2->id, $users->first()->id);
    }

    public function test_get_targets_by_type_with_constraints_respects_unique_constraints(): void
    {
        $constrainedUser = TestConstrainedUser::create([
            'name' => 'Constrained User',
            'email' => 'constrained@example.com',
        ]);

        $post1 = TestPost::create([
            'user_id' => $this->user->id,
            'title' => 'First Post',
            'body' => 'First content',
        ]);

        $post2 = TestPost::create([
            'user_id' => $this->user->id,
            'title' => 'Second Post',
            'body' => 'Second content',
        ]);

        // Attacher le premier post
        $this->service->attach($constrainedUser, $post1, Role::DOCTOR);

        // Récupérer les targets
        $targets = $this->service->getTargetsByType($constrainedUser, TestPost::class);

        $this->assertCount(1, $targets);
        $this->assertEquals($post1->id, $targets->first()->id);

        // Essayer d'attacher un deuxième post (devrait échouer à cause de la contrainte unique)
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('already has a unique attachment to');

        $this->service->attach($constrainedUser, $post2, Role::ADMIN);
    }

    public function test_count_rattachables_returns_correct_count(): void
    {
        $user2 = TestUser::create([
            'name' => 'Jane Doe',
            'email' => 'jane@example.com',
        ]);

        $this->service->attach($this->user, $this->post, Role::DOCTOR);
        $this->service->attach($user2, $this->post, Role::ADMIN);

        $this->assertEquals(2, $this->service->countRattachables($this->post));
    }

    public function test_count_targets_returns_correct_count(): void
    {
        $post2 = TestPost::create([
            'user_id' => $this->user->id,
            'title' => 'Second Post',
            'body' => 'More content',
        ]);

        $this->service->attach($this->user, $this->post, Role::DOCTOR);
        $this->service->attach($this->user, $post2, Role::ADMIN);

        $this->assertEquals(2, $this->service->countTargets($this->user));
    }

    public function test_count_rattachables_by_role_returns_correct_count(): void
    {
        $user2 = TestUser::create([
            'name' => 'Jane Doe',
            'email' => 'jane@example.com',
        ]);

        $this->service->attach($this->user, $this->post, Role::DOCTOR);
        $this->service->attach($user2, $this->post, Role::ADMIN);

        $this->assertEquals(1, $this->service->countRattachablesByRole($this->post, Role::DOCTOR));
        $this->assertEquals(1, $this->service->countRattachablesByRole($this->post, Role::ADMIN));
    }

    public function test_count_targets_by_role_returns_correct_count(): void
    {
        $post2 = TestPost::create([
            'user_id' => $this->user->id,
            'title' => 'Second Post',
            'body' => 'More content',
        ]);

        $this->service->attach($this->user, $this->post, Role::DOCTOR);
        $this->service->attach($this->user, $post2, Role::ADMIN);

        $this->assertEquals(1, $this->service->countTargetsByRole($this->user, Role::DOCTOR));
        $this->assertEquals(1, $this->service->countTargetsByRole($this->user, Role::ADMIN));
    }

    public function test_get_distinct_roles_for_target(): void
    {
        $user2 = TestUser::create([
            'name' => 'Jane Doe',
            'email' => 'jane@example.com',
        ]);

        $this->service->attach($this->user, $this->post, Role::DOCTOR);
        $this->service->attach($user2, $this->post, Role::ADMIN);

        $roles = $this->service->getDistinctRolesForTarget($this->post);

        $roleValues = $roles->map(fn ($role) => $role instanceof Role ? $role->value : $role)->toArray();

        $this->assertCount(2, $roles);
        $this->assertContains('doctor', $roleValues);
        $this->assertContains('admin', $roleValues);
    }

    public function test_get_distinct_roles_for_rattachable(): void
    {
        $post2 = TestPost::create([
            'user_id' => $this->user->id,
            'title' => 'Second Post',
            'body' => 'More content',
        ]);

        $this->service->attach($this->user, $this->post, Role::DOCTOR);
        $this->service->attach($this->user, $post2, Role::ADMIN);

        $roles = $this->service->getDistinctRolesForRattachable($this->user);

        $roleValues = $roles->map(fn ($role) => $role instanceof Role ? $role->value : $role)->toArray();

        $this->assertCount(2, $roles);
        $this->assertContains('doctor', $roleValues);
        $this->assertContains('admin', $roleValues);
    }

    public function test_has_attachments_between(): void
    {
        $user2 = TestUser::create([
            'name' => 'Jane Doe',
            'email' => 'jane@example.com',
        ]);

        $this->service->attach($this->user, $this->post, Role::DOCTOR);

        $this->assertTrue(
            $this->service->hasAttachmentsBetween($this->user, $this->post)
        );

        $this->assertFalse(
            $this->service->hasAttachmentsBetween($user2, $this->post)
        );
    }

    public function test_get_attachments_between_types(): void
    {
        $user2 = TestUser::create([
            'name' => 'Jane Doe',
            'email' => 'jane@example.com',
        ]);

        $this->service->attach($this->user, $this->post, Role::DOCTOR);
        $this->service->attach($user2, $this->post, Role::ADMIN);

        $attachments = $this->service->getAttachmentsBetweenTypes(
            $this->user->getMorphClass(),
            $this->post->getMorphClass()
        );

        $this->assertCount(2, $attachments);
        $this->assertEquals($this->user->id, $attachments->first()->rattachable_id);
        $this->assertEquals($user2->id, $attachments->last()->rattachable_id);
    }

    public function test_delete_all_attachments_between_types(): void
    {
        $user2 = TestUser::create([
            'name' => 'Jane Doe',
            'email' => 'jane@example.com',
        ]);

        $this->service->attach($this->user, $this->post, Role::DOCTOR);
        $this->service->attach($user2, $this->post, Role::ADMIN);

        $this->assertDatabaseCount('rattachments', 2);

        $deleted = $this->service->deleteAllAttachmentsBetweenTypes(
            $this->user->getMorphClass(),
            $this->post->getMorphClass()
        );

        $this->assertEquals(2, $deleted);
        $this->assertDatabaseCount('rattachments', 0);
    }

    public function test_sync_attachments_creates_updates_and_deletes(): void
    {
        $user2 = TestUser::create([
            'name' => 'Jane Doe',
            'email' => 'jane@example.com',
        ]);

        $post2 = TestPost::create([
            'user_id' => $this->user->id,
            'title' => 'Second Post',
            'body' => 'More content',
        ]);

        $this->service->attach($this->user, $this->post, Role::DOCTOR);
        $this->service->attach($this->user, $post2, Role::ADMIN);

        $this->assertDatabaseCount('rattachments', 2);

        $targets = [
            [
                'target' => $this->post,
                'role' => Role::ADMIN,
                'metadata' => ['updated' => true],
            ],
            [
                'target' => $post2, // On garde post2, pas user2
                'role' => Role::DOCTOR,
            ],
        ];

        $results = $this->service->syncAttachments($this->user, $targets);

        $this->assertCount(2, $results);

        // Vérifier que post2 est toujours attaché (avec le nouveau rôle)
        $this->assertTrue($this->service->isAttached($this->user, $post2));

        // Vérifier que post a été mis à jour
        $attachment = $this->service->getAttachment($this->user, $this->post);
        $this->assertNotNull($attachment);
        $this->assertEquals(Role::ADMIN, $attachment->role);
        $this->assertEquals(['updated' => true], $this->getMetadataArray($attachment));

        // Vérifier que post2 a été mis à jour
        $attachment2 = $this->service->getAttachment($this->user, $post2);
        $this->assertNotNull($attachment2);
        $this->assertEquals(Role::DOCTOR, $attachment2->role);
    }

    public function test_has_attachments_between_types(): void
    {
        $user2 = TestUser::create([
            'name' => 'Jane Doe',
            'email' => 'jane@example.com',
        ]);

        $this->service->attach($this->user, $this->post, Role::DOCTOR);

        // ✅ Vrai : il existe un attachment entre TestUser et TestPost
        $this->assertTrue(
            $this->service->hasAttachmentsBetweenTypes(
                $this->user->getMorphClass(),
                $this->post->getMorphClass()
            )
        );

        // ✅ Vrai aussi : il existe un attachment entre TestUser et TestPost
        // (peu importe quel TestUser et quel TestPost)
        $this->assertTrue(
            $this->service->hasAttachmentsBetweenTypes(
                $user2->getMorphClass(),
                $this->post->getMorphClass()
            )
        );
    }

    public function test_attach_with_constraints_allowed_target_and_role(): void
    {
        $constrainedUser = TestConstrainedUser::create([
            'name' => 'Constrained User',
            'email' => 'constrained@example.com',
        ]);

        $attachment = $this->service->attach(
            $constrainedUser,
            $this->post,
            Role::DOCTOR,
            ['test' => true]
        );

        $this->assertInstanceOf(Rattachment::class, $attachment);
        $this->assertEquals(Role::DOCTOR, $attachment->role);
    }

    public function test_attach_with_constraints_throws_exception_for_disallowed_target(): void
    {
        $constrainedUser = TestConstrainedUser::create([
            'name' => 'Constrained User',
            'email' => 'constrained@example.com',
        ]);

        $checkpoint = TestCheckPoint::create([
            'name' => 'Test Checkpoint',
            'location' => 'Test Location',
            'is_active' => true,
        ]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('cannot be attached to');

        $this->service->attach($constrainedUser, $checkpoint, Role::DOCTOR);
    }

    public function test_attach_with_constraints_throws_exception_for_disallowed_role(): void
    {
        $constrainedUser = TestConstrainedUser::create([
            'name' => 'Constrained User',
            'email' => 'constrained@example.com',
        ]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Role "pharmacist" is not allowed');

        $this->service->attach($constrainedUser, $this->post, Role::PHARMACIST);
    }

    public function test_update_role_with_constraints_validates_role(): void
    {
        $constrainedUser = TestConstrainedUser::create([
            'name' => 'Constrained User',
            'email' => 'constrained@example.com',
        ]);

        $this->service->attach($constrainedUser, $this->post, Role::DOCTOR);

        $this->service->updateRole($constrainedUser, $this->post, Role::ADMIN);

        $attachment = $this->service->getAttachment($constrainedUser, $this->post);
        $this->assertEquals(Role::ADMIN, $attachment->role);
    }

    public function test_update_role_with_constraints_throws_exception_for_disallowed_role(): void
    {
        $constrainedUser = TestConstrainedUser::create([
            'name' => 'Constrained User',
            'email' => 'constrained@example.com',
        ]);

        $this->service->attach($constrainedUser, $this->post, Role::DOCTOR);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Role "pharmacist" is not allowed');

        $this->service->updateRole($constrainedUser, $this->post, Role::PHARMACIST);
    }

    public function test_sync_attachments_with_constraints_validates_roles(): void
    {
        $constrainedUser = TestConstrainedUser::create([
            'name' => 'Constrained User',
            'email' => 'constrained@example.com',
        ]);

        $targets = [
            [
                'target' => $this->post,
                'role' => Role::ADMIN,
            ],
        ];

        $results = $this->service->syncAttachments($constrainedUser, $targets);

        $this->assertCount(1, $results);
        $this->assertEquals(Role::ADMIN, $results->first()->role);
    }

    public function test_sync_attachments_with_constraints_throws_exception_for_disallowed_role(): void
    {
        $constrainedUser = TestConstrainedUser::create([
            'name' => 'Constrained User',
            'email' => 'constrained@example.com',
        ]);

        $targets = [
            [
                'target' => $this->post,
                'role' => Role::PHARMACIST,
            ],
        ];

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Role "pharmacist" is not allowed');

        $this->service->syncAttachments($constrainedUser, $targets);
    }

    // ============================================================
    // UNIQUE CONSTRAINTS TESTS
    // ============================================================

    public function test_attach_with_unique_constraint_allows_single_attachment(): void
    {
        $constrainedUser = TestConstrainedUser::create([
            'name' => 'Constrained User',
            'email' => 'constrained@example.com',
        ]);

        $post = TestPost::create([
            'user_id' => $this->user->id,
            'title' => 'First Post',
            'body' => 'First content',
        ]);

        // ✅ Premier rattachement autorisé
        $attachment = $this->service->attach(
            $constrainedUser,
            $post,
            Role::DOCTOR
        );

        $this->assertInstanceOf(Rattachment::class, $attachment);
        $this->assertEquals(Role::DOCTOR, $attachment->role);
    }

    public function test_attach_with_unique_constraint_throws_exception_for_second_attachment_to_same_type(): void
    {
        $constrainedUser = TestConstrainedUser::create([
            'name' => 'Constrained User',
            'email' => 'constrained@example.com',
        ]);

        $post1 = TestPost::create([
            'user_id' => $this->user->id,
            'title' => 'First Post',
            'body' => 'First content',
        ]);

        $post2 = TestPost::create([
            'user_id' => $this->user->id,
            'title' => 'Second Post',
            'body' => 'Second content',
        ]);

        // ✅ Premier rattachement autorisé
        $this->service->attach($constrainedUser, $post1, Role::DOCTOR);

        // ❌ Deuxième rattachement vers le même type de target → Exception
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('already has a unique attachment to');

        $this->service->attach($constrainedUser, $post2, Role::ADMIN);
    }

    public function test_attach_with_unique_constraint_allows_different_target_types(): void
    {
        $constrainedUser = TestConstrainedUser::create([
            'name' => 'Constrained User',
            'email' => 'constrained@example.com',
        ]);

        $post = TestPost::create([
            'user_id' => $this->user->id,
            'title' => 'Test Post',
            'body' => 'Test content',
        ]);

        $user2 = TestUser::create([
            'name' => 'Another User',
            'email' => 'another@example.com',
        ]);

        // ✅ Rattachement vers TestPost autorisé
        $this->service->attach($constrainedUser, $post, Role::DOCTOR);

        // ✅ Rattachement vers TestUser (target différent) autorisé
        $attachment = $this->service->attach($constrainedUser, $user2, Role::ADMIN);

        $this->assertInstanceOf(Rattachment::class, $attachment);
        $this->assertEquals(Role::ADMIN, $attachment->role);
    }

    public function test_sync_attachments_with_unique_constraint_prevents_duplicate_types(): void
    {
        $constrainedUser = TestConstrainedUser::create([
            'name' => 'Constrained User',
            'email' => 'constrained@example.com',
        ]);

        $post1 = TestPost::create([
            'user_id' => $this->user->id,
            'title' => 'First Post',
            'body' => 'First content',
        ]);

        $post2 = TestPost::create([
            'user_id' => $this->user->id,
            'title' => 'Second Post',
            'body' => 'Second content',
        ]);

        // Premier rattachement autorisé
        $this->service->attach($constrainedUser, $post1, Role::DOCTOR);

        // ❌ Sync avec deux TestPost → Exception
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('already has a unique attachment to');

        $this->service->syncAttachments($constrainedUser, [
            [
                'target' => $post1,
                'role' => Role::DOCTOR,
            ],
            [
                'target' => $post2,
                'role' => Role::ADMIN,
            ],
        ]);
    }

    public function test_update_role_does_not_trigger_unique_constraint(): void
    {
        $constrainedUser = TestConstrainedUser::create([
            'name' => 'Constrained User',
            'email' => 'constrained@example.com',
        ]);

        $post = TestPost::create([
            'user_id' => $this->user->id,
            'title' => 'Test Post',
            'body' => 'Test content',
        ]);

        $this->service->attach($constrainedUser, $post, Role::DOCTOR);

        // ✅ Mise à jour du rôle autorisée (ne crée pas de nouveau rattachement)
        $this->service->updateRole($constrainedUser, $post, Role::ADMIN);

        $attachment = $this->service->getAttachment($constrainedUser, $post);
        $this->assertEquals(Role::ADMIN, $attachment->role);
    }

    public function test_unique_constraint_does_not_affect_models_without_interface(): void
    {
        // TestUser n'implémente pas RattachmentConstraintsInterface
        $user = TestUser::create([
            'name' => 'Normal User',
            'email' => 'normal@example.com',
        ]);

        $post1 = TestPost::create([
            'user_id' => $this->user->id,
            'title' => 'First Post',
            'body' => 'First content',
        ]);

        $post2 = TestPost::create([
            'user_id' => $this->user->id,
            'title' => 'Second Post',
            'body' => 'Second content',
        ]);

        // ✅ Rattachement autorisé
        $this->service->attach($user, $post1, Role::DOCTOR);

        // ✅ Deuxième rattachement autorisé (pas de contrainte unique)
        $attachment = $this->service->attach($user, $post2, Role::ADMIN);

        $this->assertInstanceOf(Rattachment::class, $attachment);
        $this->assertEquals(Role::ADMIN, $attachment->role);
    }

    // ============================================================
    // DISALLOWED CONSTRAINTS TESTS
    // ============================================================

    public function test_attach_with_disallowed_target_throws_exception(): void
    {
        $disallowedUser = TestDisallowedUser::create([
            'name' => 'Disallowed User',
            'email' => 'disallowed@example.com',
        ]);

        $checkpoint = TestCheckPoint::create([
            'name' => 'Test Checkpoint',
            'location' => 'Test Location',
            'is_active' => true,
        ]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('cannot be attached to');

        $this->service->attach($disallowedUser, $checkpoint, Role::DOCTOR);
    }

    public function test_attach_with_allowed_target_works_even_with_disallowed_defined(): void
    {
        $disallowedUser = TestDisallowedUser::create([
            'name' => 'Disallowed User',
            'email' => 'disallowed@example.com',
        ]);

        $post = TestPost::create([
            'user_id' => $this->user->id,
            'title' => 'Test Post',
            'body' => 'Test content',
        ]);

        $attachment = $this->service->attach(
            $disallowedUser,
            $post,
            Role::DOCTOR
        );

        $this->assertInstanceOf(Rattachment::class, $attachment);
        $this->assertEquals(Role::DOCTOR, $attachment->role);
    }

    public function test_disallowed_target_override_allowed_target(): void
    {
        $disallowedUser = TestDisallowedUser::create([
            'name' => 'Disallowed User',
            'email' => 'disallowed@example.com',
        ]);

        $post = TestPost::create([
            'user_id' => $this->user->id,
            'title' => 'Test Post',
            'body' => 'Test content',
        ]);

        $checkpoint = TestCheckPoint::create([
            'name' => 'Test Checkpoint',
            'location' => 'Test Location',
            'is_active' => true,
        ]);

        // ✅ Attacher à Post (autorisé) → OK
        $attachment = $this->service->attach($disallowedUser, $post, Role::DOCTOR);
        $this->assertInstanceOf(Rattachment::class, $attachment);

        // ❌ Attacher à Checkpoint (disallowed) → Exception
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('cannot be attached to');

        $this->service->attach($disallowedUser, $checkpoint, Role::STAFF);
    }

    public function test_disallowed_target_is_respected_in_sync_attachments(): void
    {
        $disallowedUser = TestDisallowedUser::create([
            'name' => 'Disallowed User',
            'email' => 'disallowed@example.com',
        ]);

        $post = TestPost::create([
            'user_id' => $this->user->id,
            'title' => 'Test Post',
            'body' => 'Test content',
        ]);

        $checkpoint = TestCheckPoint::create([
            'name' => 'Test Checkpoint',
            'location' => 'Test Location',
            'is_active' => true,
        ]);

        // ✅ Premier rattachement autorisé
        $this->service->attach($disallowedUser, $post, Role::DOCTOR);

        // ❌ Sync avec Checkpoint (disallowed) → Exception
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('cannot be attached to');

        $this->service->syncAttachments($disallowedUser, [
            [
                'target' => $post,
                'role' => Role::DOCTOR,
            ],
            [
                'target' => $checkpoint,
                'role' => Role::STAFF,
            ],
        ]);
    }

    public function test_attach_with_disallowed_role_on_allowed_target_throws_exception(): void
    {
        $disallowedUser = TestDisallowedUser::create([
            'name' => 'Disallowed User',
            'email' => 'disallowed@example.com',
        ]);

        $post = TestPost::create([
            'user_id' => $this->user->id,
            'title' => 'Test Post',
            'body' => 'Test content',
        ]);

        // ❌ STAFF est disallowed pour TestPost
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Role "staff" is disallowed for');

        $this->service->attach($disallowedUser, $post, Role::STAFF);
    }

    public function test_attach_with_allowed_role_on_allowed_target_works(): void
    {
        $disallowedUser = TestDisallowedUser::create([
            'name' => 'Disallowed User',
            'email' => 'disallowed@example.com',
        ]);

        $post = TestPost::create([
            'user_id' => $this->user->id,
            'title' => 'Test Post',
            'body' => 'Test content',
        ]);

        $post2 = TestPost::create([
            'user_id' => $this->user->id,
            'title' => 'Second Post',
            'body' => 'Test content',
        ]);

        // ✅ DOCTOR est autorisé pour TestPost
        $attachment = $this->service->attach($disallowedUser, $post, Role::DOCTOR);
        $this->assertInstanceOf(Rattachment::class, $attachment);

        // ✅ ADMIN est autorisé pour TestPost (post différent)
        $attachment2 = $this->service->attach($disallowedUser, $post2, Role::ADMIN);
        $this->assertInstanceOf(Rattachment::class, $attachment2);
    }

    public function test_attach_with_disallowed_role_on_disallowed_target_throws_disallowed_target_exception_first(): void
    {
        $disallowedUser = TestDisallowedUser::create([
            'name' => 'Disallowed User',
            'email' => 'disallowed@example.com',
        ]);

        $checkpoint = TestCheckPoint::create([
            'name' => 'Test Checkpoint',
            'location' => 'Test Location',
            'is_active' => true,
        ]);

        // ❌ Checkpoint est totalement disallowed ([]), priorité sur le rôle
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('cannot be attached to');

        $this->service->attach($disallowedUser, $checkpoint, Role::STAFF);
    }

    public function test_disallowed_role_on_user_target_throws_exception(): void
    {
        $disallowedUser = TestDisallowedUser::create([
            'name' => 'Disallowed User',
            'email' => 'disallowed@example.com',
        ]);

        $user2 = TestUser::create([
            'name' => 'Jane Doe',
            'email' => 'jane@example.com',
        ]);

        // ❌ ADMIN est disallowed pour TestUser
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Role "admin" is disallowed for');

        $this->service->attach($disallowedUser, $user2, Role::ADMIN);
    }

    public function test_attach_with_allowed_role_on_user_target_works(): void
    {
        $disallowedUser = TestDisallowedUser::create([
            'name' => 'Disallowed User',
            'email' => 'disallowed@example.com',
        ]);

        $user2 = TestUser::create([
            'name' => 'Jane Doe',
            'email' => 'jane@example.com',
        ]);

        // ✅ DOCTOR est autorisé pour TestUser
        $attachment = $this->service->attach($disallowedUser, $user2, Role::DOCTOR);
        $this->assertInstanceOf(Rattachment::class, $attachment);
    }
}
