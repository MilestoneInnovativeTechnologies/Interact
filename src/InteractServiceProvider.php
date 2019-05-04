<?php

namespace Milestone\Interact;

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
        $this->loadRoutesFrom($this->path('routes','web.php'));
        $this->publishes([$this->pathConfig() => config_path()]);
        Event::register();
    }

    private function pathConfig($file = null){ return $this->path('config',$file); }
    private function path($folder = null,$file = null){ return trim(implode('/',[$this->project_root,$folder,$file]),'\\\/'); }

    private function mergeConfigExtras(){
        foreach ($this->bindConfigs as $key => $interact_key)
            $this->mergeConfig($key,config('interact.' . $interact_key));
    }

    private function mergeConfig($key,$content){
        $config = $this->app['config']->get($key, []);
        $this->app['config']->set($key, array_merge($content, $config));
    }
}
