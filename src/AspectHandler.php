<?php

namespace BiiiiiigMonster\Aop;

use BiiiiiigMonster\Aop\Attributes\After;
use BiiiiiigMonster\Aop\Attributes\AfterReturning;
use BiiiiiigMonster\Aop\Attributes\AfterThrowing;
use BiiiiiigMonster\Aop\Attributes\Around;
use BiiiiiigMonster\Aop\Attributes\Before;
use BiiiiiigMonster\Aop\Concerns\JoinPoint;
use ReflectionClass;
use ReflectionMethod;

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
     * @throws \ReflectionException
     */
    public function __invoke(JoinPoint $joinPoint): mixed
    {
        $aspectInstance = $joinPoint->getAspect();
        [, $around] = self::getAspectAdvices($aspectInstance::class);

        // Execute "Around"
        return $around ? $around->invoke($aspectInstance, $joinPoint) : $joinPoint->process();
    }

    /**
     * @param string $aspectClassName
     * @return ReflectionMethod[]
     * @throws \ReflectionException
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
