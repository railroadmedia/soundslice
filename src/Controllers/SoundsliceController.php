<?php

namespace Railroad\Soundslice\Controllers;


use Illuminate\Http\Request;
use Railroad\Soundslice\Services\SoundsliceService;

class SoundsliceController
{
    /**
     * @var SoundsliceService
     */
    private $soundsliceService;

    public function __construct(SoundsliceService $soundsliceService)
    {
        $this->soundsliceService = $soundsliceService;
    }

    public function uploadNotation(Request $request)
    {
        $target = $request->get('target');
        $file = $request->file('file');

        $this->soundsliceService->createScore($target, $file);
    }
}