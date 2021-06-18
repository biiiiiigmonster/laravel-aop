<?php

namespace BiiiiiigMonster\Aop;

use BiiiiiigMonster\Aop\Console\ScanCommand;
use Illuminate\Support\ServiceProvider;

class AopServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->mergeConfigFrom(dirname(__DIR__) . '/config/aop.php', 'aop');

        $this->commands(
            ScanCommand::class,
        );

        // Aop autoload function register
        AopClassLoader::init(config('aop'));
    }

    public function boot()
    {
        $this->publishes([dirname(__DIR__) . '/config/aop.php' => config_path('aop.php')]);
    }
}