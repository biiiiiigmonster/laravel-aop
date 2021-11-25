<?php

namespace BiiiiiigMonster\Aop;

use BiiiiiigMonster\Aop\Attributes\Aspect;
use BiiiiiigMonster\Aop\Exceptions\AopException;
use ReflectionClass;
use SplPriorityQueue;

class Aop
{
    private static array $aspects = [];

    private static array $aspectMapping = [];

    private static array $attributeMapping = [];

    private static array $classMethodAttributeInstances = [];

    /**
     * Register aspect class.
     * @param string|array $aspectClass
     */
    public static function register(string|array $aspectClass): void
    {
        self::$aspects = array_merge(self::$aspects, (array)$aspectClass);
    }

    /**
     * Get aspects.
     * @return array
     */
    public static function getAspects(): array
    {
        return self::$aspects;
    }

    /**
     * Parse the class & method aspect instance.
     * @param string $className
     * @param string $method
     * @return void
     * @throws \ReflectionException
     * @throws \BiiiiiigMonster\Aop\Exceptions\AopException
     */
    public static function parse(string $className, string $method): void
    {
        self::attributeNewInstance($className,$method);
        $queue = new SplPriorityQueue();

        foreach (self::getAspects() as $aspect) {
            $aspectClass = new ReflectionClass($aspect);
            if (empty($aspectClassAspectAttributes = $aspectClass->getAttributes(Aspect::class))) {
                throw new AopException("$aspect is not a Aspect!");
            }
            foreach ($aspectClassAspectAttributes as $aspectClassAspectAttribute) {
                /** @var Aspect $aspectClassAspectAttributeInstance */
                $aspectClassAspectAttributeInstance = $aspectClassAspectAttribute->newInstance();
                foreach ($aspectClassAspectAttributeInstance->pointcuts as $pointcut) {
                    /**----------引入(注解)-----------*/
                    if ($rfcAttributeInstances = self::getAttributeNewInstances($className, $method, $pointcut)) {
                        foreach ($rfcAttributeInstances as $rfcAttributeInstance) {
                            $queue->insert([$aspectClass->newInstance(), $rfcAttributeInstance], $aspectClassAspectAttributeInstance->priority);
                        }
                    }
                    /**--------织入(切点)----------*/
                    if (self::isMatch($pointcut, $className, $method)) {
                        $queue->insert([$aspectClass->newInstance(), null], $aspectClassAspectAttributeInstance->priority);
                    }
                }
            }
        }

        while (!$queue->isEmpty()) {
            [$aspectInstance, $attributeInstance] = $queue->extract();
            self::$aspectMapping[$className][$method][] = $aspectInstance;
            if ($attributeInstance) {
                self::$attributeMapping[$className][$method][$aspectInstance::class][] = $attributeInstance;
            }
        }
    }

    /**
     * @param string $className
     * @param string $method
     * @throws \ReflectionException
     */
    public static function attributeNewInstance(string $className, string $method): void
    {
        if (isset(self::$classMethodAttributeInstances[$className][$method])) {
            return;
        }

        $rfcClass = new ReflectionClass($className);
        $rfcClassAttributes = $rfcClass->getAttributes();
        $rfcMethodAttributes = $rfcClass->getMethod($method)->getAttributes();
        foreach ($rfcClassAttributes + $rfcMethodAttributes as $rfcAttribute) {
            self::$classMethodAttributeInstances[$className][$method][] = $rfcAttribute->newInstance();
        }
    }

    /**
     * @param string $className
     * @param string $method
     * @param string|null $attributeClassName
     * @return array
     */
    public static function getAttributeNewInstances(string $className, string $method, ?string $attributeClassName): array
    {
        $instances = self::$classMethodAttributeInstances[$className][$method] ?? [];

        return $attributeClassName
            ? array_filter($instances, fn($instance) => $instance::class === $attributeClassName)
            : $instances;
    }

    /**
     * Get the method aspect map.
     * @param string $className
     * @param string $method
     * @return array
     * @throws \ReflectionException
     * @throws \BiiiiiigMonster\Aop\Exceptions\AopException
     */
    public static function getAspectMapping(string $className, string $method): array
    {
        if (!isset(self::$aspectMapping[$className][$method])) {
            self::parse($className, $method);
        }

        return self::$aspectMapping[$className][$method] ?? [];
    }

    /**
     * Get the method attribute by aspect class.
     * @param string $className
     * @param string $method
     * @param string $aspectClass
     * @return array
     */
    public static function getAttributeMapping(string $className, string $method, string $aspectClass): array
    {
        return self::$attributeMapping[$className][$method][$aspectClass] ?? [];
    }

    /**
     * Judgment the method whether match the pointcut rule.
     * @param string $className
     * @param string $method
     * @param string $pointcut
     * @return bool
     */
    public static function isMatch(string $pointcut, string $className, string $method): bool
    {
        $pointcutArr = explode('::', $pointcut);
        // Classname must eq.
        if ($className !== $pointcutArr[0]) {
            return false;
        }

        /**
         * match eg.
         * $method = insertLog
         * $ruleMethod = insertLog | insert* | *Log
         */
        if (isset($pointcutArr[1])) {
            $ruleMethod = $pointcutArr[1];
            if (str_starts_with($ruleMethod, '*')) {
                $ruleMethod = strrev($ruleMethod);
                $method = strrev($method);
            }
            for ($i = 0; $i < strlen($ruleMethod); $i++) {
                if ($ruleMethod[$i] === '*') {
                    break;
                }
                if ($ruleMethod[$i] !== $method[$i]) {
                    return false;
                }
            }
        }

        return true;
    }
}
