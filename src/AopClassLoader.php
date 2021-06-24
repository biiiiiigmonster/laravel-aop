<?php

namespace BiiiiiigMonster\Aop;

use BiiiiiigMonster\Aop\Visitors\ClassVisitor;
use BiiiiiigMonster\Aop\Visitors\MethodVisitor;
use Composer\Autoload\ClassLoader as ComposerClassLoader;
use SplFileInfo;

class AopClassLoader
{
    /**
     * @var string[]
     */
    private array $classMap = [];

    /**
     * AopClassLoader constructor.
     * @param ComposerClassLoader $composerLoader
     * @param array $config
     */
    public function __construct(
        private ComposerClassLoader $composerLoader,
        array $config
    )
    {
        Aop::register(AopConfig::instance($config)->getAspects());
    }

    /**
     * Aop ClassLoader init.
     * @param array $config
     */
    public static function init(array $config): void
    {
        $loaders = spl_autoload_functions();

        foreach ($loaders as &$loader) {
            $unregisterLoader = $loader;
            if (is_array($loader) && $loader[0] instanceof ComposerClassLoader) {
                $loader[0] = new static($loader[0], $config);
            }
            spl_autoload_unregister($unregisterLoader);
        }
        unset($loader);

        foreach ($loaders as $reLoader) {
            spl_autoload_register($reLoader);
        }
    }

    /**
     * Lazy load class file.
     * @param $file
     * @return string
     */
    private function lazyLoad($file): string
    {
        // 实例化代理(在初始化时生成代理代码的过程中，源代码相关信息会被存储在访客节点类中)
        $proxy = new Proxy(new SplFileInfo($file), [$classVisitor = new ClassVisitor(), new MethodVisitor()]);
        // 无须代理文件，直接返回
        if ($classVisitor->isInterface()) {
            return $file;
        }

        // 获取代理文件路径名
        return $this->classMap[$classVisitor->getClass()] = $proxy->generateProxyFile();
    }

    /**
     * @param string $class
     */
    public function loadClass(string $class): void
    {
        if ($file = $this->findFile($class)) {

            include $file;
        }
    }

    /**
     * @param string $class
     * @return string
     */
    private function findFile(string $class): string
    {
        if (isset($this->classMap[$class])) {
            return $this->classMap[$class];
        } else {
            // find file from composer loader.
            $file = $this->composerLoader->findFile($class);

            return self::isExclude($class) || !self::isInclude($class) ? $file : $this->lazyLoad($file);
        }
    }

    /**
     * @param string $class
     * @return bool
     */
    private static function isExclude(string $class): bool
    {
        $excludes = AopConfig::instance()->getExcludes();
        foreach ($excludes as $exclude) {
            if (self::namespaceMatch($exclude, $class)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param string $class
     * @return bool
     */
    private static function isInclude(string $class): bool
    {
        $includes = AopConfig::instance()->getIncludes();
        foreach ($includes as $include) {
            if (self::namespaceMatch($include, $class)) {
                return true;
            }
        }

        return empty($includes);
    }

    /**
     * @param string $rule
     * @param string $class
     * @return bool
     */
    private static function namespaceMatch(string $rule, string $class): bool
    {
        $strBefore = function (string $subject, string $search): string {
            if ($search === '') {
                return $subject;
            }

            $result = strstr($subject, (string)$search, true);

            return $result === false ? $subject : $result;
        };

        return str_starts_with($strBefore($rule, '*'), $class);
    }
}
