<?php

namespace Railroad\Soundslice\Tests\Acceptance;

use Illuminate\Routing\RouteCollection;
use Illuminate\Routing\Router;
use Illuminate\Support\Facades\File;
use Railroad\Soundslice\Services\SoundsliceService;
use Railroad\Soundslice\Tests\TestCase;

class SoundsliceTest extends TestCase
{
    const TEST_FOLDER_ID = 5619;
    const S3_DIR = 'soundslice-dev-1802';

    const fileWithSlugsOfDummyScores = __DIR__ . '/../../to-delete-scores.txt';
    const fileWithIdsOfDummyFolders = __DIR__ . '/../../to-delete-folders.txt';

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

        $this->createDummyFolder();
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

    private function createDummyFolder($parentId = self::TEST_FOLDER_ID){
        if(isset($this->folderId)){
            return;
        }

        try{
            $this->folderId = $this->soundSliceService->createFolder($this->getNameForADummy('folder'), $parentId);
        }catch(\Exception $ce){
            $this->fail('"SoundsliceTest::createDummyFolder" failed');
        }

        $this->log_folder_id($this->folderId);

        if(empty($this->folderId)){
            $this->fail('dummy folder appears to have not worked');
        }
    }

    private function createDummyScore($name = '', $artist = ''){
        $this->createDummyFolder(); // sets $this->folderId

        $name = empty($name) ? 'nameFoo ' . $this->faker->words(rand(1,3), true): $name;
        $artist = empty($artist) ? 'artistFoo ' . $this->faker->words(rand(1,3), true): $artist;

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

        $expected = [
            'status' => 1,
            'show_notation' => true,
            'print_status' => 1,
            'can_print' => false,
            'embed_status' => 1,
            'name' => $name,
            'artist' => $artist,
            'url' => '/scores/' . $slug . '/',
            'recording_count' => 0,
            'has_notation' => false
        ];

        if($expected !== $score){
            $this->fail('Dummy score not as expected. Actual: ' . print_r($score, true) . '. End.');
        };

        $score['slug'] = $slug;

        return $score;
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

    private function log_folder_id($folderId)
    {
        File::append(self::fileWithIdsOfDummyFolders, PHP_EOL . $folderId);
    }

    private function log_score_slug($scoreSlug)
    {
        File::append(self::fileWithSlugsOfDummyScores, $scoreSlug . PHP_EOL);
    }

    private function countScoresInAccount(){
        $scores = $this->soundSliceService->listScores();

        return count($scores);
    }


    /**
     * This isn't actually a test - it's just a way of deleting scores created in testing. Otherwise we have to
     * go into the soundslice "slice manager" web UI and manually delete each one. Fuck that. Just look in
     * "to-delete-scores.txt" in the root of this project to see a list of slugs representing the scores created
     * in the process of running these tests. Merry xmas. Jonathan, 2018
     */
//    public function test_delete_dummy_content_on_soundslice()
//    {
//        $this->delete_dummy_scores_on_soundslice();
//        echo PHP_EOL;
//        $this->delete_dummy_folders_on_soundslice();
//    }

    private function delete_dummy_scores_on_soundslice()
    {
        $toDelete = file(self::fileWithSlugsOfDummyScores);

        foreach($toDelete as &$slug){
            $slug = rtrim($slug);
        }

        echo 'Attempting to delete ' . count($toDelete) . ' scores.' . PHP_EOL;

        $beforeCount = $this->countScoresInAccount();

        foreach($toDelete as $slug){
            try{
                $this->soundSliceService->deleteScore($slug);
            }catch(\Exception $e){
                // $deleteFailed[$slug] = $e->getMessage();
            }
        }

        echo '---------- Deleted ' . $beforeCount - $this->countScoresInAccount() . ' scores. ----------' . PHP_EOL;

        $itFuckenWorked = true;
        $this->assertTrue($itFuckenWorked);

        file_put_contents(self::fileWithSlugsOfDummyScores, '');
    }

    private function delete_dummy_folders_on_soundslice()
    {
        $toDelete = file(self::fileWithIdsOfDummyFolders);

        foreach($toDelete as &$id){
            $id = rtrim($id);
        }

        $toDelete = array_filter($toDelete, function($value) { return $value !== ''; });

        echo 'Attempting to delete ' . count($toDelete) . ' folders.' . PHP_EOL;

        $numberDeleted = 0;

        foreach($toDelete as $folderId){
            try{
                if($this->soundSliceService->deleteFolder($folderId)){
                    $numberDeleted++;
                }
            }catch(\Exception $e){
                // $deleteFailed[$folderId] = $e->getMessage();
            }
        }

        echo '---------- Deleted ' . $numberDeleted . ' folders. ----------' . PHP_EOL;

        $itFuckenWorked = true;

        $this->assertTrue($itFuckenWorked);

        file_put_contents(self::fileWithIdsOfDummyFolders, '');

        $this->folderId = null;
    }

    /**
     * @return string
     *
     * Gets a dummy slug and ensures it's not already in use. This is important because there is no test environment
     * for soundslice - we're using the production "database" for our tests! Thus, if you need to test that delete
     * doesn't work when passed some random value - first make sure that random value isn't actually a slug for some
     * score that's actually in use on a site somewhere. Sure, it's not likely, by why risk it? This provides a dummy
     * slug that you know will not collide with one in use. Merry xmas - Jonathan, 2018
     */
    private function dummySlugUniqueNotInProd(){
        $allScores = $this->soundSliceService->listScores();
        $allSlugs = [];

        foreach($allScores as $score){
            $allSlugs[] = $score['slug'];
        }

        do{
            $altSlug = (string) rand(00000, 99999);
        }while(in_array($altSlug, $allSlugs));

        return $altSlug;
    }


    // -----------------------------------------------------------------------------------------------------------------


//    public function test_dummySlugUniqueNotInProd()
//    {
//        $slug = 1234; $altSlug = $this->altSlug($slug); $this->assertNotEquals($slug, $altSlug);
//    }

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

        $expected = [
            'status' => 1,
            'show_notation' => true,
            'print_status' => 1,
            'can_print' => false,
            'embed_status' => 1,
            'name' => $name,
            'artist' => $artist,
            'url' => '/scores/' . $slug . '/',
            'recording_count' => 0,
            'has_notation' => false
        ];

        $this->assertEquals($expected, $score);
    }


