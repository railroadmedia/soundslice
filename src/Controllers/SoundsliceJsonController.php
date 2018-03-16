<?php

namespace Railroad\Soundslice\Controllers;


use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
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
     * @method ::validate() $request
     */
    public function createScore(Request $request)
    {
        try{
            $request->validate([ /* \Illuminate\Validation\Validator::validate() */
                'name' => 'max:255',
                'artist' => 'max:255',
                'folder-id' => 'numeric'
            ]);
        }catch(ValidationException $exception){ // \Illuminate\Validation\Validator::validate() **does** throw this
            $validator = $exception->validator;
            $messages = $validator->getMessageBag()->getMessages();

            return new JsonResponse(['errors' => [[
                'status' => 'Bad Request',
                'code' => 400,
                'title' => 'SoundSliceJsonController@createScore validation failed',
                'detail' => $messages
            ] ]], 400);
        }

        try{
            $slug = $this->soundsliceService->createScore(
                $request->get('name'),
                $request->get('artist'),
                $request->get('folder-id'),
                $request->get('publicly-listed'),
                $request->get('embed-white-list-only'),
                $request->get('embed-globally'),
                $request->get('printing-allowed')
            );
        }catch(Exception $e){

            error_log('SoundSliceJsonController@createScore failed with the following message from Soundslice: ' .
                $e->getMessage());

            return new JsonResponse(
                ['errors' => [
                    [
                        'status' => 'error',
                        'code' => 500,
                        'title' => 'SoundSliceJsonController@createScore failed (detail has message from Soundslice)',
                        'detail' => $e->getMessage()
                    ]
                ]],
                500
            );

        }

        return new JsonResponse(['slug' => $slug], 201);
    }

    /**
     * @return JsonResponse
     */
    public function list()
    {
        $scores = $this->soundsliceService->listScores();

        return new JsonResponse(['scores' => $scores], 200);
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

        return new JsonResponse(['score' => $body], 200);
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

        return new JsonResponse(['deleted' => $delete], 200);
    }

    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function createFolder(Request $request){
        $name = $request->get('name');
        $parentId = $request->get('parent_id');

        try{
            $folderId = $this->soundsliceService->createFolder($name, $parentId);
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

        return new JsonResponse(['folder-id' => $folderId], 201);
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

        return new JsonResponse(['deleted' => $delete], 200);
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

        return new JsonResponse(['notation' => $response], 201);
    }
}