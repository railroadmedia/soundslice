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

        $this->loadMigrationsFrom(__DIR__ . '/../migrations');
        $this->publishes(
            [
                __DIR__ . '/../config/soundslice.php' => config_path('soundslice.php'),
            ]
        );
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