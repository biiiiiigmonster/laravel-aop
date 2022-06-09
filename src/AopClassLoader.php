<?php

namespace BiiiiiigMonster\Aop;

use BiiiiiigMonster\Aop\Visitors\ClassVisitor;
use BiiiiiigMonster\Aop\Visitors\MethodVisitor;
use Composer\Autoload\ClassLoader as ComposerClassLoader;
use Illuminate\Contracts\Foundation\Application;
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
     * @param Application $app
     */
    public function __construct(
        private ComposerClassLoader $composerLoader,
        private Application $app
    )
    {
        Aop::register(AopConfig::instance($this->app)->getAspects());
    }

    /**
     * Aop ClassLoader init.
     * @param Application $app
     */
    public static function init(Application $app): void
    {
        $loaders = spl_autoload_functions();

        foreach ($loaders as &$loader) {
            $unregisterLoader = $loader;
            if (is_array($loader) && $loader[0] instanceof ComposerClassLoader) {
                $loader[0] = new static($loader[0], $app);
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
