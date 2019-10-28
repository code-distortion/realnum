<?php

namespace CodeDistortion\RealNum\Tests\Laravel\Unit;

use App;
use CodeDistortion\RealNum\RealNum;
use CodeDistortion\RealNum\Percent;
use CodeDistortion\RealNum\Tests\Laravel\TestCase;

/**
 * Test the RealNum's integration into Laravel
 *
 * @phpcs:disable PSR1.Methods.CamelCapsMethodName.NotCamelCaps
 */
class LaravelUnitTest extends TestCase
{
    /**
     * Test that the service-provider is registered in Laravel and acts correctly
     *
     * @todo get Laravel (orchestra-testbench) to pick up the service provider, and then perform the below test to
     *       check that the change-locale event is picked up.
     * @test
     * @return void
     */
    public function test_service_provider(): void
    {
        $this->assertTrue(true);
        return;

        $this->assertSame('en', RealNum::getDefaultLocale()); // default locale
        $this->assertSame('en', Percent::getDefaultLocale()); // default locale
        App::setLocale('en-AU');
        $this->assertSame('en-AU', RealNum::getDefaultLocale());
        $this->assertSame('en-AU', Percent::getDefaultLocale());
    }
}
