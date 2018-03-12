<?php namespace MyNamespace\MyPackageName;

use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class SoundsliceServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     *
     * @return void
     */
    public function boot()
    {
        parent::boot();

        $this->publishes([
            __DIR__ . '/../config/soundslice.php' => config_path('soundslice.php'),
        ]);

        $this->loadRoutesFrom(__DIR__ . '/../../routes.php');
    }

    /**
     * Register the application services.
     *
     * @return void
     */
    public function register()
    {

    }
}