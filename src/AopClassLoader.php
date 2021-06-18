<?php

namespace BiiiiiigMonster\Aop;

use BiiiiiigMonster\Aop\Visitors\ClassVisitor;
use BiiiiiigMonster\Aop\Visitors\MethodVisitor;
use Composer\Autoload\ClassLoader as ComposerClassLoader;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;

class AopClassLoader
{
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
        $aopConfig = AopConfig::instance($config);
        // 支持代理缓存模式，无须重复扫描
        if ($aopConfig->isCacheable()) {
            // 代理缓存模式中，Aop aspects 直接从配置中获取，而非扫描注册
            Aop::register($aopConfig->getAspects());
        } elseif (!empty($aopConfig->getScanDirs())) {
            $this->scan();
        }
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
     * Create Finder.
     *
     * @param string|string[] $dirs
     * @return Finder
     */
    public static function finder(string|array $dirs): Finder
    {
        return Finder::create()
            ->in($dirs)
            ->files()
            ->filter(fn(SplFileInfo $file) => !str_starts_with($file->getRealPath(), dirname(__DIR__)))
            ->name('*.php');
    }

    /**
     * Scan file.
     */
    public function scan(): void
    {
        $aopConfig = AopConfig::instance();

        $finder = self::finder($aopConfig->getScanDirs());

        foreach ($finder as $file) {
            // 实例化代理
            $proxy = new Proxy($file, [$classVisitor = new ClassVisitor(), new MethodVisitor()]);
            // 代理文件预设路径
            $proxyFilepath = $proxy->proxyFilepath($aopConfig->getStorageDir());
            // 生成代理文件(在生成代理文件的过程中，源代码相关信息会被存储在访客节点类中)
            $proxy->generateProxyFile($proxyFilepath);
            $this->classMap[$classVisitor->getClass()] = $proxyFilepath;
            // 判断当前扫描结果，如果是Aspect注解，那就进行注册
            if ($classVisitor->isAspect()) {
                Aop::register($classVisitor->getClass());
            }
        }
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
     * @return string|null
     */
    public function findFile(string $class): ?string
    {
        $file = $this->classMap[$class] ?? $this->composerLoader->findFile($class);

        return is_string($file) ? $file : null;
    }
}
