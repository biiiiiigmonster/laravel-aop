<?php

namespace BiiiiiigMonster\Aop\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS)]
final class Aspect
{
    /**
     * Aop constructor.
     * @param string $pointcut
     * @param int $order
     */
    public function __construct(
        /**
         * 数组内参数格式支持以下三种
         * @example [
         *      App\\Http\\UserController::class,
         *      'App\\Http\\PostController::index',
         *      'App\\Http\\CommentController::get*',
         * ]
         */
        public string $pointcut,
        public int $order = 0,
    )
    {
    }
}
