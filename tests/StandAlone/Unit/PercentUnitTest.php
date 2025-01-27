<?php

namespace CodeDistortion\RealNum\Tests\StandAlone\Unit;

use CodeDistortion\RealNum\Tests\PHPUnitTestCase;
use CodeDistortion\RealNum\Percent;
use CodeDistortion\RealNum\RealNum;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;

/**
 * Test the Percent library class.
 *
 * @phpcs:disable PSR1.Methods.CamelCapsMethodName.NotCamelCaps
 */
class PercentUnitTest extends PHPUnitTestCase
{
    /**
     * Some set-up, run before each test.
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
     * Provides the different render options for testing in the test_percentage_rendering test below.
     *
     * @return array
     */
    public static function renderingDataProvider(): array
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
            '12.34568%',
            '+10012.34500000000000000000%',
            'null',
            null,
            '12.345%',
        ];
        $output['de'] = [
            '12,345 %',
            '12,345 %',
            '12,35 %',
            '12,3 %',
            '12 %',
            '100 %',
            '10.000 %',
            '100 %',
            '100,000 %',
            '10000 %',
            '+100 %',
            '100 %',
            '(100 %)',
            '12,34568 %',
            '+10012,34500000000000000000 %',
            'null',
            null,
            '12,345 %', // breaking spaces
        ];



        $return = [];
        foreach ($output as $locale => $outputValues) {
            $return[] = [$locale, 0.12345, 20, null, $outputValues[0]];
            $return[] = [$locale, 0.12345, 3, null, $outputValues[1]];
            $return[] = [$locale, 0.12345, 2, null, $outputValues[2]];
            $return[] = [$locale, 0.12345, 1, null, $outputValues[3]];
            $return[] = [$locale, 0.12345, 0, null, $outputValues[4]];
            $return[] = [$locale, 1, 20, null, $outputValues[5]];
            $return[] = [$locale, 100, 20, null, $outputValues[6]];
            $return[] = [$locale, 1, 3, null, $outputValues[7]];
            $return[] = [$locale, 1, 3, 'trailZeros', $outputValues[8]];
            $return[] = [$locale, 100, 20, '-thousands', $outputValues[9]];
            $return[] = [$locale, 1, 20, 'showPlus', $outputValues[10]];
            $return[] = [$locale, 1, 20, 'accountingNeg', $outputValues[11]];
            $return[] = [$locale, -1, 20, 'accountingNeg', $outputValues[12]];
            $return[] = [$locale, 0.123456789, 20, 'decPl=5', $outputValues[13]];
            $return[] = [
                $locale,
                100.1234500,
                20,
                'trailZeros -thousands showPlus accountingNeg null="null"',
                $outputValues[14]];
            $return[] = [$locale, null, 20, 'null="null"', $outputValues[15]];
            $return[] = [$locale, null, 20, null, $outputValues[16]];
            $return[] = [$locale, 0.12345, 20, 'breaking', $outputValues[17]];
        }

        return $return;
    }





    /**
     * Test the ways the default locale, maxDecPl, immutability and default-format settings are altered.
     *
     * @test
     *
     * @return void
     */
    #[Test]
    public function test_percentage_default_settings(): void
    {
        // make sure the Percent and RealNum settings are distinct
        Percent::resetDefaults();
        RealNum::resetDefaults();
        self::assertSame('en', Percent::getDefaultLocale());
        self::assertSame('en', RealNum::getDefaultLocale());
        self::assertSame(20, Percent::getDefaultMaxDecPl());
        self::assertSame(20, RealNum::getDefaultMaxDecPl());
        self::assertTrue(Percent::getDefaultImmutability());
        self::assertTrue(RealNum::getDefaultImmutability());

        Percent::setDefaultLocale('en-AU');
        RealNum::setDefaultLocale('en-UK');
        self::assertSame('en-AU', Percent::getDefaultLocale());
        self::assertSame('en-UK', RealNum::getDefaultLocale());

        Percent::setDefaultMaxDecPl(5);
        RealNum::setDefaultMaxDecPl(10);
        self::assertSame(5, Percent::getDefaultMaxDecPl());
        self::assertSame(10, RealNum::getDefaultMaxDecPl());

        Percent::setDefaultImmutability(false);
        RealNum::setDefaultImmutability(true);
        self::assertFalse(Percent::getDefaultImmutability());
        self::assertTrue(RealNum::getDefaultImmutability());
    }

    /**
     * Test arithmetic and rounding operations.
     *
     * @test
     *
     * @return void
     */
    #[Test]
    public function test_arithmetic_and_rounding(): void
    {
        // arithmetic
        self::assertSame('223.4567%', Percent::new(1.234567)->add(1)->format());
        self::assertSame('23.4567%', Percent::new(1.234567)->sub(1)->format());
        self::assertSame('246.9134%', Percent::new(1.234567)->mul(2)->format());
        self::assertSame('61.72835%', Percent::new(1.234567)->div(2)->format());
        $num1 = Percent::new(1.234567);
        self::assertSame('246.9134%', Percent::new(1.234567)->add($num1)->format());

        // make sure the Percent and RealNum settings are distinct
        self::assertSame('123%', Percent::new(1.234567)->round(0)->format());
        self::assertSame('123.5%', Percent::new(1.234567)->round(1)->format());
        self::assertSame('123.46%', Percent::new(1.234567)->round(2)->format());

        // floor and ceil to 1 percent
        self::assertSame('123%', Percent::new(1.234567)->floor()->format());
        self::assertSame('124%', Percent::new(1.234567)->ceil()->format());

        self::assertTrue(Percent::new(1.234567)->between(1, 2));
    }

    /**
     * Test the different ways to the Percentage value can be rendered.
     *
     * @test
     * @dataProvider renderingDataProvider
     *
     * @param string            $locale        The locale to use.
     * @param float|null        $initialValue  The value to render.
     * @param integer           $maxDecPl      The options to use while rendering.
     * @param string|array|null $renderOptions The number of decimal places to round to.
     * @param string|null       $expectedValue The expected render output.
     * @return void
     */
    #[Test]
    #[DataProvider('renderingDataProvider')]
    public function test_percentage_rendering(
        string $locale,
        ?float $initialValue,
        int $maxDecPl,
        $renderOptions,
        ?string $expectedValue
    ): void {

        self::assertSame(
            $expectedValue,
            Percent::new($initialValue)->locale($locale)->maxDecPl($maxDecPl)->format($renderOptions)
        );
    }
}
