<?php

namespace Milestone\Interact;

use Illuminate\Support\ServiceProvider;

class InteractServiceProvider extends ServiceProvider
{
    protected $project_root = __DIR__ . '/..';

    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        $this->mergeConfigFrom($this->pathConfig('interact.php'), 'interact' );
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
}
