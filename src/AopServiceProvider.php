<?php

namespace BiiiiiigMonster\Aop;

use Illuminate\Support\ServiceProvider;

class AopServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->mergeConfigFrom(dirname(__DIR__) . '/config/aop.php', 'aop');

        // Aop autoload function register
        AopClassLoader::init($this->app);
    }

    public function boot()
    {
        $this->publishes([dirname(__DIR__) . '/config/aop.php' => $this->app->configPath('aop.php')]);
    }
}