<?php

namespace Railroad\Soundslice\Services;

use GuzzleHttp;
use Illuminate\Http\JsonResponse;
use PHPUnit\Util\Json;

class SoundsliceService
{
    const TYPE_FOR_SCORE= 'soundslice-score';
    const KEY_FOR_SLUG = 'soundslice-slug';
    const KEY_FOR_HASH = 'soundslice-upload-hash';

    /**
     * SoundsliceService constructor.
     */
    public function __construct()
    {

    }

    private function request($uri, $method = 'GET', $options = [], $withAuth = true, $entireUrl = '')
    {
        $uri = 'https://www.soundslice.com/' . ltrim($uri, '/'); // so doesn't matter if param has leading slash

        $uri = !empty($entireUrl) ? $entireUrl : $uri;

        if(substr($uri, -1) !== '/'){ // ensure has trailing slash
            $uri = $uri . '/';
        }

        if($withAuth){ // At least one method needs to *not* pass authentication.
            $options['auth'] = [env('SOUNDSLICE_APP_ID'), env('SOUNDSLICE_SECRET')];
        }

        $client = new GuzzleHttp\Client();
        return $client->request($method, $uri, $options);
    }

    /**
     * @param string $name
     * @param int|string $folderId
     * @param string $artist
     * @param bool $published
     * @param bool $embedGlobally
     * @param bool $embedWhiteListOnly
     * @param bool $printingAllowed
     * @return array|bool|JsonResponse
     */
    public function createScore(
        $name,
        $folderId = '',
        $artist = '',
        $published = false,
        $embedWhiteListOnly = false,
        $embedGlobally = false,
        $printingAllowed = false
    )
    {
        $response = $this->request( // https://www.soundslice.com/help/data-api/#createscore
            'api/v1/scores/',
            'POST',
            [
                'form_params' => [
                    'name' => $name,
                    'artist' => $artist,
                    'status' => $published ? 3 : 1,
                    'embed_status' => $embedWhiteListOnly ? 4 : ($embedGlobally ? 2 : 1),
                    'print_status' => $printingAllowed ? 3 : 1,
                    'folder_id' => $folderId
                ]
            ]
        );

        $body = json_decode((string) $response->getBody());
        $code = json_decode((string) $response->getStatusCode());
        // $reason = json_decode((string) $response->getReasonPhrase());

        // catch unsuccessful request
        if(!preg_match('/^(?=.{3})[2]\d*$/', $code)){
            if($code !== 422 ){
                error_log('status ' . $code . ' not expected.');
            }
            error_log('Failed with error:"' . print_r($body->errors ?? '', true) . '"');
            return ['success' => false, 'error' => $body->errors ?? 'Very broken. Check the logs.'];
        }
        if($code !== 201) {
            error_log('succeeded but with unexpected code (' . $code . ')');
        }
        if(empty($body->slug){
            return ['success' => false, 'error' => 'Succeeded but no value returned for slug'];
        }

        return ['success' => true, 'slug' => $body->slug];
    }

    /**
     * @param $slug
     * @param $assetUrl
     * @return array
     */
    public function createNotation($slug, $assetUrl)
    {
        // get asset file data

        $tmp_handle = fopen('php://temp', 'r+');
        fwrite($tmp_handle, $assetUrl);
        rewind($tmp_handle);
        $fileContents = stream_get_contents($tmp_handle); // stackoverflow.com/q/9287368

        // Soundslice-Notation-Upload Step 1: https://www.soundslice.com/help/data-api/#putnotation

        $urlResponse = $this->request('https://www.soundslice.com/api/v1/scores/' . $slug . '/notation/','POST');
        $providedUploadUrl = ((array) json_decode((string) $urlResponse->getBody()))['url'];

        if(json_decode((string) $urlResponse->getStatusCode()) !== 201){
            return [
                'success' => false,
                'error' => 'Soundslice API Response didn\'t match expected 201 status'
            ];
        }

        // Soundslice-Notation-Upload Step 2: https://www.soundslice.com/help/data-api/#putnotation

        $notationResponse = $this->request(
            '',
            'PUT',
            ['body' => $fileContents],
            false,
            $providedUploadUrl
        );

        // We're done; close everything up

        fclose($tmp_handle); // clean up temporary storage handle

        if($notationResponse->getStatusCode() !== 200){
            return [
                'success' => false,
                'error' => 'Soundslice API Response for notation POST didn\'t match expected 200 status'
            ];
        }

        return ['success' => true];
    }

//    public function list()
//    {
//        $response = $this->request('api/v1/scores/');
//
//        $body = (array) json_decode((string) $response->getBody());
//
//        return $body;
//    }


// todo: make this

//    public function createFolder($name)
//    {
//        $response = $this->request('api/v1/folders/', 'POST', ['form_params' => ['name' => $name]]);
//        $body = (array) json_decode((string) $response->getBody());
//        return $body['id'] ?? false;
//    }


// todo: make this

//    public function deleteFolder($id)
//    {
//        $uri = 'api/v1/folders/' . (string) $id;
//        $response = $this->request($uri, 'DELETE');
//
//        //$body = (array) json_decode((string) $response->getBody());
//        $status = json_decode((string) $response->getStatusCode());
//
//        $success = ($status == 201 || $status == 200) ?? false;
//
//        return $success;
//    }


// todo: make this

//    public function get($slug)
//    {
//        $response = $this->request('api/v1/scores/' . $slug);
//
//        if(is_null($response)){
//            return false;
//        }
//
//        $body = (array) json_decode((string) $response->getBody());
//        $status = json_decode((string) $response->getStatusCode());
//
//        if($status !== 201 && $status !== 200){ // Soundslice's docs says expect 201, but we're getting 200. No idea why.
//            return false;
//        }
//
//        return $body;
//    }


// todo: make this

//    /**
//     * @param $slug
//     * @return bool
//     *
//     * https://www.soundslice.com/help/data-api/#deletescore
//     */
//    public function delete($slug)
//    {
//        $uri = 'api/v1/scores/' . $slug;
//        $response = $this->request($uri, 'delete');
//
//        // $body = (array) json_decode((string) $response->getBody());
//        $status = json_decode((string) $response->getStatusCode());
//
//        return $status == 201;
//    }
}

