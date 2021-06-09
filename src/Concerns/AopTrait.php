<?php

namespace BiiiiiigMonster\Aop\Concerns;

use BiiiiiigMonster\Aop\AspectHandler;
use Closure;

trait AopTrait
{
    /**
     * @param array $skins
     * @return mixed
     */
    public static function __onion(array $skins): Closure
    {
        $kernel = static fn(Pointer $pointer) => $pointer->process();

        $through = fn(Closure $stack, array $skin) => function (Pointer $pointer) use ($stack, $skin) {
            $aspectHandler = new AspectHandler();
            $pointer->into($skin);
            $pointer->setHandler($aspectHandler);
            $pointer->setTarget($stack);
            $aspectHandler($pointer);
        };

        return array_reduce($skins, $through, $kernel);
    }
}
