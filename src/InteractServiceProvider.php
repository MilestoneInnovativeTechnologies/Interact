<?php

namespace Milestone\Interact;

use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

class InteractServiceProvider extends ServiceProvider
{
    protected $project_root = __DIR__ . '/../';

    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        $this->mergeConfigFrom($this->project_root . 'config/interact.php', 'interact' );
        $this->mergeConfigFrom($this->project_root . 'config/cache.stores.php', 'cache.stores.interact' );
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

        Event::listen(['eloquent.created: *'], function($name,$data) { cache()->store('interact')->put(Method::getModelClassFromEloquentName($name, 'eloquent.created: '),now()); });
        Event::listen(['eloquent.updated: *'], function($name,$data) { cache()->store('interact')->put(Method::getModelClassFromEloquentName($name,'eloquent.updated: '),now()); });
    }
}
