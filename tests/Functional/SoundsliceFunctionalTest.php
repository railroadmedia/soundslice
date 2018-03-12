<?php

namespace Railroad\Soundslice\Tests\Acceptance;

use Orchestra\Testbench\TestCase;
use Railroad\Soundslice\Services\SoundsliceService;

class SoundsliceFunctionalTest extends TestCase
{
    /** @var SoundsliceService */
    private $soundSliceService;

    public function setup()
    {
        parent::setUp();

        $this->soundSliceService = new SoundsliceService();
    }

    public function test_list()
    {
        $response = $this->soundSliceService->list();

        $foo = 'bar';
    }

}