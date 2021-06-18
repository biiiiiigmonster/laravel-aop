<?php

namespace BiiiiiigMonster\Aop;

use BiiiiiigMonster\Aop\Visitors\ClassVisitor;
use BiiiiiigMonster\Aop\Visitors\MethodVisitor;
use Composer\Autoload\ClassLoader as ComposerClassLoader;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;

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
            // 实例化代理(在初始化时生成代理代码的过程中，源代码相关信息会被存储在访客节点类中)
            $proxy = new Proxy($file, [$classVisitor = new ClassVisitor(), new MethodVisitor()]);
            // 代理文件预设路径
            $proxyFilepath = $proxy->proxyFilepath($aopConfig->getStorageDir());
            // 生成代理文件
            if ($proxy->generateProxyFile($proxyFilepath)) {
                $this->classMap[$classVisitor->getClass()] = $proxyFilepath;
            }
            // 判断当前扫描结果，如果是Aspect注解，那就进行注册
            if ($classVisitor->isAspect()) {
                Aop::register($classVisitor->getClass());
            }
        }
    }

    /**
     * Lazy load class file.
     * @param $class
     * @return string
     */
    public function lazyLoad($class): string
    {
        $file = $this->composerLoader->findFile($class);

        return $file;
    }

    /**
     * @param string $class
     */
    public function loadClass(string $class): void
    {
        $this->lazyLoad($class);

        if ($file = $this->findFile($class)) {

            include $file;
        }
    }

    /**
     * @param string $class
     * @return string
     */
    public function findFile(string $class): string
    {
        return $this->classMap[$class] ?? $this->lazyLoad($class);
    }
}
