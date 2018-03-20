<?php

namespace Railroad\Soundslice\Controllers;


use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Railroad\Soundslice\Exceptions\ExternalErrorException;
use Railroad\Soundslice\Services\SoundsliceService;

class SoundsliceJsonController
{
    /**
     * @var SoundsliceService
     */
    private $soundsliceService;

    private function validate($request, $rules, $methodDescription){
        try{
            $request->validate($rules); // \Illuminate\Validation\Validator::validate()
        }catch(ValidationException $exception){ // \Illuminate\Validation\Validator::validate() **does** throw this
            $validator = $exception->validator;
            $messages = $validator->getMessageBag()->getMessages();

            return new JsonResponse(['errors' => [[
                'status' => 'Bad Request',
                'code' => 400,
                'title' => $methodDescription . ' request validation failure',
                'detail' => $messages
            ] ]], 400);
        }

        return true;
    }

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
     * @method ::validate() $request
     */
    public function createScore(Request $request)
    {
        $validation = $this->validate($request, [
            'name' => 'max:255',
            'artist' => 'max:255',
            'folder-id' => 'numeric'
        ], 'create score');
        if($validation !== true){
            return $validation;
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
            if($e instanceof ExternalErrorException){ // code-smell...
                return new JsonResponse(
                    ['errors' => [
                        [
                            'status' => 'error',
                            'code' => 500,
                            'title' => 'score create error',
                            'detail' => $e->getMessage()
                        ]
                    ]],
                    500
                );
            }
            return new JsonResponse(
                ['errors' => [
                    [
                        'status' => 'error',
                        'code' => 500,
                        'title' => 'score create error',
                        'detail' => $e->getMessage()
                    ]
                ]],
                500
            );
        }

        return new JsonResponse(['slug' => $slug], 201);
    }

    /**
     * @param Request $request
     * @return JsonResponse|bool
     */
    public function moveScore(Request $request)
    {
        $validation = $this->validate($request, [
            'slug' => 'required',
            'folder-id' => 'numeric|required'
        ], 'move score');

        if($validation !== true){
            return $validation;
        }

        try{
            $folderId = $this->soundsliceService->moveScore(
                $request->get('slug'),
                $request->get('folder-id')
            );
        }catch(ExternalErrorException $e){
            return new JsonResponse(
                ['errors' => [
                    [
                        'status' => 'error',
                        'code' => 500,
                        'title' => 'score move error',
                        'detail' => $e->getMessage()
                    ]
                ]],
                500
            );
        }

//        if($folderId !== $request->get('folder-id')){
//            return new JsonResponse(
//                ['errors' => [
//                    [
//                        'status' => 'error',
//                        'code' => 500,
//                        'title' => 'score move error',
//                        'detail' => "request folder id of " . $folderId . " is not where the score was moved. Was " .
//                            "actually moved to " . $request->get('folder-id') . "."
//                    ]
//                ]],
//                500
//            );
//        }

        return new JsonResponse(['folder-id' => $folderId], 201);
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
        }catch(ExternalErrorException $e){
            return new JsonResponse(
                ['errors' => [
                    [
                        'status' => 'error',
                        'code' => 500,
                        'title' => 'score get failed',
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
    public function delete($slug){
        try{
            $delete = $this->soundsliceService->deleteScore($slug);
        }catch(ExternalErrorException $e){
            return new JsonResponse(
                ['errors' => [
                    [
                        'status' => 'error',
                        'code' => 500,
                        'title' => 'score delete error',
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

        $validation = $this->validate($request, ['name' => 'required'], 'create folder');
        if($validation !== true){
            return $validation;
        }

        $name = $request->get('name');
        $parentId = $request->get('parent_id');

        try{
            $folderId = $this->soundsliceService->createFolder($name, $parentId);
        }catch(ExternalErrorException $e){
            return new JsonResponse(
                ['errors' => [
                    [
                        'status' => 'error',
                        'code' => 500,
                        'title' => 'create folder failed',
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
    public function deleteFolder($id){
        try{
            $delete = $this->soundsliceService->deleteFolder($id);
        }catch(ExternalErrorException $e){
            return new JsonResponse(
                ['errors' => [
                    [
                        'status' => 'error',
                        'code' => 500,
                        'title' => 'delete folder failed',
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

        $validation = $this->validate($request, [
            'slug' => 'required',
            'asset-url' => 'required|url'
        ], 'create notation');
        if($validation !== true){
            return $validation;
        }

        try{
            $response = $this->soundsliceService->addNotation($slug, $assetUrl);
        }catch(ExternalErrorException $e){
            return new JsonResponse(
                ['errors' => [
                    [
                        'status' => 'error',
                        'code' => 500,
                        'title' => 'create notation failed',
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