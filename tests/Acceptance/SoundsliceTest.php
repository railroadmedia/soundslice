<?php

namespace Railroad\Soundslice\Tests\Acceptance;

use Illuminate\Routing\RouteCollection;
use Illuminate\Routing\Router;
use Illuminate\Support\Facades\File;
use Railroad\Soundslice\Services\SoundsliceService;
use Railroad\Soundslice\Tests\TestCase;

class SoundsliceTest extends TestCase
{
    const TEST_FOLDER_ID = 5232;
    const S3_DIR = 'soundslice-dev-1802';

    private $folderId;
    private $dummyScoresToDeleteOnTearDown;

    /** @var $soundSliceService SoundsliceService */
    protected $soundSliceService;

    protected function setUp()
    {
        parent::setUp();

        $config = new \Dotenv\Dotenv(__DIR__ . '/../../', '.env.testing');
        $config->load();

        $this->app['config']->set('soundslice.soundsliceAppId', env('SOUNDSLICE_APP_ID'));
        $this->app['config']->set('soundslice.soundsliceSecret', env('SOUNDSLICE_SECRET'));

        $this->soundSliceService = $this->app->make(SoundsliceService::class);
    }

    protected function tearDown()
    {
        if(!empty($this->dummyScoresToDeleteOnTearDown)){
            $this->deleteDummyScores();
        }

        if(isset($this->folderId)){
            $this->deleteDummyFolder();
        }

        parent::tearDown();
    }



    private function createDummyFolder(){
        try{
            $this->folderId = $this->soundSliceService->createFolder($this->getNameForADummy('folder'));
        }catch(\Exception $ce){
            $this->fail('"SoundsliceTest::createDummyFolder" failed');
        }

        $this->log_folder_id($this->folderId);

        $this->assertTrue(!empty($this->folderId));
    }



    private function deleteDummyScores(){
        $failed = [];
        foreach($this->dummyScoresToDeleteOnTearDown as $scoreSlug){
            try{
                if(!$this->soundSliceService->deleteScore($scoreSlug)){
                    $failed[] = $scoreSlug;
                }
            }catch(\Exception $ce){
                $this->fail('"SoundsliceTest::deleteDummyScores" failed');
            }
        }
        if(!empty($failed)){
            $this->fail('failed to delete a dummy score created for this test (' .
                print_r($this->dummyScoresToDeleteOnTearDown, true) . ').'
            );
        }
    }



    private function deleteDummyFolder(){
        try{
            $success = $this->soundSliceService->deleteFolder($this->folderId);
        }catch(\Exception $e){
            $this->fail('"SoundsliceTest::deleteDummyFolder" failed');
        }

        if(!$success){
            $this->fail('failed to delete dir created for this test (' . $this->folderId . ').');
        }
    }



    private function getNameForADummy($specialTerm = ''){
        if(!empty($specialTerm)){
            $specialTerm = $specialTerm . '_';
        }
        return 'TEST_ ' . $specialTerm . 'SoundsliceServiceTest_' . time() . '_' . rand(000, 999);
    }

    public function log_folder_id($folderId)
    {
        File::append(__DIR__ . '/../../to-delete-folders.txt', $folderId. ',' . PHP_EOL);
    }

    public function log_score_slug($scoreSlug)
    {
        File::append(__DIR__ . '/../../to-delete-scores.txt', $scoreSlug. ',' . PHP_EOL);
    }

    // -----------------------------------------------------------------------------------------------------------------



    public function test_create_score()
    {
        $this->createDummyFolder(); // sets $this->folderId

        $name = 'nameFoo ' . $this->faker->words(rand(1,3), true);
        $artist = 'artistFoo ' . $this->faker->words(rand(1,3), true);

        $response = $this->call('PUT', '/soundslice/create', [
            'name' => $name,
            'artist' => $artist,
            'folder-id' => $this->folderId
        ]);

        $response = (array) json_decode($response->getContent());

        $this->assertNotEmpty($response['slug']);

        if(isset($response['slug'])){
            $this->log_score_slug($response['slug']);
            $this->dummyScoresToDeleteOnTearDown[] = $response['slug'];
        }
    }



