<?php

namespace BiiiiiigMonster\Aop;

use BiiiiiigMonster\Aop\Attributes\After;
use BiiiiiigMonster\Aop\Attributes\AfterReturning;
use BiiiiiigMonster\Aop\Attributes\Around;
use BiiiiiigMonster\Aop\Attributes\Before;
use BiiiiiigMonster\Aop\Concerns\Pointer;
use Closure;
use ReflectionClass;
use ReflectionException;
use ReflectionMethod;

class AspectHandler
{
    /**
     * Aspect handler advices cache
     * @var array
     */
    private static array $advices = [];

    /**
     * @param Pointer $pointer
     * @param Closure $stack
     * @return mixed
     * @throws ReflectionException
     */
    public function __invoke(Pointer $pointer, Closure $stack): mixed
    {
        $aspectInstance = $pointer->getCurAspectInstance();
        [$before, $around, $after, $afterReturning] = self::getAspectAdvices($aspectInstance::class);

        // Execute Before
        if ($before) $before->invoke($aspectInstance, $pointer);
        // Execute Around
        $pointer->setReturn(
            $around ? $around->invoke($aspectInstance, $pointer, $stack) : $stack($pointer)
        );
        // Execute After
        if ($after) $after->invoke($aspectInstance, $pointer);
        // Execute AfterReturning
        if ($afterReturning) $pointer->setReturn($afterReturning->invoke($aspectInstance, $pointer));

        return $pointer->getReturn();
    }

    /**
     * @param string $aspectClassName
     * @param string|null $advice
     * @return ReflectionMethod[]
     * @throws ReflectionException
     */
    public static function getAspectAdvices(string $aspectClassName, ?string $advice = null): array
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
                } else if (!empty($method->getAttributes(AfterReturning::class))) {
                    $afterReturning = $method;
                }
            }
            self::$advices[$aspectClassName] = [
                'before' => $before ?? null,
                'around' => $around ?? null,
                'after' => $after ?? null,
                'afterReturning' => $afterReturning ?? null
            ];
        }
        $advices = self::$advices[$aspectClassName];

        return $advices[$advice] ?? array_values($advices);
    }
}
