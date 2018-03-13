<?php

namespace Railroad\Soundslice\Controllers;


use Exception;
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
     * @throws Exception
     */
    public function createScore(Request $request)
    {
        try{
            $slug = $this->soundsliceService->createScore(
                $request->get('name'),
                $request->get('folder-id'),
                $request->get('artist'),
                $request->get('publicly-listed'),
                $request->get('embed-white-list-only'),
                $request->get('embed-globally'),
                $request->get('printing-allowed')
            );
        }catch(Exception $e){
            return new JsonResponse(
                ['errors' => [
                    [
                        'status' => 'error',
                        'code' => 500,
                        'title' => 'SoundSliceJsonController@createScore failed',
                        'detail' =>
                            'Param ("name"*): "' . $request->get('name') . '", error message: "' .
                            $e->getMessage() . '" (* not necessarily the only param).'
                    ]
                ]],
                500
            );

        }

        return new JsonResponse($slug, 201);
    }

    /**
     * @return JsonResponse
     */
    public function list()
    {
        $response = $this->soundsliceService->listScores();

        return new JsonResponse($response);
    }

    /**
     * @param $slug
     * @return JsonResponse
     */
    public function get($slug){

        try{
            $body = $this->soundsliceService->getScore($slug);
        }catch(Exception $e){
            return new JsonResponse(
                ['errors' => [
                    [
                        'status' => 'error',
                        'code' => 500,
                        'title' => 'SoundSliceJsonController@get failed',
                        'detail' => 'Slug: "' . $slug . '", error message: "' . $e->getMessage() . '".'
                    ]
                ]],
                500
            );
        }

        return new JsonResponse($body);
    }

    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function delete(Request $request){
        $slug = $request->get('slug');

        try{
            $delete = $this->soundsliceService->deleteScore($slug);
        }catch(Exception $e){
            return new JsonResponse(
                ['errors' => [
                    [
                        'status' => 'error',
                        'code' => 500,
                        'title' => 'SoundSliceJsonController@delete failed',
                        'detail' => 'Param("slug): "' . $slug . '", error message: "' . $e->getMessage() . '".'
                    ]
                ]],
                500
            );

        }

        return new JsonResponse($delete);
    }

    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function createFolder(Request $request){
        $name = $request->get('name');

        try{
            $folderId = $this->soundsliceService->createFolder($name);
        }catch(Exception $e){
            return new JsonResponse(
                ['errors' => [
                    [
                        'status' => 'error',
                        'code' => 500,
                        'title' => 'SoundSliceJsonController@createFolder failed',
                        'detail' => 'Param("name"): "' . $name . '", error message: "' . $e->getMessage() . '".'
                    ]
                ]],
                500
            );
        }

        return new JsonResponse($folderId, 201);
    }

    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function deleteFolder(Request $request){
        $id = $request->get('id');

        try{
            $delete = $this->soundsliceService->deleteFolder($id);
        }catch(Exception $e){
            return new JsonResponse(
                ['errors' => [
                    [
                        'status' => 'error',
                        'code' => 500,
                        'title' => 'SoundSliceJsonController@deleteFolder failed',
                        'detail' => 'Param ("id"): "' . $id . '", error message: "' . $e->getMessage() . '".'
                    ]
                ]],
                500
            );
        }

        return new JsonResponse($delete);
    }

    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function createNotation(Request $request)
    {
        $slug = $request->get('slug');
        $assetUrl = $request->get('asset-url');

        try{
            $response = $this->soundsliceService->addNotation($slug, $assetUrl);
        }catch(Exception $e){
            return new JsonResponse(
                ['errors' => [
                    [
                        'status' => 'error',
                        'code' => 500,
                        'title' => 'SoundSliceJsonController@createNotation failed',
                        'detail' => 'Param 1 ("slug"): "' .  $slug .
                            '", Param 2 ("assetUrl"): "' . $assetUrl .
                            '", error message: "' . $e->getMessage() . '".'
                    ]
                ]],
                500
            );
        }

        return new JsonResponse($response, 201);
    }
}