<?php

declare(strict_types=1);

namespace AndyDefer\LaravelRattachments\Tests\Integration\Services\Visitors;

use AndyDefer\LaravelRattachments\Services\Visitors\ConstraintModelVisitor;
use AndyDefer\LaravelRattachments\Tests\Fixtures\CodeSnippets\ConstraintModelSnippets;
use AndyDefer\LaravelRattachments\Tests\Fixtures\Models\TestConstrainedUser;
use AndyDefer\LaravelRattachments\Tests\Fixtures\Models\TestPlainUser;
use AndyDefer\LaravelRattachments\Tests\IntegrationTestCase;
use PhpParser\NodeTraverser;
use PhpParser\Parser;
use PhpParser\ParserFactory;

final class ConstraintModelVisitorTest extends IntegrationTestCase
{
    private Parser $parser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->parser = (new ParserFactory)->createForNewestSupportedVersion();
    }

    public function test_visitor_discovers_model_implementing_constraint_interface(): void
    {
        $content = ConstraintModelSnippets::CONSTRAINED_MODEL;
        $ast = $this->parser->parse($content);
        $visitor = new ConstraintModelVisitor;
        $traverser = new NodeTraverser;
        $traverser->addVisitor($visitor);

        $traverser->traverse($ast);
        $models = $visitor->getModels();

        $this->assertContains('App\\Models\\ConstrainedUser', $models);
    }

    public function test_visitor_ignores_model_not_implementing_constraint_interface(): void
    {
        $content = ConstraintModelSnippets::UNCONSTRAINED_MODEL;
        $ast = $this->parser->parse($content);
        $visitor = new ConstraintModelVisitor;
        $traverser = new NodeTraverser;
        $traverser->addVisitor($visitor);

        $traverser->traverse($ast);
        $models = $visitor->getModels();

        $this->assertEmpty($models);
    }

    public function test_visitor_discovers_model_with_interface_alias(): void
    {
        $content = ConstraintModelSnippets::CONSTRAINED_MODEL_WITH_ALIAS;
        $ast = $this->parser->parse($content);
        $visitor = new ConstraintModelVisitor;
        $traverser = new NodeTraverser;
        $traverser->addVisitor($visitor);

        $traverser->traverse($ast);
        $models = $visitor->getModels();

        $this->assertContains('App\\Models\\ConstrainedUser', $models);
    }

    public function test_visitor_ignores_abstract_constrained_class(): void
    {
        $content = ConstraintModelSnippets::ABSTRACT_CONSTRAINED_MODEL;
        $ast = $this->parser->parse($content);
        $visitor = new ConstraintModelVisitor;
        $traverser = new NodeTraverser;
        $traverser->addVisitor($visitor);

        $traverser->traverse($ast);
        $models = $visitor->getModels();

        $this->assertNotContains('App\\Models\\AbstractConstrainedModel', $models);
    }

    public function test_visitor_discovers_multiple_constrained_models(): void
    {
        $content = ConstraintModelSnippets::MULTIPLE_CONSTRAINED_MODELS;
        $ast = $this->parser->parse($content);
        $visitor = new ConstraintModelVisitor;
        $traverser = new NodeTraverser;
        $traverser->addVisitor($visitor);

        $traverser->traverse($ast);
        $models = $visitor->getModels();

        $this->assertContains('App\\Models\\ConstrainedUser', $models);
        $this->assertContains('App\\Models\\ConstrainedPost', $models);
        $this->assertCount(2, $models);
    }

    public function test_visitor_handles_nested_namespace(): void
    {
        $content = ConstraintModelSnippets::NESTED_NAMESPACE_CONSTRAINED_MODEL;
        $ast = $this->parser->parse($content);
        $visitor = new ConstraintModelVisitor;
        $traverser = new NodeTraverser;
        $traverser->addVisitor($visitor);

        $traverser->traverse($ast);
        $models = $visitor->getModels();

        $this->assertContains('App\\Models\\Users\\ConstrainedUser', $models);
    }

    public function test_visitor_discovers_real_constrained_model(): void
    {
        $path = __DIR__.'/../../../Fixtures/Models/TestConstrainedUser.php';
        $content = file_get_contents($path);

        $ast = $this->parser->parse($content);
        $visitor = new ConstraintModelVisitor;
        $traverser = new NodeTraverser;
        $traverser->addVisitor($visitor);

        $traverser->traverse($ast);
        $models = $visitor->getModels();

        $this->assertContains(TestConstrainedUser::class, $models);
    }

    public function test_visitor_ignores_non_constrained_real_model(): void
    {
        $path = __DIR__.'/../../../Fixtures/Models/TestPlainUser.php';
        $content = file_get_contents($path);

        $ast = $this->parser->parse($content);
        $visitor = new ConstraintModelVisitor;
        $traverser = new NodeTraverser;
        $traverser->addVisitor($visitor);

        $traverser->traverse($ast);
        $models = $visitor->getModels();

        $this->assertNotContains(TestPlainUser::class, $models);
    }
}
