<?php

namespace BiiiiiigMonster\Aop\Concerns;

use BiiiiiigMonster\Aop\AspectHandler;
use Closure;

trait AopTrait
{
    /**
     * @param array $pipes
     * @return mixed
     */
    public static function __pipeline(array $pipes): Closure
    {
        $target = fn(JoinPoint $joinPoint) => $joinPoint->invokeTarget();

        $through =
            fn(Closure $pipeline, object $pipe) =>
                fn(JoinPoint $joinPoint) =>
                    (new AspectHandler)($joinPoint->setPipeline($pipeline)->setAspect($pipe));

        return array_reduce($pipes, $through, $target);
    }
}
