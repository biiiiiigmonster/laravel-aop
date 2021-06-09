<?php

namespace BiiiiiigMonster\Aop;

use BiiiiiigMonster\Aop\Attributes\Aspect;
use ReflectionClass;
use ReflectionException;
use SplPriorityQueue;

class Aop
{
    private static array $aspects = [];

    private static array $mapping = [];

    /**
     * @param string $aspectClass
     */
    public static function register(string $aspectClass): void
    {
        self::$aspects[] = $aspectClass;
    }

    /**
     * @param string $className
     * @param string $method
     * @return array
     * @throws ReflectionException
     */
    public static function parse(string $className, string $method): array
    {
        $rfcClass = new ReflectionClass($className);
        $queue = new SplPriorityQueue();

        foreach (self::$aspects as $aspect) {
            $aspectClass = new ReflectionClass($aspect);
            /** @var Aspect $aspectAttribute */
            $aspectAttribute = $aspectClass->getAttributes(Aspect::class)[0]->newInstance();
            // 允许同一个切面多次注册指定方法
            foreach ($aspectAttribute->pointcuts as $pointcut) {
                foreach ($rfcClass->getAttributes() as $attribute) {
                    // 如果切入点存在当前方法所在类的注解数组中
                    if ($pointcut === $attribute->getName()) {
                        $queue->insert([$aspectClass->newInstance(), $attribute->newInstance()], $aspectAttribute->order);
                    }
                }
                foreach ($rfcClass->getMethod($method)->getAttributes() as $attribute) {
                    // 如果切入点存在当前方法的注解数组中
                    if ($pointcut === $attribute->getName()) {
                        $queue->insert([$aspectClass->newInstance(), $attribute->newInstance()], $aspectAttribute->order);
                    }
                }
                // 如果切入点匹配于当前方法
                if (self::isMatch($pointcut, $className, $method)) {
                    $queue->insert([$aspectClass->newInstance(), null], $aspectAttribute->order);
                }
            }
        }

        $aspectInstances = [];
        while (!$queue->isEmpty()) {
            $aspectInstances[] = $queue->extract();
        }

        return self::$mapping[$className][$method] = $aspectInstances;
    }

    /**
     * @param string $className
     * @param string $method
     * @return array
     * @throws ReflectionException
     */
    public static function get(string $className, string $method): array
    {
        return self::$mapping[$className][$method] ?? self::parse($className, $method);
    }

    /**
     * @param string $className
     * @param string $method
     * @param string $pointcut
     * @return bool
     */
    public static function isMatch(string $pointcut, string $className, string $method): bool
    {
        $pointcutArr = explode('::', $pointcut);
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
            if(str_starts_with($ruleMethod, '*')){
                $ruleMethod = strrev($ruleMethod);
                $method = strrev($method);
            }
            for($i=0;$i<strlen($ruleMethod);$i++){
                if($ruleMethod[$i]==='*'){
                    break;
                }
                if($ruleMethod[$i]!==$method[$i]){
                    return false;
                }
            }
        }

        return true;
    }
}
