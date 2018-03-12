<?php

namespace Railroad\Soundslice\Controllers;


use Illuminate\Http\JsonResponse;
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

    public function createNotation(Request $request)
    {
        $name = $request->get('name');
        $folderId = $request->get('folderId');
        $artist = $request->get('artist');
        $published = $request->get('published');
        $embedWhiteListOnly = $request->get('embedWhiteListOnly');
        $embedGlobally = $request->get('embedGlobally');
        $printingAllowed = $request->get('printingAllowed');

        $response = $this->soundsliceService->createScore(
            $name,
            $folderId,
            $artist,
            $published,
            $embedWhiteListOnly,
            $embedGlobally,
            $printingAllowed
        );

        return new JsonResponse($response);
    }

    public function uploadNotation(Request $request)
    {
        $slug = $request->get('slug');
        $assetUrl = $request->get('assetUrl');

        $response = $this->soundsliceService->createNotation($slug, $assetUrl);

        return new JsonResponse($response);
    }
}