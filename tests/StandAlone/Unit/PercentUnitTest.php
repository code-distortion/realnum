<?php

namespace CodeDistortion\RealNum\Tests\StandAlone\Unit;

use CodeDistortion\RealNum\Tests\StandAlone\TestCase;
use CodeDistortion\RealNum\Percent;
use CodeDistortion\RealNum\RealNum;

/**
 * Test the Percent library class
 *
 * @phpcs:disable PSR1.Methods.CamelCapsMethodName.NotCamelCaps
 */
class PercentUnitTest extends TestCase
{
    /**
     * Some set-up, run before each test
     *
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();

        // additional setup
        Percent::resetDefaults();
    }

    /**
     * Provides the different render options for testing in the test_percentage_rendering test below
     *
     * @return array
     */
    public function renderingDataProvider(): array
    {
        $output = [];
        $output['en-AU'] = [
            '12.345%',
            '12.345%',
            '12.35%',
            '12.3%',
            '12%',
            '100%',
            '10,000%',
            '100%',
            '100.000%',
            '10000%',
            '+100%',
            '100%',
            '(100%)',
            '+10012.34500000000000000000%',
            'null',
            null,
            '12.345%',
        ];
        $output['de'] = [
            '12,345 %',
            '12,345 %',
            '12,35 %',
            '12,3 %',
            '12 %',
            '100 %',
            '10.000 %',
            '100 %',
            '100,000 %',
            '10000 %',
            '+100 %',
            '100 %',
            '(100 %)',
            '+10012,34500000000000000000 %',
            'null',
            null,
            '12,345 %', // non-breaking spaces
        ];



        $return = [];
        foreach ($output as $locale => $outputValues) {
            $return[] = [$locale, 0.12345, 20, 0, $outputValues[0]];
            $return[] = [$locale, 0.12345, 3, 0, $outputValues[1]];
            $return[] = [$locale, 0.12345, 2, 0, $outputValues[2]];
            $return[] = [$locale, 0.12345, 1, 0, $outputValues[3]];
            $return[] = [$locale, 0.12345, 0, 0, $outputValues[4]];
            $return[] = [$locale, 1, 20, 0, $outputValues[5]];
            $return[] = [$locale, 100, 20, 0, $outputValues[6]];
            $return[] = [$locale, 1, 3, 0, $outputValues[7]];
            $return[] = [$locale, 1, 3, Percent::ALL_DEC_PL, $outputValues[8]];
            $return[] = [$locale, 100, 20, Percent::NO_THOUSANDS, $outputValues[9]];
            $return[] = [$locale, 1, 20, Percent::SHOW_PLUS, $outputValues[10]];
            $return[] = [$locale, 1, 20, Percent::ACCT_NEG, $outputValues[11]];
            $return[] = [$locale, -1, 20, Percent::ACCT_NEG, $outputValues[12]];
            $return[] = [
                $locale,
                100.1234500,
                20,
                Percent::ALL_DEC_PL | Percent::NO_THOUSANDS | Percent::SHOW_PLUS | Percent::ACCT_NEG
                    | Percent::NULL_AS_STRING,
                $outputValues[13]];
            $return[] = [$locale, null, 20, Percent::NULL_AS_STRING, $outputValues[14]];
            $return[] = [$locale, null, 20, 0, $outputValues[15]];
            $return[] = [$locale, 0.12345, 20, Percent::NO_BREAK_WHITESPACE, $outputValues[16]];
        }

        return $return;
    }





    /**
     * Test the ways the default locale, maxDecPl, immutability non-breaking-whitespace settings are altered
     *
     * @test
     * @return void
     */
    public function test_percentage_default_settings(): void
    {
        // make sure the Percent and RealNum settings are distinct
        Percent::resetDefaults();
        RealNum::resetDefaults();
        $this->assertSame('en', Percent::getDefaultLocale());
        $this->assertSame('en', RealNum::getDefaultLocale());
        $this->assertSame(20, Percent::getDefaultMaxDecPl());
        $this->assertSame(20, RealNum::getDefaultMaxDecPl());
        $this->assertTrue(Percent::getDefaultImmutability());
        $this->assertTrue(RealNum::getDefaultImmutability());
        $this->assertFalse(Percent::getDefaultNoBreakWhitespace());
        $this->assertFalse(RealNum::getDefaultNoBreakWhitespace());

        Percent::setDefaultLocale('en-AU');
        RealNum::setDefaultLocale('en-UK');
        $this->assertSame('en-AU', Percent::getDefaultLocale());
        $this->assertSame('en-UK', RealNum::getDefaultLocale());

        Percent::setDefaultMaxDecPl(5);
        RealNum::setDefaultMaxDecPl(10);
        $this->assertSame(5, Percent::getDefaultMaxDecPl());
        $this->assertSame(10, RealNum::getDefaultMaxDecPl());

        Percent::setDefaultImmutability(false);
        RealNum::setDefaultImmutability(true);
        $this->assertFalse(Percent::getDefaultImmutability());
        $this->assertTrue(RealNum::getDefaultImmutability());

        Percent::setDefaultNoBreakWhitespace(true);
        RealNum::setDefaultNoBreakWhitespace(false);
        $this->assertTrue(Percent::getDefaultNoBreakWhitespace());
        $this->assertFalse(RealNum::getDefaultNoBreakWhitespace());
    }

    /**
     * Test arithmetic and rounding operations
     *
     * @test
     * @return void
     */
    public function test_arithmetic_and_rounding(): void
    {
        // arithmetic
        $this->assertSame('223.4567%', Percent::new(1.234567)->add(1)->format());
        $this->assertSame('23.4567%', Percent::new(1.234567)->sub(1)->format());
        $this->assertSame('246.9134%', Percent::new(1.234567)->mul(2)->format());
        $this->assertSame('61.72835%', Percent::new(1.234567)->div(2)->format());
        $num1 = Percent::new(1.234567);
        $this->assertSame('246.9134%', Percent::new(1.234567)->add($num1)->format());

        // make sure the Percent and RealNum settings are distinct
        $this->assertSame('123%', Percent::new(1.234567)->round(0)->format());
        $this->assertSame('123.5%', Percent::new(1.234567)->round(1)->format());
        $this->assertSame('123.46%', Percent::new(1.234567)->round(2)->format());

        // floor and ceil to 1 percent
        $this->assertSame('123%', Percent::new(1.234567)->floor()->format());
        $this->assertSame('124%', Percent::new(1.234567)->ceil()->format());

        $this->assertTrue(Percent::new(1.234567)->between(1, 2));
    }

    /**
     * Test the different ways to the Percentage value can be rendered
     *
     * @test
     * @dataProvider renderingDataProvider
     * @param string      $locale        The locale to use.
     * @param float|null  $initialValue  The value to render.
     * @param integer     $maxDecPl      The options to use while rendering.
     * @param integer     $renderOptions The number of decimal places to round to.
     * @param string|null $expectedValue The expected render output.
     * @return void
     */
    public function test_percentage_rendering(
        string $locale,
        ?float $initialValue,
        int $maxDecPl,
        int $renderOptions,
        ?string $expectedValue
    ): void {

        $this->assertSame(
            $expectedValue,
            Percent::new($initialValue)->locale($locale)->maxDecPl($maxDecPl)->format($renderOptions)
        );
    }
}
