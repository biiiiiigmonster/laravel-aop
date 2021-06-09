<?php

namespace BiiiiiigMonster\Aop\Point;

use BiiiiiigMonster\Aop\AspectHandler;
use BiiiiiigMonster\Aop\Concerns\Pointer;
use Closure;
use ReflectionException;
use ReflectionMethod;
use ReflectionNamedType;
use ReflectionUnionType;

class FunctionPointer extends Pointer
{
    private array $argsMap;

    /**
     * Pointer constructor.
     *
     * @param string $className
     * @param string $method
     * @param array $arguments
     * @param array $variadicArguments
     * @param Closure $target
     * @throws ReflectionException
     */
    public function __construct(
        private string $className,
        private string $method,
        private array $arguments,// func_get_args() can't get named arguments in variadic parameter.
        private array $variadicArguments,// maybe contain named arguments, if give.
        Closure $target,
    )
    {
        $this->target = $target;
        // Parse ReflectionMethod Data.
        $methodRfc = new ReflectionMethod($className, $method);
        $this->argsMap = $this->parseArgsMap($methodRfc);
        $this->types = $this->parseReturnType($methodRfc);
    }

    /**
     * @return string
     */
    public function getClassName(): string
    {
        return $this->className;
    }

    /**
     * @return string
     */
    public function getMethod(): string
    {
        return $this->method;
    }

    /**
     * @return array
     */
    public function getArguments(): array
    {
        return $this->arguments;
    }

    /**
     * @param ReflectionMethod $reflectionMethod
     * @return array
     */
    protected function parseReturnType(ReflectionMethod $reflectionMethod): array
    {
        $types = [];
        $returnType = $reflectionMethod->getReturnType();
        if ($returnType instanceof ReflectionUnionType) {
            foreach ($returnType->getTypes() as $returnNamedType) {
                $types[] = $returnNamedType->getName();
            }
        } elseif ($returnType instanceof ReflectionNamedType) {
            if ($returnType->allowsNull()) {
                $types[] = 'null';
            }
            $types[] = $returnType->getName();
        } else {
            $types[] = 'void';
        }

        return $types;
    }

    /**
     * @param ReflectionMethod $reflectionMethod
     * @return array
     * @throws ReflectionException
     */
    protected function parseArgsMap(ReflectionMethod $reflectionMethod): array
    {
        $func_get_args = $this->getArguments();
        $variadic_args = $this->getVariadicArguments();
        $argsMap = [];
        foreach ($reflectionMethod->getParameters() as $parameter) {
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

    /**
     * @return array
     */
    public function getArgsMap(): array
    {
        return $this->argsMap;
    }

    /**
     * @return array
     */
    public function getVariadicArguments(): array
    {
        return $this->variadicArguments;
    }

    /**
     * Get the original parameter contains func_get_args() & variadic named arguments.
     * @return array
     */
    protected function getOriginalArguments(): array
    {
        $args = $this->getArguments();
        foreach ($this->getVariadicArguments() as $named => $argument) {
            if (is_string($named)) {
                $args[$named] = $argument;
            }
        }

        return $args;
    }

    /**
     * Process the original method, this method should trigger by pipeline.
     * @param array|null $params
     * @return mixed
     * @throws ReflectionException
     */
    public function process(?array $params = null): mixed
    {
        // Before Advice.
        [$before] = AspectHandler::getAspectAdvices($this->curAttributeInstance::class);
        if ($before) $before->invoke($this->curAttributeInstance, $this);

        // call target.
        $target = $this->target;
        // Support customize cover the original parameter.
        $args = $params ?? $this->getOriginalArguments();
        return $target(...$args);
    }
}