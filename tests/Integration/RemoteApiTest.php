<?php

namespace Railroad\Soundslice\Tests\Acceptance;

use Dotenv\Dotenv;
use GuzzleHttp\Client;
use Railroad\Soundslice\Tests\TestCase;

/**
 *
 * https://www.soundslice.com/help/data-api/
 *
 * Class RemoteApiTest
 * @package Railroad\Soundslice\Tests\Acceptance
 */
class RemoteApiTest extends TestCase
{
    protected $auth;

    protected function setUp()
    {
        parent::setUp();

        $config = new Dotenv(__DIR__ . '/../../', '.env.testing');
        $config->load();

        $this->app['config']->set('soundslice.soundsliceAppId', env('SOUNDSLICE_APP_ID'));
        $this->app['config']->set('soundslice.soundsliceSecret', env('SOUNDSLICE_SECRET'));

        $this->auth = [env('SOUNDSLICE_APP_ID'), env('SOUNDSLICE_SECRET')];
    }

    /**
     * Response Formats:
     * ["id" => 123]
     */
    public function testCreateFolder()
    {
        $name = 'testing_' . $this->faker->word;

        $client = new Client();

        $response = $client->request(
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

        $this->assertArrayHasKey('id', $body);
        $this->assertEquals(201, $response->getStatusCode());

        // cleanup
        $client->request(
            'DELETE',
            'https://www.soundslice.com/' . 'api/v1/folders/' . $body['id'] . '/',
            [
                'auth' => $this->auth
            ]
        );
    }

    /**
     * Docs say this returns 201 but we only seem to get 200 response code.
     *
     * Response Formats:
     * ["parent_id" => 123]
     * ["parent_id" => null]
     */
    public function testDeleteFolder()
    {
        $name = 'testing_' . $this->faker->word;

        $client = new Client();

        $response = $client->request(
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

        $response = $client->request(
            'DELETE',
            'https://www.soundslice.com/' . 'api/v1/folders/' . $body['id'] . '/',
            [
                'auth' => $this->auth
            ]
        );

        $body = json_decode($response->getBody(), true);

        $this->assertArrayHasKey('parent_id', $body);
        $this->assertEquals(200, $response->getStatusCode());
    }

    /**
     * Response Formats:
     * [
     *     "url" => "/scores/123abc/"
     *     "embed_url" => "/scores/123abc/embed/"
     *     "slug" => "123abc"
     * ]
     */
    public function testCreateScore()
    {
        $name = 'testing_' . $this->faker->word;
        $artist = 'testing_' . $this->faker->word;
        $status = 1;
        $embedStatus = 4;
        $printStatus = 3;

        $client = new Client();

        $response = $client->request(
            'POST',
            'https://www.soundslice.com/' . 'api/v1/folders/',
            [
                'auth' => $this->auth,
                'form_params' => [
                    'name' => $name
                ],
            ]
        );

        $folderId = json_decode($response->getBody(), true)['id'];

        $response = $client->request(
            'POST',
            'https://www.soundslice.com/' . 'api/v1/scores/',
            [
                'auth' => $this->auth,
                'form_params' => [
                    'name' => $name,
                    'artist' => $artist,
                    'status' => $status,
                    'embed_status' => $embedStatus,
                    'print_status' => $printStatus,
                    'folder_id' => $folderId,
                ],
            ]
        );

        $body = json_decode($response->getBody(), true);

        $this->assertArrayHasKey('slug', $body);
        $this->assertArrayHasKey('url', $body);
        $this->assertArrayHasKey('embed_url', $body);
        $this->assertEquals(201, $response->getStatusCode());

        // cleanup
        $client->request(
            'DELETE',
            'https://www.soundslice.com/' . 'api/v1/scores/' . $body['slug'] . '/',
            [
                'auth' => $this->auth
            ]
        );

        $client->request(
            'DELETE',
            'https://www.soundslice.com/' . 'api/v1/folders/' . $folderId . '/',
            [
                'auth' => $this->auth
            ]
        );
    }

    /**
     * Response Formats:
     * [
     *     "url" => "/scores/123abc/"
     *     "embed_url" => "/scores/123abc/embed/"
     *     "slug" => "123abc"
     * ]
     */
    public function testDeleteScore()
    {
        $name = 'testing_' . $this->faker->word;
        $artist = 'testing_' . $this->faker->word;
        $status = 1;
        $embedStatus = 4;
        $printStatus = 3;

        $client = new Client();

        $response = $client->request(
            'POST',
            'https://www.soundslice.com/' . 'api/v1/folders/',
            [
                'auth' => $this->auth,
                'form_params' => [
                    'name' => $name
                ],
            ]
        );

        $folderId = json_decode($response->getBody(), true)['id'];

        $response = $client->request(
            'POST',
            'https://www.soundslice.com/' . 'api/v1/scores/',
            [
                'auth' => $this->auth,
                'form_params' => [
                    'name' => $name,
                    'artist' => $artist,
                    'status' => $status,
                    'embed_status' => $embedStatus,
                    'print_status' => $printStatus,
                    'folder_id' => $folderId,
                ],
            ]
        );

        $body = json_decode($response->getBody(), true);

        $response = $client->request(
            'DELETE',
            'https://www.soundslice.com/' . 'api/v1/scores/' . $body['slug'] . '/',
            [
                'auth' => $this->auth
            ]
        );

        $body = json_decode($response->getBody(), true);
        $this->assertArrayHasKey('name', $body);
        $this->assertArrayHasKey('artist', $body);

        // cleanup
        $client->request(
            'DELETE',
            'https://www.soundslice.com/' . 'api/v1/folders/' . $folderId . '/',
            [
                'auth' => $this->auth
            ]
        );
    }
}