<?php

namespace Railroad\Soundslice\Events;

use Illuminate\Support\Facades\Event;

class RemoteAssetPutEvent extends Event
{
    public $assetUrl;
    public $assetName;
    public $bucket;

    /**
     * RemoteAssetUploadEvent constructor.
     * @param string $assetUrl
     * @param string $assetName
     * @param string $bucket
     */
    public function __construct(
        $assetUrl,
        $assetName,
        $bucket
    ){
        $this->assetUrl = $assetUrl;
        $this->assetName = $assetName;
        $this->bucket = $bucket;
    }
}