<?php

namespace BiiiiiigMonster\Aop;

use BiiiiiigMonster\Aop\Attributes\After;
use BiiiiiigMonster\Aop\Attributes\AfterReturning;
use BiiiiiigMonster\Aop\Attributes\AfterThrowing;
use BiiiiiigMonster\Aop\Attributes\Around;
use BiiiiiigMonster\Aop\Attributes\Before;
use BiiiiiigMonster\Aop\Concerns\Pointer;
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
     * @param Pointer $pointer
     * @return mixed
     * @throws ReflectionException
     * @throws Throwable
     */
    public function __invoke(Pointer $pointer): mixed
    {
        $aspectInstance = $pointer->getCurAspectInstance();
        [$before, $around, $after, $afterThrowing, $afterReturning] = self::getAspectAdvices($aspectInstance::class);

        try {
            // Execute Around
            $pointer->setValue(
                $around ? $around->invoke($aspectInstance, $pointer) : $pointer->process()
            );
        } catch (Throwable $throwable) {
            // Set Throwable
            $pointer->setThrowable($throwable);
        }
        // Execute After
        if ($after) $after->invoke($aspectInstance, $pointer);

        //  Execute AfterThrowing If kernel has throwable
        if ($pointer->getThrowable()) {
            // Execute AfterThrowing
            if ($afterThrowing) {
                $pointer->setValue($afterThrowing->invoke($aspectInstance, $pointer->getThrowable(), $pointer));
            } else {
                throw $pointer->getThrowable();
            }
        } else {
            // Execute AfterReturning
            if ($afterReturning) $pointer->setValue($afterReturning->invoke($aspectInstance, $pointer));
        }

        return $pointer->getValue();
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
