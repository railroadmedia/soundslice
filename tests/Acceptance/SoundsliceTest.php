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

    /** @var $soundSliceService SoundsliceService */
    protected $soundSliceService;

    protected function setUp()
    {
        parent::setUp();


        $this->soundSliceService = $this->app->make(SoundsliceService::class);
    }


    protected function tearDown()
    {
        if(isset($this->folderId)){
            $this->deleteDummyFolder();
        }

        parent::tearDown();
    }

    private function createDummyFolder(){
        $this->folderId = $this->soundSliceService->createFolder($this->getNameForADummy('folder'));
    }

    private function deleteDummyFolder(){
        $success = $this->soundSliceService->deleteFolder($this->folderId);
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

    public function test_list()
    {
        $response = $this->call('GET', 'soundslice/list');

        $this->assertNotEquals('404', $response->getStatusCode());
    }

}