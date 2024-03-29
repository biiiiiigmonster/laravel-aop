<?php

namespace BiiiiiigMonster\Aop\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS | Attribute::IS_REPEATABLE)]
final class Aspect
{
    private array $pointcuts;
    private int $priority;

    /**
     * Aop constructor.
     * @param string|array $pointcuts
     * @example [
     *      App\\Http\\UserController::class,
     *      'App\\Http\\PostController::index',
     *      'App\\Http\\CommentController::get*',
     * ]
     * @param int $priority
     */
    public function __construct(string|array $pointcuts, int $priority = 0)
    {
        $this->pointcuts = (array) $pointcuts;
        $this->priority = $priority;
    }

    /**
     * @return array
     */
    public function getPointcuts(): array
    {
        return $this->pointcuts;
    }

    /**
     * @return int
     */
    public function getPriority(): int
    {
        return $this->priority;
    }
}
