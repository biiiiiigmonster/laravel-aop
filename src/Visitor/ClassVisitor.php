<?php

namespace BiiiiiigMonster\Aop\Visitor;

use BiiiiiigMonster\Aop\Attributes\Aspect;
use BiiiiiigMonster\Aop\Concerns\FunctionTrait;
use PhpParser\Node;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\TraitUse;
use PhpParser\NodeVisitorAbstract;

class ClassVisitor extends NodeVisitorAbstract
{
    const ASPECT = Aspect::class;
    private string $namespace = '';
    private string $originalName = '';
    private array $uses = [];
    private array $attributes = [];

    /**
     * @return string
     */
    public function getOriginalClassName(): string
    {
        return sprintf('%s\\%s', $this->namespace, $this->originalName);
    }

    /**
     * @return bool
     */
    public function isAspect(): bool
    {
        foreach ($this->attributes as $attribute) {
            foreach ($this->uses as $alias => $use) {
                if ($use !== self::ASPECT) continue;
                $useArr = explode('\\', $use);
                if ($attribute === $alias || $attribute === end($useArr)) {
                    return true;
                }
            }
        }
        return false;
    }

    /**
     * Enter node
     *
     * @param Node $node
     * @return Node
     */
    public function enterNode(Node $node): Node
    {
        if ($node instanceof Node\Stmt\Use_) {
            foreach ($node->uses as $use) {
                $use->alias
                    ? $this->uses[$use->alias->name] = $use->name->toString()
                    : $this->uses[] = $use->name->toString();
            }
        } elseif ($node instanceof Node\Attribute) {
            $this->attributes[] = $node->name->toString();
        } elseif ($node instanceof Node\Stmt\Namespace_) {
            $this->namespace = $node->name->toString();
        } elseif ($node instanceof Node\Stmt\Class_) {
            $this->originalName = $node->name->toString();
        }
        return $node;
    }

    /**
     * @param Node $node
     * @return Node
     */
    public function leaveNode(Node $node): Node
    {
        if (
            $node instanceof Node\Stmt\Trait_
            || ($node instanceof Class_ && !$node->isAnonymous())
        ) {
            array_unshift($node->stmts, new TraitUse([
                new Node\Name('\\' . FunctionTrait::class)
            ]));
        }

        return $node;
    }
}
