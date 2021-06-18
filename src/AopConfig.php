<?php

namespace BiiiiiigMonster\Aop;

class AopConfig
{
    /**
     * @var AopConfig
     */
    private static AopConfig $instance;

    private function __construct(
        private array $scanDirs = [],
        private bool $cacheable = false,
        private string $storageDir = '',
        private array $aspects = [],
    )
    {
        // create storage path dir when not exist.
        !is_dir($this->storageDir) && mkdir($this->storageDir);
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
    public function getScanDirs(): array
    {
        return $this->scanDirs;
    }

    /**
     * @return string
     */
    public function getStorageDir(): string
    {
        return $this->storageDir;
    }

    /**
     * @return bool
     */
    public function isCacheable(): bool
    {
        return $this->cacheable;
    }

    /**
     * @return array
     */
    public function getAspects(): array
    {
        return $this->aspects;
    }
}