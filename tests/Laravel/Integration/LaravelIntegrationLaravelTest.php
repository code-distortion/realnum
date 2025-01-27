<?php

namespace CodeDistortion\RealNum\Tests\Laravel\Integration;

use App;
use CodeDistortion\RealNum\Percent;
use CodeDistortion\RealNum\RealNum;
use CodeDistortion\RealNum\Tests\LaravelTestCase;
use PHPUnit\Framework\Attributes\Test;

/**
 * Test the RealNum's integration into Laravel.
 *
 * @phpcs:disable PSR1.Methods.CamelCapsMethodName.NotCamelCaps
 */
class LaravelIntegrationLaravelTest extends LaravelTestCase
{
    /**
     * Test that the service-provider is registered in Laravel and acts correctly.
     *
     * @test
     *
     * @return void
     */
    #[Test]
    public function test_service_provider(): void
    {
        self::assertSame('en', RealNum::getDefaultLocale()); // default locale
        self::assertSame('en', Percent::getDefaultLocale()); // default locale
        App::setLocale('en-AU');
        self::assertSame('en-AU', RealNum::getDefaultLocale());
        self::assertSame('en-AU', Percent::getDefaultLocale());
    }
}
