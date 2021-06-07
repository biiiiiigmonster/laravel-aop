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
        $kernel = static fn(Pointer $pointer) => $pointer->kernel();

        $growing = fn(Closure $stack, array $skin) => fn(Pointer $pointer) => (new AspectHandler())($pointer->into($skin), $stack);

        return array_reduce(array_reverse($skins), $growing, $kernel);
    }
}
