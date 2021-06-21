<?php

namespace BiiiiiigMonster\Aop\Visitors;

use BiiiiiigMonster\Aop\Attributes\Aspect;
use BiiiiiigMonster\Aop\Concerns\FunctionTrait;
use PhpParser\Node;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\TraitUse;
use PhpParser\NodeVisitorAbstract;

class ClassVisitor extends NodeVisitorAbstract
{
    /**
     * Aspect classname
     */
    public const ASPECT = Aspect::class;

    /**
     * Class namespace
     * @var string
     */
    private string $namespace = '';

    /**
     * Class name
     * @var string
     */
    private string $className = '';

    /**
     * Class uses array
     * @var array
     */
    private array $uses = [];

    /**
     * Class attributes array
     * @var array
     */
    private array $attributes = [];

    /**
     * Class is interface.
     * @var bool
     */
    private bool $interface = false;

    /**
     * @return string
     */
    public function getClass(): string
    {
        return sprintf('%s\\%s', $this->namespace, $this->className);
    }

    /**
     * @return bool
     */
    public function isInterface(): bool
    {
        return $this->interface;
    }

    /**
     * @return bool
     */
    public function isAspect(): bool
    {
        foreach ($this->uses as $alias => $use) {
            if ($use !== self::ASPECT) continue;
            foreach ($this->attributes as $attribute) {
                if ($attribute === $alias || str_ends_with($use, $attribute)) {
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
            $this->className = $node->name->toString();
        } elseif ($node instanceof Node\Stmt\Interface_) {
            $this->interface = true;
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
