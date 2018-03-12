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

    public function testCreateFolderNoParent()
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
}