    public function test_create_score_fails_folder_id_not_whole_number()
    {
        $name = 'nameFoo ' . $this->faker->words(rand(1,3), true);
        $artist = 'artistFoo ' . $this->faker->words(rand(1,3), true);

        $response = $this->call('PUT', '/soundslice/create', [
            'name' => $name,
            'artist' => $artist,
            'folder-id' => $this->faker->words(rand(6,12), true)
        ]);

        $expected = [
            'status' => 'error',
            'code' => 500,
            'title' => 'SoundSliceJsonController@createScore failed (detail has message from Soundslice)',
            'detail' => 'Client error: `POST https://www.soundslice.com/api/v1/scores/` resulted in a `422 Unknown ' .
                'Status Code` response:' . PHP_EOL . '{"errors": {"folder_id": ["Enter a whole number."]}}' . PHP_EOL
        ];
        $actual = (array) json_decode($response->getContent())->errors{0};
        $this->assertEquals($expected, $actual);
    }


    public function test_create_score_fails_folder_does_not_exist()
    {
        $name = 'nameFoo ' . $this->faker->words(rand(1,3), true);
        $artist = 'artistFoo ' . $this->faker->words(rand(1,3), true);

        $response = $this->call('PUT', '/soundslice/create', [
            'name' => $name,
            'artist' => $artist,
            'folder-id' => rand(999999999900000, 999999999999999)
        ]);

        $expected = [
            'status' => 'error',
            'code' => 500,
            'title' => 'SoundSliceJsonController@createScore failed (detail has message from Soundslice)',
            'detail' => 'Client error: `POST https://www.soundslice.com/api/v1/scores/` resulted in a `422 Unknown ' .
                'Status Code` response:' . PHP_EOL . '{"errors": {"folder_id": ["This folder ID is invalid."]}}' . PHP_EOL
        ];
        $actual = (array) json_decode($response->getContent())->errors{0};
        $this->assertEquals($expected, $actual);
    }



    public function test_create_score_fails_already_exists()
    {
        $this->markTestIncomplete();
    }



    public function test_create_score_validation_fail()
    {
        $this->markTestIncomplete();
    }



    public function test_list()
    {
        // todo: setup content to expect

        $response = $this->call('GET', 'soundslice/list');

        $content = json_decode($response->getContent());

        $this->assertNotEquals('404', $response->getStatusCode());
    }



    public function test_get_score()
    {
        $this->createDummyFolder(); // sets $this->folderId

        $name = 'nameFoo ' . $this->faker->words(rand(1,3), true);
        $artist = 'artistFoo ' . $this->faker->words(rand(1,3), true);

        $responseToCreate = $this->call('PUT', '/soundslice/create', [
            'name' => $name,
            'artist' => $artist,
            'folder-id' => $this->folderId
        ]);

        $responseToCreate = (array) json_decode($responseToCreate->getContent());

        if(empty($responseToCreate['slug'])){
            $this->fail('\"$response[\'slug\']\" should not be empty.');
        }

        $slug = $responseToCreate['slug'];

        $this->log_score_slug($slug);
        $this->dummyScoresToDeleteOnTearDown[] = $slug;

        $response = $this->call('get', '/soundslice/get/' . $slug);

        $score = (array) json_decode($response->getContent())->score;

        $this->assertEquals(1, $score['status']);
        $this->assertEquals(true, $score['show_notation']);
        $this->assertEquals(1, $score['print_status']);
        $this->assertEquals(false, $score['can_print']);
        $this->assertEquals(1, $score['embed_status']);
        $this->assertEquals($name, $score['name']);
        $this->assertEquals($artist, $score['artist']);
        $this->assertEquals('/scores/' . $slug . '/' , $score['url']);
        $this->assertEquals(0, $score['recording_count']);
        $this->assertEquals(false, $score['has_notation']);

        $this->assertNotEmpty($response);
    }



    public function test_get_score_not_found()
    {
        $this->markTestIncomplete();
    }



    public function test_delete_score()
    {
        $this->markTestIncomplete();
    }



    public function test_delete_score_not_found()
    {
        $this->markTestIncomplete();
    }



    public function test_delete_score_validation_failure()
    {
        $this->markTestIncomplete();
    }



    public function test_create_folder()
    {
        $this->markTestIncomplete();
    }



    public function test_create_folder_validation_failure()
    {
        $this->markTestIncomplete();
    }



    public function test_delete_folder()
    {
        $this->markTestIncomplete();
    }



    public function test_delete_folder_not_found()
    {
        $this->markTestIncomplete();
    }



    public function test_delete_folder_validation_failure()
    {
        $this->markTestIncomplete();
    }



    public function test_create_notation()
    {
        $this->markTestIncomplete();
    }



    public function test_create_notation_validation_failure()
    {
        $this->markTestIncomplete();
    }



    public function test_create_notation_upload_more_than_one()
    {
        $this->markTestIncomplete();
    }



    public function test_create_notation_with_same_values()
    {
        $this->markTestIncomplete();
    }
}