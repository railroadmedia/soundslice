<?php

namespace Railroad\Soundslice\Services;

use Exception;
use GuzzleHttp\Client;
use Illuminate\Http\JsonResponse;

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
     * @throws Exception
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

        $body = json_decode($response->getBody(), true);
        $code = $response->getStatusCode();

        if ($code !== 201) {
            // todo: replace with custom exception class
            throw new Exception($body['error'], $code);
        }

        return $body['slug'];
    }

    /**
     * @param $slug
     * @param $assetUrl
     * @return bool
     * @throws Exception
     */
    public function addNotation($slug, $assetUrl)
    {
        $notationXml = file_get_contents($assetUrl);

        $response = $this->client->request(
            'POST',
            'https://www.soundslice.com/api/v1/scores/' . $slug . '/notation/',
            [
                'auth' => $this->auth,
            ]
        );

        $body = json_decode($response->getBody(), true);
        $code = $response->getStatusCode();

        if ($code !== 201) {
            // todo: replace with custom exception class
            throw new Exception($body['error'], $code);
        }

        $response = $this->client->request(
            'PUT',
            $body['url'],
            [
                'body' => $notationXml,
            ]
        );

        $body = json_decode($response->getBody(), true);
        $code = $response->getStatusCode();

        if ($code !== 201 && $code !== 200) {
            // todo: replace with custom exception class
            throw new Exception($body['error'], $code);
        }

        return true;
    }

    /**
     * @return array
     */
    public function listScores()
    {
        $response = $this->client->request(
            'GET',
            'https://www.soundslice.com/' . 'api/v1/scores/',
            [
                'auth' => $this->auth
            ]
        );

        $body = json_decode($response->getBody(), true);

        return $body;
    }

    /**
     * @param $name
     * @return bool
     * @throws Exception
     */
    public function createFolder($name)
    {
        $response = $this->client->request(
            'POST',
            'https://www.soundslice.com/' . 'api/v1/folders/',
            [
                'auth' => $this->auth,
                'form_params' => [
                    'name' => $name
                ],
            ]
        );

        $body = json_decode($response->getBody(), true);
        $code = $response->getStatusCode();

        if ($code !== 201) {
            // todo: replace with custom exception class
            throw new Exception($body['error'], $code);
        }

        return $body['id'];
    }

    /**
     * @param $id
     * @return bool
     * @throws Exception
     */
    public function deleteFolder($id)
    {
        $response = $this->client->request(
            'DELETE',
            'https://www.soundslice.com/' . 'api/v1/folders/' . $id . '/',
            [
                'auth' => $this->auth
            ]
        );

        $body = json_decode($response->getBody(), true);
        $code = $response->getStatusCode();

        if ($code !== 201) {
            // todo: replace with custom exception class
            throw new Exception($body['error'], $code);
        }

        return true;
    }

    /**
     * @param $slug
     * @return mixed
     * @throws Exception
     */
    public function getScore($slug)
    {
        $response = $this->client->request(
            'GET',
            'https://www.soundslice.com/' . 'api/v1/scores/' . $slug . '/',
            [
                'auth' => $this->auth
            ]
        );

        $body = json_decode($response->getBody(), true);
        $code = $response->getStatusCode();

        if ($code !== 201) {
            // todo: replace with custom exception class
            throw new Exception($body['error'], $code);
        }

        return $body;
    }

    /**
     *
     * @param $slug
     * @return bool
     * @throws Exception
     */
    public function deleteScore($slug)
    {
        $response = $this->client->request(
            'DELETE',
            'https://www.soundslice.com/' . 'api/v1/scores/' . $slug . '/',
            [
                'auth' => $this->auth
            ]
        );

        $body = json_decode($response->getBody(), true);
        $code = $response->getStatusCode();

        if ($code !== 201) {
            // todo: replace with custom exception class
            throw new Exception($body['error'], $code);
        }

        return true;
    }
}

