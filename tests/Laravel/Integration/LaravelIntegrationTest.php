<?php

namespace CodeDistortion\RealNum\Tests\Laravel\Integration;

use App;
use CodeDistortion\RealNum\RealNum;
use CodeDistortion\RealNum\Percent;
use CodeDistortion\RealNum\Tests\Laravel\TestCase;

/**
 * Test the RealNum's integration into Laravel.
 *
 * @group laravel
 * @phpcs:disable PSR1.Methods.CamelCapsMethodName.NotCamelCaps
 */
class LaravelIntegrationTest extends TestCase
{
    /**
     * Test that the service-provider is registered in Laravel and acts correctly.
     *
     * @test
     * @return void
     */
    public function test_service_provider(): void
    {
        self::assertSame('en', RealNum::getDefaultLocale()); // default locale
        self::assertSame('en', Percent::getDefaultLocale()); // default locale
        App::setLocale('en-AU');
        self::assertSame('en-AU', RealNum::getDefaultLocale());
        self::assertSame('en-AU', Percent::getDefaultLocale());
    }
}
