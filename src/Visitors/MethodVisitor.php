<?php

namespace BiiiiiigMonster\Aop\Visitors;

use PhpParser\Node;
use PhpParser\Node\Arg;
use PhpParser\Node\Expr\Assign;
use PhpParser\Node\Expr\Closure;
use PhpParser\Node\Expr\StaticCall;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Identifier;
use PhpParser\Node\Name;
use PhpParser\Node\Scalar\MagicConst\Class_ as MagicConstClass;
use PhpParser\Node\Scalar\MagicConst\Function_ as MagicConstFunction;
use PhpParser\Node\Scalar\MagicConst\Method as MagicConstMethod;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\Node\Stmt\Expression;
use PhpParser\Node\Stmt\Return_;
use PhpParser\NodeVisitorAbstract;

class MethodVisitor extends NodeVisitorAbstract
{
    private ?Variable $magicConstFunction = null;
    private ?Variable $magicConstMethod = null;

    /**
     * @param Node $node
     * @return Node
     */
    public function leaveNode(Node $node): Node
    {
        if ($node instanceof ClassMethod) {
            if (
                $node->isStatic()
                || $node->isMagic()
                || $node->isPrivate()
                || $node->isAbstract()
            ) {
                return $node;
            }
            // Rewrite the method to proxy call method.
            return $this->rewriteMethod($node);
        } elseif ($node instanceof MagicConstFunction) {
            // Rewrite __FUNCTION__ to $__function__ variable.
            return $this->magicConstFunction = new Variable('__function__');
        } elseif ($node instanceof MagicConstMethod) {
            // Rewrite __METHOD__ to $__method__ variable.
            return $this->magicConstMethod = new Variable('__method__');
        } else {
            return $node;
        }
    }

    /**
     * Rewrite a normal class method to a proxy call method,
     * include normal class method and static method.
     * @param ClassMethod $node
     * @return ClassMethod
     */
    private function rewriteMethod(ClassMethod $node): ClassMethod
    {
        $args = [];
        foreach ($node->getParams() as $param) {
            $arg = new Variable($param->var->name);
            if ($param->variadic) {
                $arg = new Node\Param($arg, variadic: true);
            }
            $args[] = $arg;
        }
        array_unshift($args, ...[
            // __CLASS__
            new Arg(new MagicConstClass()),
            // __FUNCTION__
            new Arg(new MagicConstFunction()),
            // A closure that wrapped original method code.
            new Arg(new Variable('__target__')),
        ]);

        $staticCall = new StaticCall(new Name('self'), '__proxyCall', $args);

        $stmts = $this->methodAssign($node);
        $returnType = $node->getReturnType();
        $stmts[] = $returnType instanceof Identifier && $returnType->name === 'void'
            ? new Expression($staticCall)
            : new Return_($staticCall);

        $node->stmts = $stmts;
        return $node;
    }

    /**
     * @param ClassMethod $node
     * @return Expression[]
     */
    private function methodAssign(ClassMethod $node): array
    {
        /** @var ?array $closureUses */
        $closureUses = null;
        if ($this->magicConstFunction) {
            $magicConstFunction = new Expression(new Assign(new Variable('__function__'), new MagicConstFunction()));
            $closureUses[] = new Variable('__function__');
            $this->magicConstFunction = null;
        }
        if ($this->magicConstMethod) {
            $magicConstMethod = new Expression(new Assign(new Variable('__method__'), new MagicConstMethod()));
            $closureUses[] = new Variable('__method__');
            $this->magicConstMethod = null;
        }
        // Create Original Target
        $target = new Expression(new Assign(new Variable('__target__'), new Closure([
            'params' => $node->getParams(),
            'uses' => $closureUses,
            'stmts' => $node->stmts,
            'returnType' => $node->getReturnType()
        ])));

        $return = [$target];
        if (isset($magicConstMethod)) {
            array_unshift($return, $magicConstMethod);
        }
        if (isset($magicConstFunction)) {
            array_unshift($return, $magicConstFunction);
        }

        return $return;
    }
}
