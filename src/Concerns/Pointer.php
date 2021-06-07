<?php

namespace BiiiiiigMonster\Aop\Concerns;


abstract class Pointer
{
    protected mixed $original;
    protected mixed $return;
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
     * @param mixed $return
     */
    public function setReturn(mixed $return): void
    {
        $this->return = $return;
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
     */
    abstract public function kernel();
}
