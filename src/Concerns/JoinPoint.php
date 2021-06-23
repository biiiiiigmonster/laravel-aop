<?php

namespace BiiiiiigMonster\Aop\Concerns;

use BiiiiiigMonster\Aop\Aop;
use Closure;
use Throwable;

abstract class JoinPoint
{
    protected mixed $target;// When ProceedingJoinPoint, it's a closure; When ParameterJoinPoint, it's a value;
    protected mixed $return;
    protected array $returnTypes;
    protected ?Throwable $throwable = null;
    protected ?Closure $pipeline = null;// pipeline is a wrapped target with closure, pipeline's kernel is the target!
    protected ?object $aspect = null;

    /**
     * @return mixed
     */
    public function getReturn(): mixed
    {
        return $this->return;
    }

    /**
     * @param mixed $return
     * @return JoinPoint
     */
    protected function setReturn(mixed $return): static
    {
        $this->return = $return;

        return $this;
    }

    /**
     * @return array
     */
    public function getReturnTypes(): array
    {
        return $this->returnTypes;
    }

    /**
     * @return Throwable|null
     */
    public function getThrowable(): ?Throwable
    {
        return $this->throwable;
    }

    /**
     * @param Throwable|null $throwable
     * @return JoinPoint
     */
    protected function setThrowable(?Throwable $throwable): static
    {
        $this->throwable = $throwable;

        return $this;
    }

    /**
     * Get the joinPoint attribute instance.
     * @return object|null
     */
    abstract public function getAttributeInstance(): ?object;

    /**
     * @return object|null
     */
    public function getAspect(): ?object
    {
        return $this->aspect;
    }

    /**
     * @param object|null $aspect
     * @return JoinPoint
     */
    protected function setAspect(?object $aspect): static
    {
        $this->aspect = $aspect;

        return $this;
    }

    /**
     * @param Closure $pipeline
     * @return JoinPoint
     */
    public function setPipeline(Closure $pipeline): static
    {
        $this->pipeline = $pipeline;

        return $this;
    }

    /**
     * Process the original target.
     * @return mixed
     */
    abstract public function invokeTarget(): mixed;

    /**
     * This method should trigger by pipeline.
     *
     * @param array|null $param Override the original arguments, if give.
     * @return mixed
     */
    abstract public function process(?array $param = null): mixed;
}
