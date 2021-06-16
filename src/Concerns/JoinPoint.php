<?php

namespace BiiiiiigMonster\Aop\Concerns;

use Closure;
use Throwable;

abstract class JoinPoint
{
    protected Closure $pipeline;// pipeline is a wrapped target with closure, pipeline's kernel is the target!
    protected mixed $target;// When ProceedingJoinPoint, it's a closure; When ParameterJoinPoint, it's a value;
    protected mixed $return;
    protected array $returnTypes;
    protected ?Throwable $throwable = null;
    protected object $curAspectInstance;// current Aspect Instance
    protected ?object $curAttributeInstance = null;// current Attribute Instance

    /**
     * @return mixed
     */
    public function getReturn(): mixed
    {
        return $this->return;
    }

    /**
     * @param mixed $return
     */
    public function setReturn(mixed $return): void
    {
        $this->return = $return;
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
     */
    public function setThrowable(?Throwable $throwable): void
    {
        $this->throwable = $throwable;
    }

    /**
     * @return object
     */
    public function getCurAspectInstance(): object
    {
        return $this->curAspectInstance;
    }

    /**
     * @return object|null
     */
    public function getCurAttributeInstance(): ?object
    {
        return $this->curAttributeInstance;
    }

    /**
     * @param Closure $pipeline
     */
    public function setPipeline(Closure $pipeline): void
    {
        $this->pipeline = $pipeline;
    }

    /**
     * @param array $pipe [AspectInstance, ?AttributeInstance]
     * @return JoinPoint
     */
    public function through(array $pipe): static
    {
        $this->curAspectInstance = $pipe[0];
        $this->curAttributeInstance = $pipe[1] ?? null;

        return $this;
    }

    /**
     * Process the original target.
     * @return mixed
     */
    abstract public function invokeTarget(): mixed;

    /**
     * This method should trigger by pipeline.
     * @return mixed
     */
    abstract public function process(): mixed;
}
