<?php

namespace BiiiiiigMonster\Aop\Points;

use BiiiiiigMonster\Aop\Aop;
use BiiiiiigMonster\Aop\AspectHandler;
use BiiiiiigMonster\Aop\Concerns\JoinPoint;
use Closure;
use ReflectionException;
use ReflectionMethod;
use ReflectionNamedType;
use ReflectionUnionType;
use Throwable;

class ProceedingJoinPoint extends JoinPoint
{
    private array $argsMap;
    private ?array $param = null;

    /**
     * JoinPoint constructor.
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
        $this->returnTypes = $this->parseReturnType($methodRfc);
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
     * @return array|null
     */
    public function getParam(): ?array
    {
        return $this->param;
    }

    /**
     * @param array|null $param
     */
    protected function setParam(?array $param): void
    {
        $this->param = $param;
    }

    /**
     * Parse the return types by the "ReflectionMethod".
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

        return array_unique($types);
    }

    /**
     * Parse the arguments map by the "ReflectionMethod".
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
                $tem = $func_get_args;
                $value = array_shift($func_get_args);
                $argsMap[$parameter->getName()] = $tem === $func_get_args ? $parameter->getDefaultValue() : $value;
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
     * @return object|null
     */
    public function getAttributeInstance(): ?object
    {
        return Aop::getAttributeMapping($this->className, $this->method, $this->aspect::class);
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
     * invoke the original method
     * @return mixed
     */
    public function invokeTarget(): mixed
    {
        // call target.
        $target = $this->target;
        $args = $this->getParam() ?? $this->getOriginalArguments();

        return $target(...$args);
    }

    /**
     * Process the original method, this method should trigger by pipeline.
     *
     * @param array|null $param
     * @return mixed
     * @throws ReflectionException
     * @throws Throwable
     */
    public function process(?array $param = null): mixed
    {
        $this->setParam($param);
        $aspectInstance = $this->getAspect();
        if ($aspectInstance) {
            [$before, , $after, $afterThrowing, $afterReturning] = AspectHandler::getAspectAdvices($aspectInstance::class);
        }

        // Execute "Before"
        if (isset($before)) {
            $before->invoke($aspectInstance, $this);
        }
        // Execute the next pipeline.
        try {
            $closure = $this->pipeline;
            $this->setReturn($closure($this));
        } catch (Throwable $throwable) {
            // Set "Throwable"
            $this->setThrowable($throwable);
        }
        // The next pipeline will change the cur aspect, so reset it after the pipeline execute complete.
        $this->setAspect($aspectInstance);

        //  Execute "AfterThrowing" if pipeline has throwable
        if ($this->getThrowable()) {
            // Execute "AfterThrowing"
            if (isset($afterThrowing)) {
                $afterThrowing->invoke($aspectInstance, $this->getThrowable());
            }
        } else {
            // Execute "AfterReturning"
            if (isset($afterReturning)) {
                $this->setReturn(
                    $afterReturning->invoke($aspectInstance, $this)
                );
            }
        }
        // Execute "After"
        if (isset($after)) {
            $after->invoke($aspectInstance, $this);
        }
        // Throw exception
        if ($this->getThrowable()) {
            throw $this->getThrowable();
        }
        // Or return.
        return $this->getReturn();
    }
}
