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
        $fileInfo = new SplFileInfo($file);
        // 非指定代理文件，直接返回
        if (!self::isProxy($fileInfo->getRealPath())) {
            return $file;
        }

        // 实例化代理(在初始化时生成代理代码的过程中，源代码相关信息会被存储在访客节点类中)
        $proxy = new Proxy($fileInfo, [$classVisitor = new ClassVisitor(), new MethodVisitor()]);
        // 无须代理的文件类型，直接返回
        if ($classVisitor->isInterface()) {
            return $file;
        }

        // 获取代理文件路径名
        return $this->classMap[$classVisitor->getClass()] = $proxy->generateProxyFile();
    }

    /**
     * @param string $namespaceClass
     */
    public function loadClass(string $namespaceClass): void
    {
        if ($file = $this->findFile($namespaceClass)) {

            include $file;
        }
    }

    /**
     * @param string $namespaceClass
     * @return string
     */
    private function findFile(string $namespaceClass): string
    {
        return $this->classMap[$namespaceClass] ??
            $this->lazyLoad(
                $this->composerLoader->findFile($namespaceClass)
            );
    }

    /**
     * @param string $fileRealPath
     * @return bool
     */
    private static function isProxy(string $fileRealPath): bool
    {
        $proxyDirs = AopConfig::instance()->getProxyDirs();
        foreach ($proxyDirs as $proxyDir) {
            if (str_starts_with($fileRealPath, $proxyDir)) {
                return true;
            }
        }

        return false;
    }
}
