<?php

namespace Railroad\Soundslice\Providers;

use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Railroad\Soundslice\Services\ConfigService;

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

        $this->setupConfig();

        $this->publishes(
            [
                __DIR__ . '/../../config/soundslice.php' => config_path('soundslice.php'),
            ]
        );
    }

    private function setupConfig()
    {
        
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