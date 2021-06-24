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
        private array $includes = [],
        private array $excludes = [],
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
    public function getIncludes(): array
    {
        return $this->includes;
    }

    /**
     * @return array
     */
    public function getExcludes(): array
    {
        return $this->excludes;
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