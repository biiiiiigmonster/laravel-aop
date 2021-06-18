<?php

namespace BiiiiiigMonster\Aop;

class AopConfig
{
    /**
     * @var AopConfig
     */
    private static AopConfig $instance;

    private function __construct(
        private array $scanDirs,
        private string $storagePath,
        private bool $cacheable,
    )
    {
        // create storage path dir when not exist.
        !is_dir($this->storagePath) && mkdir($this->storagePath);
    }

    /**
     * Get single instance.
     * @param array|null $config
     * @return static
     */
    public static function instance(?array $config=null): self
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
    public function getStoragePath(): string
    {
        return $this->storagePath;
    }

    /**
     * @return bool
     */
    public function isCacheable(): bool
    {
        return $this->cacheable;
    }
}