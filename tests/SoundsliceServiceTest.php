<?php

use Railroad\Soundslice\Services\SoundsliceService;
use Railroad\Soundslice\Tests\SoundsliceTestCase;

class SoundsliceServiceTest extends SoundsliceTestCase
{
    /**
     * @var SoundsliceService
     */
    private $classBeingTested;

    protected function setUp()
    {
        parent::setUp();
        $this->classBeingTested = $this->app->make(SoundsliceService::class);
    }
}
