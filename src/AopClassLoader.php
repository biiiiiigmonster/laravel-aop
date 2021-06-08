<?php

namespace BiiiiiigMonster\Aop;

use BiiiiiigMonster\Aop\Visitors\ClassVisitor;
use BiiiiiigMonster\Aop\Visitors\MethodVisitor;
use Composer\Autoload\ClassLoader as ComposerClassLoader;
use Symfony\Component\Finder\Finder;

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
        private array $config
    )
    {
        $this->proxyFile();
    }

    /**
     * @param array $config
     */
    public static function init(array $config = []): void
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
     * Create Proxy File.
     */
    public function proxyFile(): void
    {
        $proxyDir = $this->config['proxy'] ?? app_path();
        $storagePath = $this->config['storage'] ?? sys_get_temp_dir();
        !is_dir($storagePath) && mkdir($storagePath);

        $files = Finder::create()->in($proxyDir)->exclude(dirname(__DIR__))->files()->name('*.php');
        foreach ($files as $file) {
            // 实例化代理
            $proxy = new Proxy([$classVisitor = new ClassVisitor(), new MethodVisitor()]);
            $proxyFile = $proxy->generateProxyFile(
                $file->getContents(),
                sprintf('%s' . DIRECTORY_SEPARATOR . '%s', $storagePath, $file->getFilename())
            );
            // 代理类将源代码生成代理后代码(在生成代理代码的过程中，源文件相关信息会被存储在访客节点类中)
            $this->classMap[$classVisitor->getClass()] = $proxyFile;
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
