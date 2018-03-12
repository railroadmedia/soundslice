<?php

namespace Railroad\Soundslice\Tests\Acceptance;

use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use Railroad\Soundslice\Services\SoundsliceService;
use Railroad\Soundslice\Tests\TestCase;

/**
 * http://docs.guzzlephp.org/en/stable/testing.html#mock-handler
 *
 * Class SoundsliceServiceTest
 * @package Railroad\Soundslice\Tests\Acceptance
 */
class SoundsliceServiceTest extends TestCase
{
    /**
     * @var $soundSliceService SoundsliceService
     */
    protected $soundSliceService;

    protected function setUp()
    {
        parent::setUp();

        $this->soundSliceService = $this->app->make(SoundsliceService::class);
    }

    public function testCreateFolder()
    {
        $name = 'testing_' . time() . '_' . $this->faker->word;
        $parentId = null;

        $expectedFolderId = rand();

        $mock = new MockHandler([new Response(201, [], json_encode(['id' => $expectedFolderId]))]);

        $this->soundSliceService = new SoundsliceService(new Client(['handler' => HandlerStack::create($mock)]));

        $folderId = $this->soundSliceService->createFolder(
            $name,
            $parentId
        );

        $this->assertEquals($expectedFolderId, $folderId);
    }

    public function testDeleteFolder()
    {
        $expectedParentId = rand();

        $mock = new MockHandler([new Response(201, [], json_encode(['parent_id' => $expectedParentId]))]);

        $this->soundSliceService = new SoundsliceService(new Client(['handler' => HandlerStack::create($mock)]));

        $parentId = $this->soundSliceService->deleteFolder(rand());

        $this->assertEquals($expectedParentId, $parentId);
    }

    public function testCreateScore()
    {
        $expectedSlug = rand();
        $expectedUrl = $this->faker->url;
        $expectedEmbed = $this->faker->url;

        $mock = new MockHandler(
            [
                new Response(
                    201, [], json_encode(
                        [
                            'slug' => $expectedSlug,
                            'url' => $expectedUrl,
                            'embed_url' => $expectedEmbed,
                        ]
                    )
                )
            ]
        );

        $this->soundSliceService = new SoundsliceService(new Client(['handler' => HandlerStack::create($mock)]));

        $slug = $this->soundSliceService->createScore(
            $this->faker->word,
            $this->faker->word,
            rand(),
            true,
            true,
            true
        );

        $this->assertEquals($expectedSlug, $slug);
    }

    public function testDeleteScore()
    {
        $expectedName = $this->faker->word;
        $expectedArtist = $this->faker->word;

        $mock = new MockHandler(
            [
                new Response(
                    201, [], json_encode(
                        [
                            'name' => $expectedName,
                            'artist' => $expectedArtist,
                        ]
                    )
                )
            ]
        );

        $this->soundSliceService = new SoundsliceService(new Client(['handler' => HandlerStack::create($mock)]));

        $name = $this->soundSliceService->deleteScore($this->faker->word);

        $this->assertEquals($expectedName, $name);
    }
}