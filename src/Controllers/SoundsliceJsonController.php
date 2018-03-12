<?php

namespace Railroad\Soundslice\Controllers;


use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use PHPUnit\Util\Json;
use Railroad\Soundslice\Services\SoundsliceService;

class SoundsliceJsonController
{
    /**
     * @var SoundsliceService
     */
    private $soundsliceService;

    /**
     * SoundsliceController constructor.
     * @param SoundsliceService $soundsliceService
     */
    public function __construct(SoundsliceService $soundsliceService)
    {
        $this->soundsliceService = $soundsliceService;
    }

    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function createScore(Request $request)
    {
        $name = $request->get('name');
        $folderId = $request->get('folderId');
        $artist = $request->get('artist');
        $publiclyListed = $request->get('publiclyListed');
        $embedWhiteListOnly = $request->get('embedWhiteListOnly');
        $embedGlobally = $request->get('embedGlobally');
        $printingAllowed = $request->get('printingAllowed');

        $response = $this->soundsliceService->createScore(
            $name,
            $folderId,
            $artist,
            $publiclyListed,
            $embedWhiteListOnly,
            $embedGlobally,
            $printingAllowed
        );

        return new JsonResponse($response);
    }

    /**
     * @return JsonResponse
     */
    public function list()
    {
        $response = $this->soundsliceService->list();

        return new JsonResponse($response);
    }

    /**
     * @param $slug
     * @return JsonResponse
     */
    public function get($slug){
        $response = $this->soundsliceService->get($slug);

        return new JsonResponse($response);
    }

    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function delete(Request $request){
        $slug = $request->get('slug');

        $response = $this->soundsliceService->delete($slug);

        return new JsonResponse($response);
    }

    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function createFolder(Request $request){
        $name = $request->get('name');

        $response = $this->soundsliceService->createFolder($name);

        return new JsonResponse($response);
    }

    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function deleteFolder(Request $request){
        $id = $request->get('id');

        $response = $this->soundsliceService->deleteFolder($id);

        return new JsonResponse($response);
    }

    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function createNotation(Request $request)
    {
        $slug = $request->get('slug');
        $assetUrl = $request->get('assetUrl');

        $response = $this->soundsliceService->addNotation($slug, $assetUrl);

        return new JsonResponse($response);
    }
}