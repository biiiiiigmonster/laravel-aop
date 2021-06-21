<?php

namespace BiiiiiigMonster\Aop;

use BiiiiiigMonster\Aop\Visitors\ClassVisitor;
use BiiiiiigMonster\Aop\Visitors\MethodVisitor;
use Composer\Autoload\ClassLoader as ComposerClassLoader;
use Symfony\Component\Finder\Finder;
use SplFileInfo;

class AopClassLoader
{
    /**
     * @var string[]
     */
    private array $classMap = [];

    /**
     * @var string[]
     */
    private array $lazyMap = [];

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
        // 懒加载，Aop aspects 直接从配置中获取，而非扫描注册
        Aop::register($aopConfig->getAspects());
        $this->scan();
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
     * @return Finder
     */
    private function finder(): Finder
    {
        return Finder::create()
            ->in(AopConfig::instance()->getScanDirs())
            ->files()
            ->filter(fn(SplFileInfo $file) => !str_starts_with($file->getRealPath(), dirname(__DIR__)))
            ->name('*.php');
    }

    /**
     * Scan file.
     */
    public function scan(): void
    {
        foreach ($this->finder() as $file) {
            $this->lazyMap[$file->getRealPath()] = 1;
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
        if (!isset($this->lazyMap[$fileInfo->getRealPath()])) {
            return $file;
        }

        // 实例化代理(在初始化时生成代理代码的过程中，源代码相关信息会被存储在访客节点类中)
        $proxy = new Proxy($fileInfo, [$classVisitor = new ClassVisitor(), new MethodVisitor()]);
        // 无须代理文件，直接返回
        if($classVisitor->isInterface()){
            return $file;
        }

        // 获取代理文件路径名
        return $this->classMap[$classVisitor->getClass()] = $proxy->generateProxyFile();
        // 判断当前扫描结果，如果是Aspect注解，那就进行注册
//        if ($classVisitor->isAspect()) {
//            Aop::register($classVisitor->getClass());
//        }
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
        return $this->classMap[$class] ?? $this->lazyLoad($this->composerLoader->findFile($class));
    }
}
