<?php

namespace BiiiiiigMonster\Aop\Concerns;

use BiiiiiigMonster\Aop\Aop;
use BiiiiiigMonster\Aop\Exceptions\AopException;
use BiiiiiigMonster\Aop\Points\ProceedingJoinPoint;
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
     * @throws AopException
     */
    public static function __proxyCall(string $className, string $method, array $arguments, array $variadicArguments, Closure $target): mixed
    {
        $pipeline = self::__pipeline(Aop::getAspectMapping($className, $method));
        $joinPoint = new ProceedingJoinPoint($className, $method, $arguments, $variadicArguments, $target);

        return $pipeline($joinPoint);
    }
}
