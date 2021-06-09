<?php

namespace BiiiiiigMonster\Aop\Concerns;

use BiiiiiigMonster\Aop\Aop;
use BiiiiiigMonster\Aop\Point\FunctionPointer;
use Closure;
use ReflectionException;

trait FunctionTrait
{
    use AopTrait;

    /**
     * Proxy call
     *
     * @param string $className
     * @param string $method
     * @param array $arguments
     * @param array $variadicArguments
     * @param Closure $target
     * @return mixed
     * @throws ReflectionException
     */
    public static function __proxyCall(string $className, string $method, array $arguments,array $variadicArguments, Closure $target): mixed
    {
        $onion = self::__onion(Aop::get($className, $method));
        $pointer = new FunctionPointer($className, $method, $arguments, $variadicArguments, $target);

        return $onion($pointer);
    }
}
