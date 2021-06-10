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
        $target = static fn(JoinPoint $joinPoint) => $joinPoint->process();

        $through = fn(Closure $pipeline, array $pipe) => function (JoinPoint $joinPoint) use ($pipeline, $pipe) {
            $aspectHandler = new AspectHandler();
            $joinPoint->through($pipe);
            $joinPoint->setPipeline($pipeline);
            return $aspectHandler($joinPoint);
        };

        return array_reduce($pipes, $through, $target);
    }
}
