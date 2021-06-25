<?php

namespace BiiiiiigMonster\Aop;

class AopConfig
{
    /**
     * @var AopConfig
     */
    private static AopConfig $instance;

    private function __construct(
        private array $aspects = [],
        private array $proxyDirs = [],
        private bool $proxyAll = false,
        private array $exceptDirs = [],
        private bool $cacheable = false,
        private string $storageDir = '',
    )
    {
    }

    /**
     * Get single instance.
     * @param array $config
     * @return static
     */
    public static function instance(array $config=[]): self
    {
        if(!isset(self::$instance)){
            self::$instance = new self(...array_values($config));
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
     * @return bool
     */
    public function isProxyAll(): bool
    {
        return $this->proxyAll;
    }

    /**
     * @return array
     */
    public function getExceptDirs(): array
    {
        return $this->exceptDirs;
    }

    /**
     * @return bool
     */
    public function isCacheable(): bool
    {
        return $this->cacheable;
    }

    /**
     * @return string
     */
    public function getStorageDir(): string
    {
        return $this->storageDir;
    }
}