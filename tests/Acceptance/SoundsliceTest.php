<?php

namespace Railroad\Soundslice\Tests\Acceptance;

use Exception;
use Illuminate\Support\Facades\File;
use Railroad\Soundslice\Exceptions\ExternalErrorException;
use Railroad\Soundslice\Services\SoundsliceService;
use Railroad\Soundslice\Tests\TestCase;

class SoundsliceTest extends TestCase
{
    const TEST_FOLDER_ID = 6538;

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



    // 0. helper functions ---------------------------------------------------------------------------------------------

    private function createDummyFolder($parentId = self::TEST_FOLDER_ID, $returnNew = false){
        if(isset($this->folderId) && $returnNew === false){
            return $this->folderId;
        }

        $folderId = '';

        try{
            $folderId = $this->soundSliceService->createFolder(
                $this->getNameForADummy('folder'), $parentId
            );
        }catch(ExternalErrorException $e){
            $this->fail('"SoundsliceTest::createDummyFolder" failed');
        }

        $this->log_folder_id($folderId);

        if($returnNew){
            return $folderId;
        }else{
            $this->folderId = $folderId;
            return $this->folderId;
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
        foreach($this->dummyScoresToDeleteOnTearDown as $scoreSlug){
            try{
                $this->soundSliceService->deleteScore($scoreSlug);
            }catch(Exception $e){
                // meh
            }
        }
        $this->dummyScoresToDeleteOnTearDown = [];
    }

    private function deleteDummyFolder(){
        try{
            $this->soundSliceService->deleteFolder($this->folderId);
        }catch(Exception $e){
            // meh
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
        File::append(self::fileWithSlugsOfDummyScores, PHP_EOL . $scoreSlug);
    }

    private function countScoresInAccount(){
        $scores = $this->soundSliceService->listScores();

        return count($scores);
    }

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
            }catch(ExternalErrorException $e){
                // $deleteFailed[$slug] = $e->getMessage();
            }
        }

