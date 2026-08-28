<?php

declare(strict_types=1);

namespace AndyDefer\LaravelRattachments\Services\Visitors;

use AndyDefer\LaravelRattachments\Contracts\RattachmentConstraintsInterface;
use PhpParser\Node;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\Namespace_;
use PhpParser\Node\Stmt\Use_;
use PhpParser\NodeVisitorAbstract;

final class ConstraintModelVisitor extends NodeVisitorAbstract
{
    private array $models = [];

    private ?string $currentNamespace = null;

    private array $aliases = [];

    public function enterNode(Node $node): ?int
    {
        if ($node instanceof Namespace_) {
            $this->currentNamespace = $node->name?->toString();

            return null;
        }

        if ($node instanceof Use_) {
            $this->registerAliases($node);

            return null;
        }

        if ($node instanceof Class_) {
            $this->processClassNode($node);

            return null;
        }

        return null;
    }

    private function registerAliases(Use_ $node): void
    {
        foreach ($node->uses as $use) {
            $alias = $use->alias !== null
                ? $use->alias->toString()
                : $use->name->getLast();

            $this->aliases[$alias] = $use->name->toString();
        }
    }

    private function processClassNode(Class_ $node): void
    {
        $className = $node->name->toString();

        if ($node->isAbstract()) {
            return;
        }

        $implementsConstraint = false;

        if ($node->implements !== null) {
            foreach ($node->implements as $implement) {
                $interfaceName = $implement->toString();
                $resolvedName = $this->resolveAlias($interfaceName);

                if ($resolvedName === RattachmentConstraintsInterface::class) {
                    $implementsConstraint = true;
                    break;
                }
            }
        }

        if ($implementsConstraint && $this->currentNamespace !== null) {
            $this->models[] = $this->currentNamespace.'\\'.$className;
        }
    }

    private function resolveAlias(string $name): string
    {
        foreach ($this->aliases as $alias => $fqcn) {
            if ($name === $alias) {
                return $fqcn;
            }
        }

        return $name;
    }

    public function getModels(): array
    {
        return $this->models;
    }
}
