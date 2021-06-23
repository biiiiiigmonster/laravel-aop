<?php

namespace BiiiiiigMonster\Aop;

use BiiiiiigMonster\Aop\Attributes\Aspect;
use BiiiiiigMonster\Aop\Exceptions\AopException;
use ReflectionClass;
use ReflectionException;
use SplPriorityQueue;

class Aop
{
    private static array $aspects = [];

    private static array $aspectMapping = [];

    private static array $attributeMapping = [];

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
     * @throws ReflectionException
     * @throws AopException
     */
    public static function parse(string $className, string $method): void
    {
        $rfcClass = new ReflectionClass($className);
        $rfcClassAttributes = $rfcClass->getAttributes();
        $rfcMethodAttributes = $rfcClass->getMethod($method)->getAttributes();
        $queue = new SplPriorityQueue();

        foreach (self::getAspects() as $aspect) {
            $aspectClass = new ReflectionClass($aspect);
            if(empty($aspectClass->getAttributes(Aspect::class))){
                throw new AopException("$aspect is not a Aspect!");
            }
            /** @var Aspect $aspectAttribute */
            $aspectAttribute = $aspectClass->getAttributes(Aspect::class)[0]->newInstance();
            foreach ($aspectAttribute->pointcuts as $pointcut) {
                /**----------引入(注解)-----------*/
                foreach ($rfcMethodAttributes as $attribute) {
                    if ($pointcut === $attribute->getName()) {
                        // 如果切入点存在当前方法的注解数组中
                        $queue->insert([$aspectClass->newInstance(), $attribute->newInstance()], $aspectAttribute->order);
                        continue 3;
                    }
                }
                foreach ($rfcClassAttributes as $attribute) {
                    if ($pointcut === $attribute->getName()) {
                        // 如果切入点存在当前方法所在类的注解数组中
                        $queue->insert([$aspectClass->newInstance(), $attribute->newInstance()], $aspectAttribute->order);
                        continue 3;
                    }
                }
                /**--------织入(切点)----------*/
                if (self::isMatch($pointcut, $className, $method)) {
                    // 如果切入点匹配于当前方法
                    $queue->insert([$aspectClass->newInstance(), null], $aspectAttribute->order);
                    continue 2;
                }
            }
        }

        while (!$queue->isEmpty()) {
            [$aspectInstances, $attributeInstances] = $queue->extract();
            self::$aspectMapping[$className][$method][] = $aspectInstances;
            self::$attributeMapping[$className][$method][$aspectInstances::class] = $attributeInstances;
        }
    }

    /**
     * Get the method aspect map.
     * @param string $className
     * @param string $method
     * @return array
     * @throws ReflectionException
     * @throws AopException
     */
    public static function getAspectMapping(string $className, string $method): array
    {
        if(!isset(self::$aspectMapping[$className][$method])){
            self::parse($className, $method);
        }

        return self::$aspectMapping[$className][$method] ?? [];
    }

    /**
     * Get the method attribute by aspect class.
     * @param string $className
     * @param string $method
     * @param string $aspectClass
     * @return object|null
     */
    public static function getAttributeMapping(string $className, string $method, string $aspectClass): ?object
    {
        return self::$attributeMapping[$className][$method][$aspectClass] ?? null;
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
