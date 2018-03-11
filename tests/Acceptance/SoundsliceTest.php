<?php

namespace Railroad\Soundslice\Tests\Acceptance;


use Orchestra\Testbench\TestCase;

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

        include __DIR__ . '../../../.env.testing';
        if (empty(env('AWS_S3_SOUNDSLICE_ACCESS_KEY'))) { $this->fail("You must provide a value for the " .
            "AWS_S3_SOUNDSLICE_ACCESS_KEY \'putenv' (environmental variable setting) function in `/.env.testing`.");}
        if (empty(env('AWS_S3_SOUNDSLICE_ACCESS_SECRET'))) { $this->fail("You must provide a value for the " .
            "AWS_S3_SOUNDSLICE_ACCESS_SECRET \'putenv' (environmental variable setting) function in `/.env.testing`.");}
        if (empty(env('AWS_S3_SOUNDSLICE_REGION'))) { $this->fail("You must provide a value for the " .
            "AWS_S3_SOUNDSLICE_REGION \'putenv' (environmental variable setting) function in `/.env.testing`.");}
        if (empty(env('AWS_S3_SOUNDSLICE_BUCKET'))) { $this->fail("You must provide a value for the " .
            "AWS_S3_SOUNDSLICE_BUCKET \'putenv' (environmental variable setting) function in `/.env.testing`.");}

        $this->app['config']->set(
            'railcontent.awsS3_soundslice',
            [
                'accessKey' => env('AWS_S3_SOUNDSLICE_ACCESS_KEY'),
                'accessSecret' => env('AWS_S3_SOUNDSLICE_ACCESS_SECRET'),
                'region' => env('AWS_S3_SOUNDSLICE_REGION'),
                'bucket' => env('AWS_S3_SOUNDSLICE_BUCKET')
            ]
        );

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


}