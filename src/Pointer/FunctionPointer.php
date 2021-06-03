<?php

namespace BiiiiiigMonster\Aop\Pointer;

use BiiiiiigMonster\Aop\Concerns\Pointer;
use Closure;

class FunctionPointer extends Pointer
{
    /**
     * Pointer constructor.
     *
     * @param string $className
     * @param string $method
     * @param array $arguments
     * @param array $variadicArguments
     * @param array $argsMap
     * @param Closure $original
     */
    public function __construct(
        private string $className,
        private string $method,
        private array $arguments,// func_get_args() can't get named arguments in variadic parameter.
        private array $variadicArguments,// maybe contain named arguments, if give.
        private array $argsMap,
        Closure $original,
    )
    {
        $this->original = $original;
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
     * Process the original method, this method should trigger by pipeline.
     */
    public function kernel()
    {
        $closure = $this->original;
        $args = $this->getArguments();
        foreach ($this->getVariadicArguments() as $named => $argument) {
            if (is_string($named)) {
                $args[$named] = $argument;
            }
        }

        return $closure(...$args);
    }
}
