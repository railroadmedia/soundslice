<?php

namespace Railroad\Soundslice\Services;

use GuzzleHttp\Client;
use Illuminate\Http\JsonResponse;
use Railroad\Soundslice\Exceptions\ExternalErrorException;

class SoundsliceService
{
    const TYPE_FOR_SCORE = 'soundslice-score';
    const KEY_FOR_SLUG = 'soundslice-slug';
    const KEY_FOR_HASH = 'soundslice-upload-hash';

    private $auth;

    /**
     * @var Client
     */
    private $client;

    /**
     * @param Client $client
     */
    public function __construct(Client $client)
    {
        $this->client = $client;
        $this->auth = [app('config')['soundslice.soundsliceAppId'], app('config')['soundslice.soundsliceSecret']];
    }

    /**
     * @param string $name
     * @param int|string $folderId
     * @param string $artist
     * @param bool $publiclyListed
     * @param bool $embedGlobally
     * @param bool $embedWhiteListOnly
     * @param bool $printingAllowed
     * @return array|bool|JsonResponse
     * @throws ExternalErrorException
     */
    public function createScore(
        $name,
        $artist = '',
        $folderId = '',
        $publiclyListed = false,
        $embedWhiteListOnly = false,
        $embedGlobally = false,
        $printingAllowed = false
    ) {
        try{
            $response = $this->client->request(
                'POST',
                'https://www.soundslice.com/api/v1/scores/',
                [
                    'auth' => $this->auth,
                    'form_params' => [
                        'name' => $name,
                        'artist' => $artist,
                        'status' => $publiclyListed ? 3 : 1,
                        'embed_status' => $embedWhiteListOnly ? 4 : ($embedGlobally ? 2 : 1),
                        'print_status' => $printingAllowed ? 3 : 1,
                        'folder_id' => $folderId,
                    ],
                ]
            );
        }catch(\Exception $e){
            throw new ExternalErrorException($e->getMessage(), $e->getCode());
        }

        $body = json_decode($response->getBody(), true);
        $code = $response->getStatusCode();

        if ($code !== 201) {
            throw new ExternalErrorException($body['error'], $code);
        }

        return $body['slug'];
    }

    /**
     * @param $slug
     * @param $folderId
     * @return mixed
     * @throws ExternalErrorException
     */
    public function moveScore($slug, $folderId)
    {
        $url = 'https://www.soundslice.com/api/v1/scores/' . $slug . '/move/';

        $options = [
            'auth' => $this->auth,
            'form_params' => [
                'folder_id' => $folderId
            ]
        ];

        try{
            $response = $this->client->request(
                'POST',
                $url,
                $options
            );
        }catch(\Exception $e){
            throw new ExternalErrorException($e->getMessage(), $e->getCode());
        }

        $body = json_decode($response->getBody(), true);
        $code = $response->getStatusCode();


        if ($code !== 201) {
            throw new ExternalErrorException($body['error'], $code);
        }

        return $body['id'];
    }

    /**
     * @param $slug
     * @param $assetUrl
     * @return bool
     * @throws ExternalErrorException
     */
    public function addNotation($slug, $assetUrl)
    {
        $notationXml = file_get_contents($assetUrl);

        try{
            $response = $this->client->request(
                'POST',
                'https://www.soundslice.com/api/v1/scores/' . $slug . '/notation/',
                ['auth' => $this->auth]
            );
        }catch(\Exception $e){
            throw new ExternalErrorException($e->getMessage(), $e->getCode());
        }

        $body = json_decode($response->getBody(), true);
        $code = $response->getStatusCode();

        if ($code !== 201) {
            throw new ExternalErrorException($body['error'], $code);
        }

        try{
            $response = $this->client->request(
                'PUT',
                $body['url'],
                ['body' => $notationXml]
            );
        }catch(\Exception $e){
            throw new ExternalErrorException($e->getMessage(), $e->getCode());
        }

        $body = json_decode($response->getBody(), true);
        $code = $response->getStatusCode();

        if ($code !== 201 && $code !== 200) {
            throw new ExternalErrorException($body['error'], $code);
        }

        return true;
    }

    /**
     * @return array
     * @throws ExternalErrorException
     */
    public function listScores()
    {
        try {
            $response = $this->client->request(
                'GET',
                'https://www.soundslice.com/' . 'api/v1/scores/',
                [
                    'auth' => $this->auth
                ]
            );
        }catch(\Exception $e){
            throw new ExternalErrorException($e->getMessage(), $e->getCode());
        }

        $body = json_decode($response->getBody(), true);

        return $body;
    }

    /**
     * @param $name
     * @param $parentId
     * @return bool
     * @throws ExternalErrorException
     */
    public function createFolder($name, $parentId = '')
    {
        try {
            $response = $this->client->request(
                'POST',
                'https://www.soundslice.com/' . 'api/v1/folders/',
                [
                    'auth' => $this->auth,
                    'form_params' => [
                        'name' => $name,
                        'parent_id' => $parentId
                    ],
                ]
            );
        }catch(\Exception $e){
            throw new ExternalErrorException($e->getMessage(), $e->getCode());
        }

        $body = json_decode($response->getBody(), true);
        $code = $response->getStatusCode();

        if ($code !== 201) {
            throw new ExternalErrorException($body['error'], $code);
        }

        return $body['id'];
    }

    /**
     * @param $id
     * @return bool
     * @throws ExternalErrorException
     */
    public function deleteFolder($id)
    {
        try{
            $response = $this->client->request(
                'DELETE',
                'https://www.soundslice.com/' . 'api/v1/folders/' . $id . '/',
                [
                    'auth' => $this->auth
                ]
            );
        }catch(\Exception $e){
            throw new ExternalErrorException($e->getMessage(), $e->getCode());
        }

        $body = json_decode($response->getBody(), true);
        $code = $response->getStatusCode();

        if ($code !== 201 && $code !== 200) { // soundslice docs say expect 201, but we actually get 200
            throw new ExternalErrorException($body['error'], $code);
        }

        return true;
    }

    /**
     * @param $slug
     * @return mixed
     * @throws ExternalErrorException
     */
    public function getScore($slug)
    {
        try{
            $response = $this->client->request(
                'GET',
                'https://www.soundslice.com/' . 'api/v1/scores/' . $slug . '/',
                [
                    'auth' => $this->auth
                ]
            );
        }catch(\Exception $e){
            throw new ExternalErrorException($e->getMessage(), $e->getCode());
        }

        $body = json_decode($response->getBody(), true);
        $code = $response->getStatusCode();

        if ($code !== 200) {
            throw new ExternalErrorException($body['error'], $code);
        }

        return $body;
    }

    /**
     *
     * @param $slug
     * @return bool
     * @throws ExternalErrorException
     */
    public function deleteScore($slug)
    {
        try {
            $response = $this->client->request(
                'DELETE',
                'https://www.soundslice.com/' . 'api/v1/scores/' . $slug . '/',
                [
                    'auth' => $this->auth
                ]
            );
        }catch(\Exception $e){
            throw new ExternalErrorException($e->getMessage(), $e->getCode());
        }

        $body = json_decode($response->getBody(), true);
        $code = $response->getStatusCode();

        if ($code !== 201) {
            throw new ExternalErrorException($body['error'], $code);
        }

        return true;
    }
}

