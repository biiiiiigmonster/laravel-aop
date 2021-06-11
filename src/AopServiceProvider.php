<?php


namespace BiiiiiigMonster\Aop;


use Illuminate\Support\ServiceProvider;

class AopServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->mergeConfigFrom(__DIR__.'/../config/aop.php', 'aop');
        AopClassLoader::init();
    }

    public function boot()
    {
        $this->publishes([__DIR__ . '/../config/aop.php' => config_path('aop.php')]);
    }
}