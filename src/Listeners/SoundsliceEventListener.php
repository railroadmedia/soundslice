<?php

namespace Railroad\Soundslice\Listeners;

use Railroad\Soundslice\Events\RemoteAssetPutEvent;
use Railroad\Soundslice\Services\SoundsliceService;

class RemoteAssetPutEventListener
{
    /**
     * @var SoundsliceService
     */
    private $soundsliceService;

    public function __construct(SoundsliceService $soundsliceService)
    {
        $this->soundsliceService = $soundsliceService;
    }

    public function handle(RemoteAssetPutEvent $event)
    {
        $this->soundsliceService->processRemoteAssetUploadEvent($event);

        if($event['key'] === config('soundslice.notation-key-signifier')){
            $this->soundsliceService->processRemoteAssetUploadEvent($event);
        }


    }
}