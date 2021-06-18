<?php


namespace BiiiiiigMonster\Aop\Console;


use BiiiiiigMonster\Aop\AopClassLoader;
use BiiiiiigMonster\Aop\AopConfig;
use BiiiiiigMonster\Aop\Proxy;
use BiiiiiigMonster\Aop\Visitors\ClassVisitor;
use BiiiiiigMonster\Aop\Visitors\MethodVisitor;
use Illuminate\Console\Command;

class ScanCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $name = 'aop:scan';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Scan files';

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle(): void
    {
        $aopConfig = AopConfig::instance();
        if (empty($aopConfig->getScanDirs())) {
            $this->warn('Scan dirs is empty!');
            return;
        }

        $finder = AopClassLoader::finder($aopConfig->getScanDirs());
        foreach ($finder as $file) {
            // 实例化代理
            $proxy = new Proxy($file, [new ClassVisitor(), new MethodVisitor()]);
            // 代理文件预设路径
            $proxyFilepath = $proxy->proxyFilepath($aopConfig->getStorageDir());
            // 生成代理文件
            if ($proxy->generateProxyFile($proxyFilepath)) {
                $this->info("Proxy file $proxyFilepath generate success!");
            } else {
                $this->error("Proxy file $proxyFilepath generate fail!");
            }
        }
    }
}