<?php

namespace CodeDistortion\RealNum\Tests\Laravel;

use CodeDistortion\RealNum\Laravel\ServiceProvider;
//use Jchook\AssertThrows\AssertThrows;
use Orchestra\Testbench\TestCase as BaseTestCase;

/**
 * The test case that unit tests extend from.
 */
class TestCase extends BaseTestCase
{
//    use AssertThrows;

    /**
     * Get package providers.
     *
     * @param \Illuminate\Foundation\Application $app The Laravel app.
     * @return array
     */
    protected function getPackageProviders($app)
    {
        return [
            ServiceProvider::class
        ];
    }
}
