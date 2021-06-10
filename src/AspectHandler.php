<?php

namespace BiiiiiigMonster\Aop;

use BiiiiiigMonster\Aop\Attributes\After;
use BiiiiiigMonster\Aop\Attributes\AfterReturning;
use BiiiiiigMonster\Aop\Attributes\AfterThrowing;
use BiiiiiigMonster\Aop\Attributes\Around;
use BiiiiiigMonster\Aop\Attributes\Before;
use BiiiiiigMonster\Aop\Concerns\JoinPoint;
use Closure;
use ReflectionClass;
use ReflectionException;
use ReflectionMethod;
use Throwable;

class AspectHandler
{
    /**
     * Aspect handler advices cache
     * @var array
     */
    private static array $advices = [];

    /**
     * @param JoinPoint $joinPoint
     * @return mixed
     * @throws ReflectionException
     * @throws Throwable
     */
    public function __invoke(JoinPoint $joinPoint): mixed
    {
        $aspectInstance = $joinPoint->getCurAspectInstance();
        [$before, $around, $after, $afterThrowing, $afterReturning] = self::getAspectAdvices($aspectInstance::class);

        try {
            // Execute Around
            $joinPoint->setReturn(
                $around ? $around->invoke($aspectInstance, $joinPoint) : $joinPoint->process()
            );
        } catch (Throwable $throwable) {
            // Set Throwable
            $joinPoint->setThrowable($throwable);
        }
        // Execute After
        if ($after) $after->invoke($aspectInstance, $joinPoint);

        //  Execute AfterThrowing If kernel has throwable
        if ($joinPoint->getThrowable()) {
            // Execute AfterThrowing
            if ($afterThrowing) {
                $joinPoint->setReturn($afterThrowing->invoke($aspectInstance, $joinPoint->getThrowable(), $joinPoint));
            } else {
                throw $joinPoint->getThrowable();
            }
        } else {
            // Execute AfterReturning
            if ($afterReturning) $joinPoint->setReturn($afterReturning->invoke($aspectInstance, $joinPoint));
        }

        return $joinPoint->getReturn();
    }

    /**
     * @param string $aspectClassName
     * @return ReflectionMethod[]
     * @throws ReflectionException
     */
    public static function getAspectAdvices(string $aspectClassName): array
    {
        if (!isset(self::$advices[$aspectClassName])) {
            $aspectRfc = new ReflectionClass($aspectClassName);
            foreach ($aspectRfc->getMethods() as $method) {
                if (!empty($method->getAttributes(Before::class))) {
                    $before = $method;
                } else if (!empty($method->getAttributes(Around::class))) {
                    $around = $method;
                } else if (!empty($method->getAttributes(After::class))) {
                    $after = $method;
                } else if (!empty($method->getAttributes(AfterThrowing::class))) {
                    $afterThrowing = $method;
                } else if (!empty($method->getAttributes(AfterReturning::class))) {
                    $afterReturning = $method;
                }
            }
            self::$advices[$aspectClassName] = [
                'before' => $before ?? null,
                'around' => $around ?? null,
                'after' => $after ?? null,
                'afterThrowing' => $afterThrowing ?? null,
                'afterReturning' => $afterReturning ?? null,
            ];
        }

        return array_values(self::$advices[$aspectClassName]);
    }
}
