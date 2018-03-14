<?php

namespace Railroad\Soundslice\Tests\Acceptance;

use Illuminate\Routing\RouteCollection;
use Illuminate\Routing\Router;
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
            $this->dummyScoresToDeleteOnTearDown[] = $response['slug'];
        }
    }

    public function test_create_score_fails_folder_does_not_exist()
    {
        $name = 'nameFoo ' . $this->faker->words(rand(1,3), true);
        $artist = 'artistFoo ' . $this->faker->words(rand(1,3), true);

        $response = $this->call('PUT', '/soundslice/create', [
            'name' => $name,
            'artist' => $artist,
            'folder-id' => $this->faker->words(rand(6,12), true)
        ]);


        $this->markTestIncomplete();
        $content = $response->getContent();
    }

    public function test_create_score_validation_fail()
    {
        $this->markTestIncomplete();
    }

    public function test_list()
    {
        $response = $this->call('GET', 'soundslice/list');

        $content = json_decode($response->getContent());

        $this->assertNotEquals('404', $response->getStatusCode());
    }

    public function test_get_score()
    {
        $this->markTestIncomplete();
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
}