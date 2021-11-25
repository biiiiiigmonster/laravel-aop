<?php

namespace BiiiiiigMonster\Aop;

use Illuminate\Foundation\PackageManifest;

class AopConfig
{
    private array $proxyDirs = [];
    private string $storageDir;

    /**
     * @var AopConfig
     */
    private static AopConfig $instance;

    private function __construct(
        private array $aspects = [],
    )
    {
        $this->storageDir = storage_path('framework/aop');
        // 默认只代理laravel下app目录文件
        $this->proxyDirs[] = app_path();
        $this->loadConfiguredAops();
    }

    /**
     * Load all of the configured aops.
     */
    public function loadConfiguredAops(): void
    {
        $aops = app(PackageManifest::class)->config("aop");
        $this->aspects = array_merge($this->aspects, $aops['aspects'] ?? []);
    }

    /**
     * Get single instance.
     * @param array $config
     * @return static
     */
    public static function instance(array $config = []): static
    {
        if (!isset(self::$instance)) {
            self::$instance = new static(...array_values($config));
        }

        return self::$instance;
    }

    /**
     * @return array
     */
    public function getAspects(): array
    {
        return $this->aspects;
    }

    /**
     * @return array
     */
    public function getProxyDirs(): array
    {
        return $this->proxyDirs;
    }

    /**
     * @return string
     */
    public function getStorageDir(): string
    {
        return $this->storageDir;
    }
}