    public function test_get_score_not_found()
    {
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

        $altSlug = $this->dummySlugUniqueNotInProd();

        $response = $this->call('get', '/soundslice/get/' . $altSlug);

        $decodedContents = (array) json_decode($response->getContent());

        $this->assertTrue(array_key_exists('errors', $decodedContents));
        $this->assertFalse(array_key_exists('score', $decodedContents));
    }


    public function test_delete_score()
    {
        $score = $this->createDummyScore();
        $response = $this->call('delete', '/soundslice/delete/', ['slug' => $score['slug']]);
        $decodedContentAsArray = (array) json_decode($response->getContent());
        $this->assertTrue($decodedContentAsArray['deleted']);
    }


    public function test_delete_score_not_found()
    {
        $response = $this->call('delete', '/soundslice/delete/', ['slug' => $this->dummySlugUniqueNotInProd()]);
        $decodedContentAsArray = (array) json_decode($response->getContent());
        $this->assertFalse(isset($decodedContentAsArray['deleted']));
        $this->assertTrue(array_key_exists('errors', $decodedContentAsArray));
        $this->assertEquals(500, $response->getStatusCode());
    }


    public function test_delete_score_validation_failure()
    {
        $this->markTestIncomplete();
    }


    public function test_create_folder()
    {
        $this->markTestIncomplete();
    }


    public function test_create_nested_folder()
    {
        $this->markTestIncomplete();
    }


    public function test_create_folder_validation_failure()
    {
        $this->markTestIncomplete();
    }


    public function test_create_folder_failure_invalid_parent_id()
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