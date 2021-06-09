<?php

namespace BiiiiiigMonster\Aop\Concerns;

use BiiiiiigMonster\Aop\AspectHandler;
use Closure;
use Throwable;

abstract class Pointer
{
    protected Closure $target;
    protected mixed $return;
    protected array $returnTypes;
    protected ?Throwable $throwable = null;
    protected ?object $curAspectInstance = null;// 当前所处切面实例
    protected ?object $curAttributeInstance = null;// 当前所处注解实例

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
     * @param array $returnTypes
     */
    public function setReturnTypes(array $returnTypes): void
    {
        $this->returnTypes = $returnTypes;
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
     * @return object|null
     */
    public function getCurAspectInstance(): ?object
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
     * @param Closure $target
     */
    public function setTarget(Closure $target): void
    {
        $this->target = $target;
    }

    /**
     * @param array $skin
     * @return Pointer
     */
    public function into(array $skin): static
    {
        $this->curAspectInstance = $skin[0] ?? null;
        $this->curAttributeInstance = $skin[1] ?? null;

        return $this;
    }

    /**
     * Process the original method, this method should trigger by pipeline.
     * @param array|null $params
     * @return mixed
     */
    abstract public function process(?array $params = null): mixed;
}
