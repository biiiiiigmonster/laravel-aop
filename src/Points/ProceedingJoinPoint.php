<?php

namespace BiiiiiigMonster\Aop\Points;

use BiiiiiigMonster\Aop\Aop;
use BiiiiiigMonster\Aop\AspectHandler;
use BiiiiiigMonster\Aop\Concerns\JoinPoint;
use Closure;
use ReflectionMethod;
use ReflectionNamedType;
use ReflectionUnionType;
use Throwable;

class ProceedingJoinPoint extends JoinPoint
{
    private array $argsMap;
    private ?array $param = null;
    private array $args;

    /**
     * JoinPoint constructor.
     *
     * @param string $className
     * @param string $method
     * @param Closure $target
     * @param mixed ...$args
     * @throws \ReflectionException
     */
    public function __construct(
        private string $className,
        private string $method,
        Closure $target,
        &...$args,
    )
    {
        $this->target = $target;
        $this->args = $args;
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
    public function getArgs(): array
    {
        return $this->args;
    }

    /**
     * @return array
     */
    public function getArgsMap(): array
    {
        return $this->argsMap;
    }

    /**
     * @return array|null
     */
    public function getParam(): ?array
    {
        return $this->param;
    }

    /**
     * Parse the arguments map by the "ReflectionMethod".
     * @param ReflectionMethod $reflectionMethod
     * @return array
     * @throws \ReflectionException
     */
    protected function parseArgsMap(ReflectionMethod $reflectionMethod): array
    {
        $args = $this->getArgs();
        $argsMap = [];
        foreach ($reflectionMethod->getParameters() as $parameter) {
            // variadic param is end.
            if ($parameter->isVariadic()) {
                $argsMap[$parameter->getName()] = $args;
                break;
            }
            $tem = $args;
            $value = array_shift($args);
            $argsMap[$parameter->getName()] = $tem === $args ? $parameter->getDefaultValue() : $value;
        }

        return $argsMap;
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
     * @return object|null
     */
    public function getAttributeInstance(): ?object
    {
        return Aop::getAttributeMapping($this->className, $this->method, $this->aspect::class);
    }

    /**
     * invoke the original method
     * @return mixed
     */
    public function invokeTarget(): mixed
    {
        // call target.
        $target = $this->target;
        $args = $this->getParam() ?? $this->getArgs();

        return $target(...$args);
    }

    /**
     * Process the original method, this method should trigger by pipeline.
     *
     * @param array|null $param
     * @return mixed
     * @throws \ReflectionException
     * @throws \Throwable
     */
    public function process(?array $param = null): mixed
    {
        $this->param = $param;
        $aspectInstance = $this->getAspect();
        [$before, , $after, $afterThrowing, $afterReturning] = AspectHandler::getAspectAdvices($aspectInstance::class);

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
        // The next pipeline will change the cur aspect, so reset it after the next pipeline execute complete.
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
