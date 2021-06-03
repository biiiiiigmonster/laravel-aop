<?php

namespace BiiiiiigMonster\Aop\Concerns;

use BiiiiiigMonster\Aop\Aop;
use BiiiiiigMonster\Aop\Pointer\FunctionPointer;
use Closure;
use ReflectionException;
use ReflectionMethod;

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
     * @param array $argsMap
     * @param Closure $closure
     * @return mixed
     * @throws ReflectionException
     */
    public static function __proxyCall(string $className, string $method, array $arguments,array $variadicArguments, array $argsMap, Closure $closure): mixed
    {
        $onion = self::__onion(Aop::get($className, $method));
        $pointer = new FunctionPointer($className, $method, $arguments, $variadicArguments, $argsMap, $closure);

        return $onion($pointer);
    }

    /**
     * @param string $className
     * @param string $method
     * @param array $func_get_args
     * @param array $variadic_args
     * @return array
     * @throws ReflectionException
     */
    public static function __proxyArgsMap(string $className, string $method, array $func_get_args, array $variadic_args): array
    {
        $argsMap = [];
        $methodRfc = new ReflectionMethod($className, $method);
        foreach ($methodRfc->getParameters() as $parameter) {
            if (!$parameter->isVariadic()) {
                $argsMap[$parameter->getName()] = array_shift($func_get_args) ?? $parameter->getDefaultValue();
            } else {
                $remainder = $func_get_args;
                foreach ($variadic_args as $named => $value) {
                    if (is_string($named)) {
                        $remainder[$named] = $value;
                    }
                }
                $argsMap[$parameter->getName()] = $remainder;
            }
        }

        return $argsMap;
    }
}
