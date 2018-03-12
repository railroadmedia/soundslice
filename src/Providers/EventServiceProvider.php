<?php

namespace Railroad\Soundslice\Providers;

use Illuminate\Support\Facades\Event;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Railroad\RemoteStorage\Events\PutEvent;
use Railroad\Soundslice\Events\RemoteAssetUploadEvent;
use Railroad\Soundslice\Listeners\SoundsliceEventListener;
use Railroad\Soundslice\Listeners\SoundsliceRemoteStorageEventListener;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event listener mappings for the application.
     *
     * @var array
     */
    protected $listen = [
        PutEvent::class => [
            SoundsliceEventListener::class,
        ],
        RemoteAssetUploadEvent::class => [
            SoundsliceRemoteStorageEventListener::class,
        ]
    ];

    /**
     * Register any events for your application.
     *
     * @return void
     */
    public function boot()
    {
        parent::boot();

        //
    }
}

