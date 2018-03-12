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
        $name = 'testing_' . time() . '_' . $this->faker->word;

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
        $name = 'testing_' . time() . '_' . $this->faker->word;

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
        $name = 'testing_' . time() . '_' . $this->faker->word;
        $artist = 'testing_' . time() . '_' . $this->faker->word;
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
        $name = 'testing_' . time() . '_' . $this->faker->word;
        $artist = 'testing_' . time() . '_' . $this->faker->word;
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

    /**
     * Response Formats:
     * [
     *     "status" => 1
     *     "print_status" => 3
     *     "embed_status" => 4
     *     "recording_count" => 0
     *     "show_notation" => true
     *     "can_print" => true
     *     "embed_url" => "/scores/123abc/embed/"
     *     "name" => "testing_qui"
     *     "artist" => "testing_odit"
     *     "url" => "/scores/123abc/"
     *     "has_notation" => false
     * ]
     */
    public function testGetScore()
    {
        $name = 'testing_' . time() . '_' . $this->faker->word;
        $artist = 'testing_' . time() . '_' . $this->faker->word;
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

        $scoreSlug = json_decode($response->getBody(), true)['slug'];

        $response = $client->request(
            'GET',
            'https://www.soundslice.com/' . 'api/v1/scores/' . $scoreSlug . '/',
            [
                'auth' => $this->auth
            ]
        );

        $body = json_decode($response->getBody(), true);

        $this->assertEquals(
            [
                'name' => $name,
                'artist' => $artist,
                'status' => $status,
                'embed_status' => $embedStatus,
                'print_status' => $printStatus,
                'recording_count' => 0,
                'show_notation' => true,
                'can_print' => true,
                'embed_url' => '/scores/' . $scoreSlug . '/embed/',
                'url' => '/scores/' . $scoreSlug . '/',
                'has_notation' => false,
            ],
            $body
        );

        // cleanup
        $client->request(
            'DELETE',
            'https://www.soundslice.com/' . 'api/v1/scores/' . $scoreSlug . '/',
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
     *     [
     *         "status" => 1
     *         "print_status" => 3
     *         "embed_status" => 4
     *         "recording_count" => 0
     *         "show_notation" => true
     *         "can_print" => true
     *         "name" => "testing_qui"
     *         "artist" => "testing_odit"
     *         "has_notation" => false
     *     ],
     *     ...
     * ]
     */
    public function testListScores()
    {
        $name = 'testing_' . time() . '_' . $this->faker->word;
        $artist = 'testing_' . time() . '_' . $this->faker->word;
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

        $scoreSlug = json_decode($response->getBody(), true)['slug'];

        $response = $client->request(
            'GET',
            'https://www.soundslice.com/' . 'api/v1/scores/',
            [
                'auth' => $this->auth
            ]
        );

        $body = json_decode($response->getBody(), true);

        $this->assertContains(
            $name,
            array_column($body, 'name')
        );

        // cleanup
        $client->request(
            'DELETE',
            'https://www.soundslice.com/' . 'api/v1/scores/' . $scoreSlug . '/',
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
     * Docs say this returns 201 but we only seem to get 200 response code.
     *
     * Response Formats:
     * [
     *     [
     *         "status" => 1
     *         "print_status" => 3
     *         "embed_status" => 4
     *         "recording_count" => 0
     *         "show_notation" => true
     *         "can_print" => true
     *         "name" => "testing_qui"
     *         "artist" => "testing_odit"
     *         "has_notation" => false
     *     ],
     *     ...
     * ]
     */
    public function testPutNotation()
    {
        $name = 'testing_' . time() . '_' . $this->faker->word;
        $artist = 'testing_' . time() . '_' . $this->faker->word;
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

        $scoreSlug = json_decode($response->getBody(), true)['slug'];

        $response = $client->request(
            'POST',
            'https://www.soundslice.com/' . 'api/v1/scores/' . $scoreSlug . '/notation/',
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

        $this->assertNotEmpty($body['url']);

        $putUrl = $body['url'];
        $notationXml = file_get_contents(__DIR__ . '/../resources/soundslice-notation-xml-example.xml');

        $response = $client->request(
            'PUT',
            $putUrl,
            [
                'body' => $notationXml,
            ]
        );

        $this->assertEquals(200, $response->getStatusCode());

        // cleanup
        $client->request(
            'DELETE',
            'https://www.soundslice.com/' . 'api/v1/scores/' . $scoreSlug . '/',
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

}