        echo '---------- Deleted ' . ($beforeCount - $this->countScoresInAccount()) . ' scores. ----------' . PHP_EOL;

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
            }catch(ExternalErrorException $e){
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
    // -----------------------------------------------------------------------------------------------------------------
    // -----------------------------------------------------------------------------------------------------------------


    // 1. test_create_score --------------------------------------------------------------------------------------------

    public function test_create_score()
    {
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

    public function test_create_score_fails_validation_folder_id_not_whole_number()
    {
        $name = 'nameFoo ' . $this->faker->words(rand(1,3), true);
        $artist = 'artistFoo ' . $this->faker->words(rand(1,3), true);

        $response = $this->call('PUT', '/soundslice/create', [
            'name' => $name,
            'artist' => $artist,
            'folder-id' => $this->faker->words(rand(6,12), true)
            // 'folder-id' => $this->folderId
        ]);

        $expected = json_encode(['errors' => [[
            'status' => 'Bad Request',
            'code' => 400,
            'title' => 'create score request validation failure',
            'detail' => ['folder-id' => [0 => 'The folder-id must be a number.']]
        ]]]);

        $actual = $response->getContent();

        $this->assertEquals(400, $response->getStatusCode());
        $this->assertEquals($expected, $actual);
    }

    public function test_create_score_succeeds_despite_name_missing()
    {
        $response = $this->call('PUT', '/soundslice/create', []);

        $this->assertEquals(201, $response->getStatusCode());
    }

    public function test_create_score_fails_validation_name_too_long()
    {
        do{
            $name = $this->faker->text(266);
            $length = strlen($name);
        }while($length < 256);

        $response = $this->call('PUT', '/soundslice/create', ['name' => $name]);

        $expected = json_encode(['errors' => [[
            'status' => 'Bad Request',
            'code' => 400,
            'title' => 'create score request validation failure',
            'detail' => ['name' => [0 => 'The name may not be greater than 255 characters.']]
        ]]]);

        $actual = $response->getContent();

        $this->assertEquals(400, $response->getStatusCode());
        $this->assertEquals($expected, $actual);
    }

    public function test_create_score_fails_validation_artist_too_long()
    {
        do{
            $artist = $this->faker->text(266);
            $length = strlen($artist);
        }while($length < 255);

        $response = $this->call('PUT', '/soundslice/create', [
            'artist' => $artist,
        ]);

        $expected = json_encode(['errors' => [[
            'status' => 'Bad Request',
            'code' => 400,
            'title' => 'create score request validation failure',
            'detail' => ['artist' => [0 => 'The artist may not be greater than 255 characters.']]
        ]]]);

        $actual = $response->getContent();

        $this->assertEquals(400, $response->getStatusCode());
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
            'title' => 'score create error',
            'detail' => 'Client error: `POST https://www.soundslice.com/api/v1/scores/` resulted in a `422 Unknown ' .
                'Status Code` response:' . PHP_EOL . '{"errors": {"folder_id": ["This folder ID is invalid."]}}' . PHP_EOL
        ];
        $actual = (array) json_decode($response->getContent())->errors{0};
        $this->assertEquals($expected, $actual);
    }

    public function test_create_score_succeeds_despite_identical_everything()
    {
        $name = 'nameFoo ' . $this->faker->words(rand(1,3), true);
        $artist = 'artistFoo ' . $this->faker->words(rand(1,3), true);

        // ------------ one ------------

        $response = $this->call('PUT', '/soundslice/create', [
            'name' => $name,
            'artist' => $artist,
            'folder-id' => $this->folderId
        ]);

        $contentOne = (array) json_decode($response->getContent());

        $this->assertNotEmpty($contentOne['slug']);

        if(empty($contentOne['slug'])){
            $this->fail('slugOne empty');
        }
        $slugOne = $contentOne['slug'];

        if(isset($contentOne['slug'])){
            $this->log_score_slug($contentOne['slug']);
            $this->dummyScoresToDeleteOnTearDown[] = $contentOne['slug'];
        }

        // ------------ two ------------

        $response = $this->call('PUT', '/soundslice/create', [
            'name' => $name,
            'artist' => $artist,
            'folder-id' => $this->folderId
        ]);

        $contentTwo = (array) json_decode($response->getContent());

        if(empty($contentTwo['slug'])){
            $this->fail('slugTwo empty');
        }
        $slugTwo = $contentTwo['slug'];

        if(isset($contentOne['slug'])){
            $this->log_score_slug($contentOne['slug']);
            $this->dummyScoresToDeleteOnTearDown[] = $contentOne['slug'];
        }

        // -------------------------------

        $relevant = [];

        $response = $this->call('GET', 'soundslice/list');

        $content = (array) json_decode($response->getContent())->scores;

        foreach($content as &$score){
            $score = (array) $score;
            if($score['slug'] === $slugTwo || $score['slug'] === $slugOne){
                $relevant[] = $score;
            }
        }

        $this->assertEquals(2, count($relevant));

        $slugs = [];

        foreach($relevant as &$score){
            $slugs[] = $score['slug'];
            unset($score['slug']);
        }

        $this->assertNotEquals($slugs[0], $slugs[1]);
        $this->assertEquals($relevant[0], $relevant[1]);
    }



    // 2. test_list -----------------------------------------------------------------------------------------------------

    public function test_list()
    {
        $response = $this->call('PUT', '/soundslice/create', [
            'name' => 'nameFoo ' . $this->faker->words(rand(1,3), true),
            'folder-id' => $this->folderId
        ]);

        $putResultOne = ((array) json_decode($response->getContent()));

        if(empty($putResultOne['slug'])){
            $this->fail('slugOne empty');
        }

        $slugOne = $putResultOne['slug'];

        $this->log_score_slug($slugOne);
        $this->dummyScoresToDeleteOnTearDown[] = $slugOne;

        $getResponseOne = $this->call('GET', '/soundslice/get/' . $slugOne);

        if($getResponseOne->getStatusCode() !== 200){
            $this->fail('$getResponseOne->getStatusCode() !== 200');
        };

        $getResponseOneContent = (array) json_decode($getResponseOne->getContent())->score;

        $response = $this->call('PUT', '/soundslice/create', [
            'name' => 'nameFoo ' . $this->faker->words(rand(1,3), true),
            'folder-id' => $this->folderId
        ]);

        $putResultTwo = ((array) json_decode($response->getContent()));

        if(empty($putResultTwo['slug'])){
            $this->fail('slugTwo empty');
        }

        $slugTwo = $putResultTwo['slug'];

        $this->log_score_slug($slugTwo);
        $this->dummyScoresToDeleteOnTearDown[] = $slugTwo;

        $getResponseTwo = $this->call('GET', '/soundslice/get/' . $slugTwo);

        if($getResponseOne->getStatusCode() !== 200){
            $this->fail('$getResponseOne->getStatusCode() !== 200');
        };

        $getResponseTwoContent = (array) json_decode($getResponseTwo->getContent())->score;

        $forComparison = [
            'expected' => [
                $slugOne => $getResponseOneContent,
                $slugTwo => $getResponseTwoContent
            ]
        ];

        $response = $this->call('GET', 'soundslice/list');

        $content = json_decode($response->getContent())->scores;

        foreach($content as &$score){
            $score = (array) $score;
            if($score['slug'] === $slugOne){
                $forComparison['actual'][$slugOne] = $score;
            }
            if($score['slug'] === $slugTwo){
                $forComparison['actual'][$slugTwo] = $score;
            }
        }

        foreach($forComparison['actual'] as &$score){
            unset($score['slug']);
        }

        foreach($forComparison['expected'] as &$score){
            unset($score['url']);
        }

        $this->assertEquals('200', $response->getStatusCode());
        $this->assertEquals($forComparison['expected'], $forComparison['actual']);
    }



    // 3. test_get_score -----------------------------------------------------------------------------------------------

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

        $response = $this->call('GET', '/soundslice/get/' . $altSlug);

        $decodedContents = (array) json_decode($response->getContent());

        $this->assertTrue(array_key_exists('errors', $decodedContents));
        $this->assertFalse(array_key_exists('score', $decodedContents));
    }



    // 4. test_delete_score --------------------------------------------------------------------------------------------

    public function test_delete_score()
    {
        $score = $this->createDummyScore();
        $slug = $score['slug'];
        $response = $this->call('delete', '/soundslice/delete/' . $slug);
        $decodedContentAsArray = (array) json_decode($response->getContent());
        $this->assertTrue($decodedContentAsArray['deleted']);

        $response = $this->call('GET', 'soundslice/list');
        $content = json_decode($response->getContent())->scores;
        $scoreFoundInList = false;
        foreach($content as &$score){
            $score = (array) $score;
            if($score['slug'] === $slug){
                $scoreFoundInList = true;
            }
        }
        $this->assertFalse($scoreFoundInList);
    }

    public function test_delete_score_not_found()
    {
        $slug = $this->dummySlugUniqueNotInProd();
        $response = $this->call('delete', '/soundslice/delete/' . $slug);

        $this->assertTrue(array_key_exists('errors', ((array) json_decode($response->getContent()))));
        $this->assertEquals(500, $response->getStatusCode());

        $this->assertEquals(json_encode(['errors' => [[
            'status' => 'error',
            'code' => 500,
            'title' => 'score delete error',
            'detail' => 'Param("slug): "' . $slug . '", error message: '.
                '"Client error: `DELETE https://www.soundslice.com/api/v1/scores/' . $slug .
                '/` resulted in a `403 Forbidden` response:' . PHP_EOL . '{}' . PHP_EOL . '".'
        ]]]), $response->getContent());
    }

    public function test_delete_score_slug_omitted()
    {
        $response = $this->call('delete', '/soundslice/delete/');

        $this->assertEquals(404, $response->getStatusCode());
    }



    // 5. test_create_folder and test_delete_folder --------------------------------------------------------------------

    public function test_create_folder()
    {
        $parentId = self::TEST_FOLDER_ID;

        $options = [
            'name' => $this->getNameForADummy('folder'),
            'parent_id' => $parentId
        ];
        $responseFolderCreate = $this->call('PUT', 'soundslice/folder/create', $options);

        $responseFolderCreateDecoded = (array) json_decode($responseFolderCreate->getContent());

        $folderId = $responseFolderCreateDecoded['folder-id'];

        $this->assertTrue(is_numeric($folderId));

        $this->log_folder_id($folderId);

        $responseToCreate = $this->call('PUT', '/soundslice/create', ['folder-id' => $folderId]);

        $responseToCreateDecoded = (array) json_decode($responseToCreate->getContent());
        $this->log_score_slug($responseToCreateDecoded['slug']);
        $this->dummyScoresToDeleteOnTearDown[] = $responseToCreateDecoded['slug'];

        $this->assertEquals(201, $responseToCreate->getStatusCode());
    }

    public function test_create_nested_folder()
    {
        $grandParentId = self::TEST_FOLDER_ID;
        $responseFolderCreate = '';

        $options = [
            'name' => $this->getNameForADummy('folder'),
            'parent_id' => $grandParentId
        ];
        $responseParentFolderCreate = $this->call('PUT', 'soundslice/folder/create', $options);

        $responseParentFolderCreateDecoded = (array) json_decode($responseParentFolderCreate->getContent());

        $parentFolderId = $responseParentFolderCreateDecoded['folder-id'];

        $this->assertTrue(is_numeric($parentFolderId));

        $this->log_folder_id($parentFolderId);

        $options = [
            'name' => $this->getNameForADummy('folder'),
            'parent_id' => $parentFolderId
        ];
        $responseFolderCreate = $this->call('PUT', 'soundslice/folder/create', $options);

        $responseFolderCreateDecoded = (array) json_decode($responseFolderCreate->getContent());

        $folderId = $responseFolderCreateDecoded['folder-id'];

        $this->assertTrue(is_numeric($folderId));

        $this->log_folder_id($folderId);

        $responseToCreate = $this->call('PUT', '/soundslice/create', ['folder-id' => $folderId]);

        $responseToCreateDecoded = (array) json_decode($responseToCreate->getContent());
        $this->log_score_slug($responseToCreateDecoded['slug']);
        $this->dummyScoresToDeleteOnTearDown[] = $responseToCreateDecoded['slug'];

        $this->assertEquals(201, $responseToCreate->getStatusCode());
    }

    public function test_create_folder_validation_failure()
    {
        $parentId = self::TEST_FOLDER_ID;

        $options = ['name' => '', 'parent_id' => $parentId];
        $response = $this->call('PUT', 'soundslice/folder/create', $options);

        $expected = json_encode(['errors' => [[
            'status' => 'Bad Request',
            'code' => 400,
            'title' => 'create folder' . ' request validation failure',
            'detail' => ['name' => [0 => 'The name field is required.']]
        ] ]]);

        $this->assertEquals($expected, $response->getContent());
        $this->assertEquals(400, $response->getStatusCode());
    }

    public function test_create_folder_failure_invalid_parent_id()
    {
        $parentId = rand(9999999000, 9999999999);

        $name = $this->getNameForADummy('folder');

        $options = ['name' => $name, 'parent_id' => $parentId];
        $response = $this->call('PUT', 'soundslice/folder/create', $options);

        $expectedSubMsg = 'Client error: `POST https://www.soundslice.com/api/v1/folders/` resulted in a `422 ' .
            'Unknown Status Code` response:' . PHP_EOL .
            '{"error": "The parent_id was invalid or not owned by your account."}' . PHP_EOL;
        $expected = json_encode(['errors' => [[
                'status' => 'error',
                'code' => 500,
                'title' => 'create folder failed',
                'detail' => 'Param("name"): "' . $name . '", error message: "' . $expectedSubMsg . '".'
        ]]]);

        $this->assertEquals($expected, $response->getContent());
        $this->assertEquals(500, $response->getStatusCode());
    }



    public function test_delete_folder()
    {
        $this->createDummyFolder();

        $response = $this->call('delete', 'soundslice/folder/delete/' . $this->folderId);

        $deleted = ((array) json_decode($response->getContent()))['deleted'];

        $this->assertTrue($deleted);
        $this->assertEquals(200, $response->getStatusCode());
    }

    public function test_delete_folder_not_found()
    {
        $folderId = rand(999999999000, 999999999999);

        $response = $this->call('delete', 'soundslice/folder/delete/' . $folderId);

        $expected = json_encode(['errors' => [
            [
                'status' => 'error',
                'code' => 500,
                'title' => 'delete folder failed',
                //'detail' => 'Param ("id"): "' . $folderId . '", error message: "' . $expectedSubMsg . '".'
                'detail' => 'Param ("id"): "' . $folderId . '", error message: "Client error: ' .
                    '`DELETE https://www.soundslice.com/api/v1/folders/' . $folderId . '/` resulted in a `404 Not ' .
                    'Found` response:' . PHP_EOL . '<!DOCTYPE html>' . PHP_EOL .
                    '<!--[if lt IE 8]>   <html class="lte8"> <![endif]-->' . PHP_EOL .
                    '<!--[if IE 8]>     <html class="ie8 lte8"> <![endif (truncated...)' . PHP_EOL . '".'
            ]
        ]]);

        $this->assertEquals($expected, $response->getContent());
        $this->assertEquals(500, $response->getStatusCode());
    }

    public function test_delete_folder_id_omitted()
    {
        $response = $this->call('delete', 'soundslice/folder/delete/');

        $this->assertEquals(404, $response->getStatusCode());
    }



    // 6. test_create_notation -----------------------------------------------------------------------------------------

    public function test_create_notation()
    {
        $assetUrl = 'https://s3.us-east-2.amazonaws.com/soundslice/DTME+-+Week+5+-+soundslice-ex3.musicxml';

        $score = $this->createDummyScore();

        $response = $this->call('put', 'soundslice/notation/', [
            'slug' => $score['slug'],
            'asset-url' => $assetUrl
        ]);

        $notation = ((array) json_decode($response->getContent()))['notation'];

        $this->assertEquals(201, $response->getStatusCode());
        $this->assertTrue($notation);
    }

    public function test_create_notation_validation_failure_missing_slug()
    {
        $assetUrl = 'https://s3.us-east-2.amazonaws.com/soundslice/DTME+-+Week+5+-+soundslice-ex3.musicxml';

        $response = $this->call('put', 'soundslice/notation/', ['asset-url' => $assetUrl]);

        $expected = json_encode(['errors' => [[
            'status' => 'Bad Request',
            'code' => 400,
            'title' => 'create notation' . ' request validation failure',
            'detail' => ['slug' => [0 => 'The slug field is required.']]
        ] ]]);

        $this->assertEquals($expected, $response->getContent());
        $this->assertEquals(400, $response->getStatusCode());
    }

    public function test_create_notation_validation_failure_missing_asset_url()
    {
        $score = $this->createDummyScore();

        $response = $this->call('put', 'soundslice/notation/', [
            'slug' => $score['slug'],
            'asset-url' => ''
        ]);

        $expected = json_encode(['errors' => [[
            'status' => 'Bad Request',
            'code' => 400,
            'title' => 'create notation' . ' request validation failure',
            'detail' => ['asset-url' => [0 => 'The asset-url field is required.']]
        ] ]]);

        $this->assertEquals($expected, $response->getContent());
        $this->assertEquals(400, $response->getStatusCode());
    }

    public function test_create_notation_validation_failure_asset_not_url()
    {
        $score = $this->createDummyScore();

        $response = $this->call('put', 'soundslice/notation/', [
            'slug' => $score['slug'],
            'asset-url' => $this->faker->words(3, true)
        ]);

        $expected = json_encode(['errors' => [[
            'status' => 'Bad Request',
            'code' => 400,
            'title' => 'create notation' . ' request validation failure',
            'detail' => ['asset-url' => [0 => 'The asset-url format is invalid.']]
        ] ]]);

        $this->assertEquals($expected, $response->getContent());
        $this->assertEquals(400, $response->getStatusCode());
    }



    // 7. test_edit_score ----------------------------------------------------------------------------------------------

    public function test_move_score()
    {
        $score = $this->createDummyScore();
        $firstFolderCreated = $this->folderId;
        $destination = $this->createDummyFolder(self::TEST_FOLDER_ID, true);
        $this->assertNotEquals($firstFolderCreated, $destination);

        $response = $this->call('POST', 'soundslice/move/', [
            'slug' => $score['slug'],
            'folder-id' => $destination
        ]);

        $contentDecoded = json_decode($response->getContent());
        $actualFolderId = ((array) $contentDecoded)['folder-id'];
        $this->assertEquals(201, $response->getStatusCode());
        $this->assertEquals($destination, $actualFolderId);
    }

    public function test_move_score_validation_failure_missing_slug()
    {
        $firstFolderCreated = $this->folderId;
        $destination = $this->createDummyFolder(self::TEST_FOLDER_ID, true);
        $this->assertNotEquals($firstFolderCreated, $destination);

        $response = $this->call('POST', 'soundslice/move/', [
            'folder-id' => $destination
        ]);

        $expected = json_encode(['errors' => [[
            'status' => 'Bad Request',
            'code' => 400,
            'title' => 'move score' . ' request validation failure',
            'detail' => ['slug' => [0 => 'The slug field is required.']]
        ] ]]);;

        $this->assertEquals(400, $response->getStatusCode());
        $this->assertEquals($expected, $response->getContent());
    }

    public function test_move_score_validation_failure_missing_destination()
    {
        $score = $this->createDummyScore();
        $firstFolderCreated = $this->folderId;
        $destination = $this->createDummyFolder(self::TEST_FOLDER_ID, true);
        $this->assertNotEquals($firstFolderCreated, $destination);

        $response = $this->call('POST', 'soundslice/move/', [
            'slug' => $score['slug']
        ]);

        $expected = json_encode(['errors' => [[
            'status' => 'Bad Request',
            'code' => 400,
            'title' => 'move score' . ' request validation failure',
            'detail' => ['folder-id' => [0 => 'The folder-id field is required.']]
        ] ]]);;

        $this->assertEquals(400, $response->getStatusCode());
        $this->assertEquals($expected, $response->getContent());
    }

    public function test_move_score_validation_failure_destination_not_ours()
    {
        $score = $this->createDummyScore();
        $firstFolderCreated = $this->folderId;
        $destination = $this->createDummyFolder(self::TEST_FOLDER_ID, true);
        $this->assertNotEquals($firstFolderCreated, $destination);

        $response = $this->call('POST', 'soundslice/move/', [
            'slug' => $score['slug'],
            'folder-id' => rand(999999000, 999999999)
        ]);

        $expected = json_encode(['errors' => [[
            'status' => 'error',
            'code' => 500,
            'title' => 'score move error',
            'detail' => 'Client error: `POST https://www.soundslice.com/api/v1/scores/' . $score['slug'] .
                '/move/` resulted in a `422 Unknown Status Code` response:' . PHP_EOL .
                '{"error": "Invalid folder ID."}' . PHP_EOL
        ]]]);

        $this->assertEquals(500, $response->getStatusCode());
        $this->assertEquals($expected, $response->getContent());
    }



    // -----------------------------------------------------------------------------------------------------------------
    // -----------------------------------------------------------------------------------------------------------------
    // -----------------------------------------------------------------------------------------------------------------


    // 99. (helper function) runs as test case and deletes scores created in testing ------------------------------------

    /**
     * This isn't actually a test - it's just a way of deleting scores created in testing. Otherwise we have to
     * go into the soundslice "slice manager" web UI and manually delete each one. Fuck that. Just look in
     * "to-delete-scores.txt" in the root of this project to see a list of slugs representing the scores created
     * in the process of running these tests. Merry xmas. Jonathan, 2018
     */
    public function test_delete_dummy_content_on_soundslice()
    {
        $this->delete_dummy_scores_on_soundslice();
        echo PHP_EOL;
        $this->delete_dummy_folders_on_soundslice();
    }
}