<?php

namespace Railroad\Soundslice\Tests;

use Faker\Generator;
use Orchestra\Testbench\TestCase as BaseTestCase;
use Railroad\Soundslice\Providers\SoundsliceServiceProvider;
use Railroad\Soundslice\Services\SoundsliceService;

class SoundsliceTestCase extends BaseTestCase
{

    /**
     * @var Generator
     */
    protected $faker;

    protected $soundsliceService;

    protected function setUp()
    {
        parent::setUp();

        $this->artisan('cache:clear', []);
        $this->faker = $this->app->make(Generator::class);
        $this->soundsliceService = $this->app->make(SoundsliceService::class);
    }

    /**
     * Define environment setup.
     *
     * @param  \Illuminate\Foundation\Application $app
     * @return void
     */
    protected function getEnvironmentSetUp($app)
    {
        // setup package config for testing
//        $defaultConfig = require(__DIR__ . '/../config/soundslice.php');
//        $app['config']->set('foo.bar', $defaultConfig['foo.bar']);
//        $app['config']->set('foo.baz', $defaultConfig['foo.baz']);


        // register provider
        $app->register(SoundsliceServiceProvider::class);
    }

//    protected function tearDown()
//    {
//        parent::tearDown();
//    }
}