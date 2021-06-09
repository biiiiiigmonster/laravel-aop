<?php

namespace BiiiiiigMonster\Aop\Concerns;

use BiiiiiigMonster\Aop\AspectHandler;
use Closure;
use Throwable;

abstract class Pointer
{
    protected AspectHandler $handler;
    protected Closure $target;
    protected mixed $value;
    protected array $types;
    protected ?Throwable $throwable = null;
    protected ?object $curAspectInstance = null;// 当前所处切面实例
    protected ?object $curAttributeInstance = null;// 当前所处注解实例

    /**
     * @return mixed
     */
    public function getValue(): mixed
    {
        return $this->value;
    }

    /**
     * @param mixed $value
     */
    public function setValue(mixed $value): void
    {
        $this->value = $value;
    }

    /**
     * @return array
     */
    public function getTypes(): array
    {
        return $this->types;
    }

    /**
     * @param array $types
     */
    public function setTypes(array $types): void
    {
        $this->types = $types;
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
     * @param AspectHandler $handler
     */
    public function setHandler(AspectHandler $handler): void
    {
        $this->handler = $handler;
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
