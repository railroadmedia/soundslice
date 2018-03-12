<?php

namespace Railroad\Soundslice\Tests\Acceptance;

use Orchestra\Testbench\TestCase;
use Railroad\Soundslice\Services\SoundsliceService;

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

        $this->app['config']->set('soundslice.awsS3',[
            'accessKey' => env('AWS_S3_SOUNDSLICE_ACCESS_KEY'),
            'accessSecret' => env('AWS_S3_SOUNDSLICE_ACCESS_SECRET'),
            'region' => env('AWS_S3_SOUNDSLICE_REGION'),
            'bucket' => env('AWS_S3_SOUNDSLICE_BUCKET')
        ]);
        $this->app['config']->set('soundslice.awsCloudFront', env('AWS_CLOUDFRONT_SOUNDSLICE'));
        $this->app['config']->set('soundslice.soundsliceAppId', env('SOUNDSLICE_APP_ID'));
        $this->app['config']->set('soundslice.soundsliceSecret', env('SOUNDSLICE_SECRET'));
        $this->app['config']->set('soundslice.notationKeySignifier', env('NOTATION_KEY_SIGNIFIER'));

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

//    public function test_confirm_testing_config_set_correctly()
//    {
//        var_dump($this->app['config']['soundslice']['awsS3']);
//        var_dump([env('SOUNDSLICE_APP_ID'), env('SOUNDSLICE_SECRET')]); die();
//    }

    // -----------------------------------------------------------------------------------------------------------------



}