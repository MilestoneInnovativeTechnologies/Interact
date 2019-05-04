<?php

namespace Milestone\Interact;

use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

class InteractServiceProvider extends ServiceProvider
{
    protected $project_root = __DIR__ . '/..';
    protected $bindConfigs = [
        'cache.stores' => 'cache_stores',
        'filesystems.disks' => 'filesystems_disks'
    ];

    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        $this->mergeConfigFrom($this->pathConfig('interact.php'), 'interact' );
        $this->mergeConfigExtras();
    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        $this->loadRoutesFrom($this->project_root . 'routes/web.php');
        $config = $this->project_root . 'config';
        $this->publishes([$config => config_path()]);

    private function mergeConfigExtras(){
        foreach ($this->bindConfigs as $key => $interact_key)
            $this->mergeConfig($key,config('interact.' . $interact_key));
    }

    private function mergeConfig($key,$content){
        $config = $this->app['config']->get($key, []);
        $this->app['config']->set($key, array_merge($content, $config));
    }
}
