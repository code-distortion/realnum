<?php

namespace CodeDistortion\RealNum\Tests\StandAlone\Unit;

use CodeDistortion\RealNum\Exceptions\InvalidValueException;
use CodeDistortion\RealNum\Exceptions\InvalidLocaleException;
use CodeDistortion\RealNum\Exceptions\UndefinedPropertyException;
use CodeDistortion\RealNum\RealNum;
use CodeDistortion\RealNum\Tests\PHPUnitTestCase;
use DivisionByZeroError;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Constraint\Exception as ConstraintException;
use PHPUnit\Framework\Error\Warning;
use stdClass;
use Throwable;

/**
 * Test the RealNum library class.
 *
 * @phpcs:disable PSR1.Methods.CamelCapsMethodName.NotCamelCaps
 */
class RealNumUnitTest extends PHPUnitTestCase
{
    /**
     * Some alternate format settings used below for testing.
     *
     * @var array
     */
    protected static $altFormatSettings = [
        'accountingNeg' => true,
        'breaking' => true,
        'decPl' => 5,
        'locale' => 'en-US',
        'null' => 'null',
        'showPlus' => true,
        'thousands' => false,
        'trailZeros' => true,
    ];



    /**
     * Some set-up, run before each test.
     *
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();

        // additional setup
        RealNum::resetDefaults();
    }

    /**
     * Provides the different immutable situations to test for the test_realnum_immutability_setters test below.
     *
     * @return array
     */
    public static function immutableDataProviderSetters(): array
    {
        $properties = [
            ['locale', 'locale', 'en-AU', 'en-NZ'],
            ['maxDecPl', 'maxDecPl', 13, 14],
            ['immutable', 'immutable', true, false],
            ['formatSettings', 'formatSettings', self::$altFormatSettings, RealNum::ORIG_FORMAT_SETTINGS],
            ['val', 'val', '1.00000000000000000000', '2.00000000000000000000'],
            ['cast', 'cast', 1, 2],
        ];

        $return = [];
        foreach ([true, false] as $immutable) {

            foreach ($properties as $values) {

                $setMethod = $values[0];
                $getField = $values[1];
                $startValue = $values[2];
                $endValue = $values[3];

                // swap the values when getting / setting the "immutable" value (and immutability is off)
                // (because it actually changes the immutable setting itself)
                if (($getField == 'immutable') && (!$immutable)) {
                    $startValue = $values[3];
                    $endValue = $values[2];
                }

                $return[] = [
                    $immutable,
                    $setMethod,
                    $getField,
                    $startValue,
                    $endValue,
                ];
            }
        }
        return $return;
    }

    /**
     * Provides the different immutable situations to test for the test_realnum_immutability_alter_methods test below.
     *
     * @return array
     */
    public static function immutableDataProviderAlterationMethods(): array
    {
        $alterationMethods = [
            'round' => [[],  1.1, 1],
            'floor' => [[],  1.1, 1],
            'ceil'  => [[],  1.1, 2],
            'add'   => [[1], 1.1, 2.1],
            'div'   => [[5], 10,  2],
            'mul'   => [[5], 10,  50],
            'sub'   => [[5], 10,  5],
            'inc'   => [[1], 10,  11],
            'dec'   => [[1], 10,  9],
        ];

        $return = [];
        foreach ([true, false] as $immutable) {

            foreach ($alterationMethods as $method => $values) {
                $return[] = [
                    $immutable,
                    $method,
                    $values[0], // params
                    $values[1], // startValue
                    $values[2], // endValue
                ];
            }
        }
        return $return;
    }

    /**
     * Provides the different render options for testing in the test_realnum_locale_rendering test below.
     *
     * @return array
     */
    public static function localeRenderingDataProvider(): array
    {
        $output = [];
        $output['en-AU'] = [
            '12,345,678.9',
            '-12,345,678.9',
            '12,345,678.9',
            '(12,345,678.9)',
            '+12,345,678.9',
            '12,345,678',
            '12,345,678.900',
            '12,345,678.000',
            '12345678',
            '0',
            '12345678.000',
            '(12345678.00000000000000000000)',
            'null',
            null,
            '12,345,678.9',
            '12,345,678.9',
        ];
        $output['fr'] = [
            '12 345 678,9',
            '-12 345 678,9',
            '12 345 678,9',
            '(12 345 678,9)',
            '+12 345 678,9',
            '12 345 678',
            '12 345 678,900',
            '12 345 678,000',
            '12345678',
            '0',
            '12345678,000',
            '(12345678,00000000000000000000)',
            'null',
            null,
            '12 345 678,9', // breaking spaces
            '12,345,678.9',
        ];
        $output['de'] = [
            '12.345.678,9',
            '-12.345.678,9',
            '12.345.678,9',
            '(12.345.678,9)',
            '+12.345.678,9',
            '12.345.678',
            '12.345.678,900',
            '12.345.678,000',
            '12345678',
            '0',
            '12345678,000',
            '(12345678,00000000000000000000)',
            'null',
            null,
            '12.345.678,9',
            '12,345,678.9',
        ];
        $output['ja-JP'] = [
            '12,345,678.9',
            '-12,345,678.9',
            '12,345,678.9',
            '(12,345,678.9)',
            '+12,345,678.9',
            '12,345,678',
            '12,345,678.900',
            '12,345,678.000',
            '12345678',
            '0',
            '12345678.000',
            '(12345678.00000000000000000000)',
            'null',
            null,
            '12,345,678.9',
            '12,345,678.9',
        ];
        $output['en-IN'] = [
            '1,23,45,678.9',
            '-1,23,45,678.9',
            '1,23,45,678.9',
            '(1,23,45,678.9)',
            '+1,23,45,678.9',
            '1,23,45,678',
            '1,23,45,678.900',
            '1,23,45,678.000',
            '12345678',
            '0',
            '12345678.000',
            '(12345678.00000000000000000000)',
            'null',
            null,
            '1,23,45,678.9',
            '12,345,678.9',
        ];
        $output['ar-EG'] = [
            '١٢٬٣٤٥٬٦٧٨٫٩',
            '؜-١٢٬٣٤٥٬٦٧٨٫٩',
            '١٢٬٣٤٥٬٦٧٨٫٩',
            '(١٢٬٣٤٥٬٦٧٨٫٩)',
            '؜+١٢٬٣٤٥٬٦٧٨٫٩',
            '١٢٬٣٤٥٬٦٧٨',
            '١٢٬٣٤٥٬٦٧٨٫٩٠٠',
            '١٢٬٣٤٥٬٦٧٨٫٠٠٠',
            '١٢٣٤٥٦٧٨',
            '٠',
            '١٢٣٤٥٦٧٨٫٠٠٠',
            '(١٢٣٤٥٦٧٨٫٠٠٠٠٠٠٠٠٠٠٠٠٠٠٠٠٠٠٠٠)',
            'null',
            null,
            '١٢٬٣٤٥٬٦٧٨٫٩',
            '12,345,678.9',
        ];



        $return = [];
        foreach ($output as $locale => $outputValues) {
            $return[] = [$locale, 12345678.9, 20, null, $outputValues[0]];
            $return[] = [$locale, -12345678.9, 20, null, $outputValues[1]];
            $return[] = [$locale, 12345678.9, 20, 'accountingNeg', $outputValues[2]];
            $return[] = [$locale, -12345678.9, 20, 'accountingNeg', $outputValues[3]];
            $return[] = [$locale, 12345678.9, 20, 'showPlus', $outputValues[4]];
            $return[] = [$locale, 12345678, 20, null, $outputValues[5]];
            $return[] = [$locale, 12345678.9, 3, 'trailZeros', $outputValues[6]];
            $return[] = [$locale, 12345678, 3, 'trailZeros', $outputValues[7]];
            $return[] = [$locale, 12345678, 20, '!thousands', $outputValues[8]];
            $return[] = [$locale, null, 20, 'null=0', $outputValues[9]];

            $return[] = [$locale,
                12345678,
                3,
                'trailZeros -thousands',
                $outputValues[10]
            ];

            $return[] = [
                $locale,
                -12345678,
                20,
                'trailZeros -thousands accountingNeg showPlus null="null"',
                $outputValues[11]];

            $return[] = [$locale, null, 20, 'null="null"', $outputValues[12]];
            $return[] = [$locale, null, 20, null, $outputValues[13]];
            $return[] = [$locale, 12345678.9, 20, 'breaking', $outputValues[14]];
            $return[] = [$locale, 12345678.9, 20, 'locale=en-AU', $outputValues[15]];
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
    public function test_realnum_default_settings(): void
    {

        // check the default locale
        RealNum::resetDefaults();
        self::assertSame('en', RealNum::getDefaultLocale());
        self::assertSame('en', RealNum::new()->locale); // uses the default
        RealNum::setDefaultLocale('en-AU');
        self::assertSame('en-AU', RealNum::getDefaultLocale());
        self::assertSame('en-AU', RealNum::new()->locale); // uses the new default

        // check the default max decimal places
        RealNum::resetDefaults();
        self::assertSame(20, RealNum::getDefaultMaxDecPL());
        self::assertSame('1.23456789012345678901', RealNum::new('1.2345678901234567890123')->val); // uses the default
        RealNum::setDefaultMaxDecPl(5);
        self::assertSame(5, RealNum::getDefaultMaxDecPL());
        self::assertSame('1.23457', (RealNum::new('1.2345678901234567890123'))->val); // uses the new default

        // check the default immutable-setting
        RealNum::resetDefaults();
        self::assertTrue(RealNum::getDefaultImmutability());
        self::assertTrue(RealNum::new()->immutable); // uses the default
        RealNum::setDefaultImmutability(false);
        self::assertFalse(RealNum::getDefaultImmutability());
        self::assertFalse(RealNum::new()->immutable); // uses the new default

        // check the default format-settings
        RealNum::resetDefaults();
        self::assertSame(RealNum::ORIG_FORMAT_SETTINGS, RealNum::getDefaultFormatSettings());
        self::assertSame(RealNum::ORIG_FORMAT_SETTINGS, RealNum::new()->formatSettings); // uses the default
        RealNum::setDefaultFormatSettings(self::$altFormatSettings);
        self::assertSame(self::$altFormatSettings, RealNum::getDefaultFormatSettings());
        self::assertSame(self::$altFormatSettings, RealNum::new()->formatSettings); // uses the new default

        // check that the defaults are all set
        RealNum::resetDefaults();
        self::assertSame('en', RealNum::getDefaultLocale());
        self::assertSame(20, RealNum::getDefaultMaxDecPL());
        self::assertTrue(RealNum::getDefaultImmutability());
    }

    /**
     * Test the ways the RealNum class can be instantiated.
     *
     * @test
     *
     * @return void
     */
    #[Test]
    public function test_realnum_instantiation(): void
    {
        self::assertNull((new RealNum())->cast);
        self::assertNull(RealNum::new()->cast);

        self::assertSame(2, (new RealNum(2))->cast);
        self::assertSame(2, RealNum::new(2)->cast);

        self::assertSame(2.23948239, (new RealNum(2.239482390))->cast);
        self::assertSame(2.23948239, RealNum::new(2.239482390)->cast);

        self::assertSame(2.23948239, (new RealNum('2.239482390'))->cast);
        self::assertSame(2.23948239, RealNum::new('2.239482390')->cast);

        self::assertSame(2.23948239, (new RealNum(new RealNum(2.239482390)))->cast);
        self::assertSame(2.23948239, RealNum::new(RealNum::new(2.239482390))->cast);

        // check that the new object picks up the given object's decimal-places
        self::assertSame(5, ((new RealNum(new RealNum(2.239482390)))->maxDecPl(5))->maxDecPl);
        self::assertSame(5, RealNum::new(RealNum::new(2.239482390)->maxDecPl(5))->maxDecPl);

        self::assertNull((new RealNum())->cast);
        self::assertNull(RealNum::new()->cast);

        self::assertNull((new RealNum('null'))->cast);
        self::assertNull(RealNum::new('null')->cast);
        self::assertNull((new RealNum('NULL'))->cast);
        self::assertNull(RealNum::new('NULL')->cast);

        // won't throw an exception for an invalid starting value
        self::assertNull((new RealNum('abc', false))->cast);
        self::assertNull(RealNum::new('abc', false)->cast);

        // cloning
        $num = RealNum::new()->immutable(true);
        self::assertNotSame($num, $num->copy());
        $num = RealNum::new()->immutable(false);
        self::assertNotSame($num, $num->copy());
    }

    /**
     * Test the various ways of changing values in RealNum when immutable / not immutable.
     *
     * @test
     * @dataProvider immutableDataProviderSetters
     *
     * @param boolean $immutable  Run the tests in immutable mode?.
     * @param string  $setMethod  The name of the method to call to set the value.
     * @param string  $getField   The name of the value to get to check the value afterwards.
     * @param mixed   $startValue The value to start with.
     * @param mixed   $endValue   The value to end up with.
     * @return void
     */
    #[Test]
    #[DataProvider('immutableDataProviderSetters')]
    public function test_realnum_immutability_setters(
        bool $immutable,
        string $setMethod,
        string $getField,
        $startValue,
        $endValue
    ): void {

        $finalValue = ($immutable ? $startValue : $endValue); // either the value changed or it didn't

        // set the value directly (uses the __set magic method)
        // $realNum = RealNum::new($startValue)->immutable($immutable)->$setMethod($startValue);
        // $realNum->$getField = $endValue; // not immutable when set this way
        // self::assertSame($endValue, $realNum->$getField);

        // set the value by calling the method
        $realNum = RealNum::new()->immutable($immutable)->$setMethod($startValue);
        $realNum->$setMethod($endValue);
        self::assertSame($finalValue, $realNum->$getField);
    }

    /**
     * Test the immutability when using methods that alter a RealNum's value.
     *
     * @test
     * @dataProvider immutableDataProviderAlterationMethods
     *
     * @param boolean $immutable  Run the tests in immutable mode?.
     * @param string  $method     The RealNum method to call.
     * @param mixed   $params     The params to pass to the method.
     * @param mixed   $startValue The value to start with.
     * @param mixed   $endValue   The value to end up with.
     * @return void
     */
    #[Test]
    #[DataProvider('immutableDataProviderAlterationMethods')]
    public function test_realnum_immutability_alter_methods(
        bool $immutable,
        string $method,
        $params,
        $startValue,
        $endValue
    ): void {

        $finalValue = ($immutable ? $startValue : $endValue); // either the value changed or it didn't

        $realNum = RealNum::new($startValue)->immutable($immutable);
        $callable = [$realNum, $method];
        if (is_callable($callable)) { // to please phpstan
            call_user_func_array($callable, $params);
        }
        self::assertSame($finalValue, $realNum->cast);
    }

    /**
     * Test setting various RealNum values.
     *
     * @test
     *
     * @return void
     */
    #[Test]
    public function test_realnum_settings(): void
    {
        // callback
        $callback = function () {
        };
        RealNum::localeResolver($callback);
        self::assertSame($callback, RealNum::getLocaleResolver());
        RealNum::localeResolver(null);
        self::assertNull(RealNum::getLocaleResolver());
        RealNum::resetDefaults();

        // locale
        self::assertSame('en', RealNum::new()->locale); // is the default
        self::assertSame('en-AU', RealNum::new()->locale('en-AU')->locale);

        // maxDecPl
        self::assertSame(20, RealNum::new()->maxDecPl); // is the default
        self::assertSame(10, RealNum::new()->maxDecPl(10)->maxDecPl);

        // immutable
        self::assertTrue(RealNum::new()->immutable); // is the default
        self::assertFalse(RealNum::new()->immutable(false)->immutable);

        // formatSettings
        self::assertSame(RealNum::ORIG_FORMAT_SETTINGS, RealNum::new()->formatSettings); // is the default
        self::assertSame(
            self::$altFormatSettings,
            RealNum::new()->formatSettings(self::$altFormatSettings)->formatSettings
        );
    }

    /**
     * Test the various methods that perform a RealNum calculation.
     *
     * @test
     *
     * @return void
     */
    #[Test]
    public function test_realnum_alterations(): void
    {
        self::assertSame(5, RealNum::new()->val(5)->cast);

        self::assertSame(5, RealNum::new(10)->val(5)->cast);

        self::assertSame(5, RealNum::new(5.222222)->round()->cast);
        self::assertSame(5.22, RealNum::new(5.222222)->round(2)->cast);

        self::assertSame(5, RealNum::new(5.222222)->floor()->cast);

        self::assertSame(6, RealNum::new(5.222222)->ceil()->cast);

        self::assertSame(5, RealNum::new(5)->add()->cast);
        self::assertSame(15, RealNum::new(5)->add(10)->cast);
        self::assertSame(17, RealNum::new(5)->add(10, 2)->cast);
        self::assertSame(22, RealNum::new(5)->add(10, 2, 5)->cast);

        self::assertSame(10, RealNum::new(10)->div()->cast);
        self::assertSame(5, RealNum::new(10)->div(2)->cast);
        self::assertSame(1, RealNum::new(10)->div(2, 5)->cast);
        self::assertSame(1, RealNum::new(10)->div(2, 5, 1)->cast);

        self::assertSame(10, RealNum::new(10)->mul()->cast);
        self::assertSame(20, RealNum::new(10)->mul(2)->cast);
        self::assertSame(100, RealNum::new(10)->mul(2, 5)->cast);
        self::assertSame(100, RealNum::new(10)->mul(2, 5, 1)->cast);

        self::assertSame(1, RealNum::new(1)->sub()->cast);
        self::assertSame(-1, RealNum::new(1)->sub(2)->cast);
        self::assertSame(-4, RealNum::new(1)->sub(2, 3)->cast);
        self::assertSame(-8, RealNum::new(1)->sub(2, 3, 4)->cast);

        self::assertSame(6, RealNum::new(5)->inc()->cast);
        self::assertSame(15, RealNum::new(5)->inc(10)->cast);

        self::assertSame(4, RealNum::new(5)->dec()->cast);
        self::assertSame(-5, RealNum::new(5)->dec(10)->cast);

        self::assertSame(0, RealNum::new(0)->abs()->cast);
        self::assertSame(5, RealNum::new(5)->abs()->cast);
        self::assertSame(5, RealNum::new(-5)->abs()->cast);

        // chaining
        self::assertSame(
            19.35,
            RealNum::new()
                ->val(10.44444)
                ->round(2)
                ->floor()
                ->add(0.2)
                ->ceil()
                ->sub(0.1)
                ->div(2)
                ->mul(3)
                ->inc(5)
                ->dec(2)
                ->cast
        );

        // internal precision - defaults to maxDecPl 20
        self::assertSame('5.00123456789012345679', RealNum::new('5.0012345678901234567890123456789')->val);
    }

    /**
     * Test the various methods that perform a calculation and generate a result.
     *
     * @test
     *
     * @return void
     */
    #[Test]
    public function test_realnum_comparisons(): void
    {
        // less-than
        self::assertTrue(RealNum::new(9.9999)->lt(10));
        self::assertTrue(RealNum::new(9.9999)->lessThan(10));
        self::assertFalse(RealNum::new(10)->lt(10));
        self::assertFalse(RealNum::new(10)->lessThan(10));
        self::assertFalse(RealNum::new(10.0001)->lt(10));
        self::assertFalse(RealNum::new(10.0001)->lessThan(10));

        self::assertFalse(RealNum::new(9.9999)->lt(10, 9.9999));
        self::assertFalse(RealNum::new(9.9999)->lessThan(10, 9.9999));
        self::assertTrue(RealNum::new(9.9999)->lt(10, 10.0001));
        self::assertTrue(RealNum::new(9.9999)->lessThan(10, 10.0001));
        self::assertFalse(RealNum::new(10)->lt(10, 9.9999));
        self::assertFalse(RealNum::new(10)->lessThan(10, 9.9999));
        self::assertFalse(RealNum::new(10)->lt(10, 10.0001));
        self::assertFalse(RealNum::new(10)->lessThan(10, 10.0001));
        self::assertFalse(RealNum::new(10.0001)->lt(10));
        self::assertFalse(RealNum::new(10.0001)->lessThan(10));
        self::assertFalse(RealNum::new(10.0001)->lt(10, 10.0001));
        self::assertFalse(RealNum::new(10.0001)->lessThan(10, 10.0001));

        // less-than or equal
        self::assertTrue(RealNum::new(9.9999)->lte(10));
        self::assertTrue(RealNum::new(9.9999)->lessThanOrEqualTo(10));
        self::assertTrue(RealNum::new(10)->lte(10));
        self::assertTrue(RealNum::new(10)->lessThanOrEqualTo(10));
        self::assertFalse(RealNum::new(10.0001)->lte(10));
        self::assertFalse(RealNum::new(10.0001)->lessThanOrEqualTo(10));

        self::assertTrue(RealNum::new(9.9999)->lte(10, 9.9999));
        self::assertTrue(RealNum::new(9.9999)->lessThanOrEqualTo(10, 9.9999));
        self::assertTrue(RealNum::new(9.9999)->lte(10, 10.0001));
        self::assertTrue(RealNum::new(9.9999)->lessThanOrEqualTo(10, 10.0001));
        self::assertFalse(RealNum::new(10)->lte(10, 9.9999));
        self::assertFalse(RealNum::new(10)->lessThanOrEqualTo(10, 9.9999));
        self::assertTrue(RealNum::new(10)->lte(10, 10.0001));
        self::assertTrue(RealNum::new(10)->lessThanOrEqualTo(10, 10.0001));
        self::assertFalse(RealNum::new(10.0001)->lte(10));
        self::assertFalse(RealNum::new(10.0001)->lessThanOrEqualTo(10));
        self::assertFalse(RealNum::new(10.0001)->lte(10, 10.0001));
        self::assertFalse(RealNum::new(10.0001)->lessThanOrEqualTo(10, 10.0001));

        // equal
        self::assertFalse(RealNum::new(9.9999)->eq(10));
        self::assertFalse(RealNum::new(9.9999)->equalTo(10));
        self::assertTrue(RealNum::new(10)->eq(10));
        self::assertTrue(RealNum::new(10)->equalTo(10));
        self::assertFalse(RealNum::new(10.0001)->eq(10));
        self::assertFalse(RealNum::new(10.0001)->equalTo(10));

        self::assertFalse(RealNum::new(9.9999)->eq(10, 9.9999));
        self::assertFalse(RealNum::new(9.9999)->equalTo(10, 9.9999));
        self::assertFalse(RealNum::new(9.9999)->eq(10, 10.0001));
        self::assertFalse(RealNum::new(9.9999)->equalTo(10, 10.0001));
        self::assertFalse(RealNum::new(10)->eq(10, 9.9999));
        self::assertFalse(RealNum::new(10)->equalTo(10, 9.9999));
        self::assertFalse(RealNum::new(10)->eq(10, 10.0001));
        self::assertFalse(RealNum::new(10)->equalTo(10, 10.0001));
        self::assertFalse(RealNum::new(10.0001)->eq(10));
        self::assertFalse(RealNum::new(10.0001)->equalTo(10));
        self::assertFalse(RealNum::new(10.0001)->eq(10, 10.0001));
        self::assertFalse(RealNum::new(10.0001)->equalTo(10, 10.0001));

        // greater-than or equal
        self::assertFalse(RealNum::new(9.9999)->gte(10));
        self::assertFalse(RealNum::new(9.9999)->greaterThanOrEqualTo(10));
        self::assertTrue(RealNum::new(10)->gte(10));
        self::assertTrue(RealNum::new(10)->greaterThanOrEqualTo(10));
        self::assertTrue(RealNum::new(10.0001)->gte(10));
        self::assertTrue(RealNum::new(10.0001)->greaterThanOrEqualTo(10));

        self::assertFalse(RealNum::new(9.9999)->gte(10, 9.9999));
        self::assertFalse(RealNum::new(9.9999)->greaterThanOrEqualTo(10, 9.9999));
        self::assertFalse(RealNum::new(9.9999)->gte(10, 10.0001));
        self::assertFalse(RealNum::new(9.9999)->greaterThanOrEqualTo(10, 10.0001));
        self::assertTrue(RealNum::new(10)->gte(10, 9.9999));
        self::assertTrue(RealNum::new(10)->greaterThanOrEqualTo(10, 9.9999));
        self::assertFalse(RealNum::new(10)->gte(10, 10.0001));
        self::assertFalse(RealNum::new(10)->greaterThanOrEqualTo(10, 10.0001));
        self::assertTrue(RealNum::new(10.0001)->gte(10));
        self::assertTrue(RealNum::new(10.0001)->greaterThanOrEqualTo(10));
        self::assertTrue(RealNum::new(10.0001)->gte(10, 10.0001));
        self::assertTrue(RealNum::new(10.0001)->greaterThanOrEqualTo(10, 10.0001));

        // greater-than
        self::assertFalse(RealNum::new(9.9999)->gt(10));
        self::assertFalse(RealNum::new(9.9999)->greaterThan(10));
        self::assertFalse(RealNum::new(10)->gt(10));
        self::assertFalse(RealNum::new(10)->greaterThan(10));
        self::assertTrue(RealNum::new(10.0001)->gt(10));
        self::assertTrue(RealNum::new(10.0001)->greaterThan(10));

        self::assertFalse(RealNum::new(9.9999)->gt(10, 9.9999));
        self::assertFalse(RealNum::new(9.9999)->greaterThan(10, 9.9999));
        self::assertFalse(RealNum::new(9.9999)->gt(10, 10.0001));
        self::assertFalse(RealNum::new(9.9999)->greaterThan(10, 10.0001));
        self::assertFalse(RealNum::new(10)->gt(10, 9.9999));
        self::assertFalse(RealNum::new(10)->greaterThan(10, 9.9999));
        self::assertFalse(RealNum::new(10)->gt(10, 10.0001));
        self::assertFalse(RealNum::new(10)->greaterThan(10, 10.0001));
        self::assertTrue(RealNum::new(10.0001)->gt(10));
        self::assertTrue(RealNum::new(10.0001)->greaterThan(10));
        self::assertFalse(RealNum::new(10.0001)->gt(10, 10.0001));
        self::assertFalse(RealNum::new(10.0001)->greaterThan(10, 10.0001));

        // between - NOT inclusive
        self::assertFalse(RealNum::new(9.9998)->between(9.9999, 10, false));
        self::assertFalse(RealNum::new(9.9998)->between(9.9999, 10.0001, false));
        self::assertFalse(RealNum::new(9.9998)->between(10, 10.0001, false));

        self::assertFalse(RealNum::new(9.9998)->between(9.9999, null, false));
        self::assertTrue(RealNum::new(9.9998)->between(null, 10.0001, false));

        self::assertFalse(RealNum::new(9.9999)->between(9.9999, 10, false));
        self::assertFalse(RealNum::new(9.9999)->between(9.9999, 10.0001, false));
        self::assertFalse(RealNum::new(9.9999)->between(10, 10.0001, false));

        self::assertFalse(RealNum::new(9.9999)->between(9.9999, null, false));
        self::assertTrue(RealNum::new(9.9999)->between(null, 10.0001, false));

        self::assertFalse(RealNum::new(10)->between(9.9999, 10, false));
        self::assertTrue(RealNum::new(10)->between(9.9999, 10.0001, false));
        self::assertFalse(RealNum::new(10)->between(10, 10.0001, false));

        self::assertTrue(RealNum::new(10)->between(9.9999, null, false));
        self::assertTrue(RealNum::new(10)->between(null, 10.0001, false));

        self::assertFalse(RealNum::new(10.0001)->between(9.9999, 10, false));
        self::assertFalse(RealNum::new(10.0001)->between(9.9999, 10.0001, false));
        self::assertFalse(RealNum::new(10.0001)->between(10, 10.0001, false));

        self::assertTrue(RealNum::new(10.0001)->between(9.9999, null, false));
        self::assertFalse(RealNum::new(10.0001)->between(null, 10.0001, false));

        self::assertFalse(RealNum::new(10.0002)->between(9.9999, 10, false));
        self::assertFalse(RealNum::new(10.0002)->between(9.9999, 10.0001, false));
        self::assertFalse(RealNum::new(10.0002)->between(10, 10.0001, false));

        self::assertTrue(RealNum::new(10.0002)->between(9.9999, null, false));
        self::assertFalse(RealNum::new(10.0002)->between(null, 10.0001, false));

        self::assertTrue(RealNum::new(10)->between(null, null, false));

        // between - INCLUSIVE
        self::assertFalse(RealNum::new(9.9998)->between(9.9999, 10, true));
        self::assertFalse(RealNum::new(9.9998)->between(9.9999, 10.0001, true));
        self::assertFalse(RealNum::new(9.9998)->between(10, 10.0001, true));

        self::assertFalse(RealNum::new(9.9998)->between(9.9999, null, true));
        self::assertTrue(RealNum::new(9.9998)->between(null, 10.0001, true));

        self::assertTrue(RealNum::new(9.9999)->between(9.9999, 10, true));
        self::assertTrue(RealNum::new(9.9999)->between(9.9999, 10.0001, true));
        self::assertFalse(RealNum::new(9.9999)->between(10, 10.0001, true));

        self::assertTrue(RealNum::new(9.9999)->between(9.9999, null, true));
        self::assertTrue(RealNum::new(9.9999)->between(null, 10.0001, true));

        self::assertTrue(RealNum::new(10)->between(9.9999, 10, true));
        self::assertTrue(RealNum::new(10)->between(9.9999, 10.0001, true));
        self::assertTrue(RealNum::new(10)->between(10, 10.0001, true));

        self::assertTrue(RealNum::new(10)->between(9.9999, null, true));
        self::assertTrue(RealNum::new(10)->between(null, 10.0001, true));

        self::assertFalse(RealNum::new(10.0001)->between(9.9999, 10, true));
        self::assertTrue(RealNum::new(10.0001)->between(9.9999, 10.0001, true));
        self::assertTrue(RealNum::new(10.0001)->between(10, 10.0001, true));

        self::assertTrue(RealNum::new(10.0001)->between(9.9999, null, true));
        self::assertTrue(RealNum::new(10.0001)->between(null, 10.0001, true));

        self::assertFalse(RealNum::new(10.0002)->between(9.9999, 10, true));
        self::assertFalse(RealNum::new(10.0002)->between(9.9999, 10.0001, true));
        self::assertFalse(RealNum::new(10.0002)->between(10, 10.0001, true));

        self::assertTrue(RealNum::new(10.0002)->between(9.9999, null, true));
        self::assertFalse(RealNum::new(10.0002)->between(null, 10.0001, true));

        self::assertTrue(RealNum::new(10)->between(null, null, true));



        // using RealNums as comparison values
        self::assertTrue(RealNum::new(9.9999)->lt(RealNum::new(10)));
        self::assertTrue(RealNum::new(9.9999)->lte(RealNum::new(10)));
        self::assertFalse(RealNum::new(9.9999)->eq(RealNum::new(10)));
        self::assertFalse(RealNum::new(9.9999)->gte(RealNum::new(10)));
        self::assertFalse(RealNum::new(9.9999)->eq(RealNum::new(10)));

        self::assertTrue(RealNum::new(9.9999)->lt(RealNum::new(10), 10.0001));
        self::assertTrue(RealNum::new(9.9999)->lte(RealNum::new(10), 10.0001));
        self::assertFalse(RealNum::new(9.9999)->eq(RealNum::new(10), 10.0001));
        self::assertFalse(RealNum::new(9.9999)->gte(RealNum::new(10), 10.0001));
        self::assertFalse(RealNum::new(9.9999)->eq(RealNum::new(10), 10.0001));
        self::assertFalse(RealNum::new(9.9999)->between(RealNum::new(10), 10.0001));

        self::assertTrue(RealNum::new(9.9999)->lt(RealNum::new(10), new RealNum(10.0001)));
        self::assertTrue(RealNum::new(9.9999)->lte(RealNum::new(10), new RealNum(10.0001)));
        self::assertFalse(RealNum::new(9.9999)->eq(RealNum::new(10), new RealNum(10.0001)));
        self::assertFalse(RealNum::new(9.9999)->gte(RealNum::new(10), new RealNum(10.0001)));
        self::assertFalse(RealNum::new(9.9999)->eq(RealNum::new(10), new RealNum(10.0001)));
        self::assertFalse(RealNum::new(9.9999)->between(RealNum::new(10), new RealNum(10.0001)));
    }

    /**
     * Test the different ways to get the value from RealNum.
     *
     * @test
     *
     * @return void
     */
    #[Test]
    public function test_realnum_value_types_and_retrieval(): void
    {
        self::assertSame(5, RealNum::new(5)->cast); // int
        self::assertSame('5.00000000000000000000', RealNum::new(5)->val);

        self::assertSame(5, RealNum::new(5.0)->cast); // int
        self::assertSame('5.00000000000000000000', RealNum::new(5.0)->val);

        self::assertSame(5.001, RealNum::new(5.001)->cast); // float
        self::assertSame('5.00100000000000000000', RealNum::new(5.001)->val);

        self::assertSame(5, RealNum::new('5')->cast); // int
        self::assertSame('5.00000000000000000000', RealNum::new('5')->val);

        self::assertSame(5, RealNum::new('5.000')->cast); // int
        self::assertSame('5.00000000000000000000', RealNum::new('5.000')->val);

        self::assertSame(5.001, RealNum::new('5.001')->cast); // float
        self::assertSame('5.00100000000000000000', RealNum::new('5.001')->val);

        self::assertSame(5.12345678, RealNum::new('5.12345678')->cast); // float
        self::assertSame('5.12345678000000000000', RealNum::new('5.12345678')->val);
    }

    /**
     * Test RealNum calculations with null values.
     *
     * @test
     *
     * @return void
     */
    #[Test]
    public function test_how_realnum_calculations_deal_with_nulls(): void
    {
        self::assertNull(RealNum::new(null)->round()->val);

        self::assertNull(RealNum::new(null)->floor()->val);

        self::assertNull(RealNum::new(null)->ceil()->val);

        self::assertNull(RealNum::new(null)->add(null)->val);
        self::assertSame('1.00000000000000000000', RealNum::new(1)->add(null)->val);
        self::assertSame('1.00000000000000000000', RealNum::new(null)->add(1)->val);
        self::assertSame('2.00000000000000000000', RealNum::new(1)->add(1)->val);

        self::assertNull(RealNum::new(null)->div(null)->val);
        self::assertNull(RealNum::new(1)->div(null)->val);
        self::assertNull(RealNum::new(null)->div(1)->val);
        self::assertSame('1.00000000000000000000', RealNum::new(1)->div(1)->val);

        self::assertNull(RealNum::new(null)->mul(null)->val);
        self::assertNull(RealNum::new(1)->mul(null)->val);
        self::assertNull(RealNum::new(null)->mul(1)->val);
        self::assertSame('1.00000000000000000000', RealNum::new(1)->mul(1)->val);

        self::assertNull(RealNum::new(null)->sub(null)->val);
        self::assertSame('1.00000000000000000000', RealNum::new(1)->sub(null)->val);
        self::assertSame('-1.00000000000000000000', RealNum::new(null)->sub(1)->val);
        self::assertSame('0.00000000000000000000', RealNum::new(1)->sub(1)->val);

        self::assertNull(RealNum::new(null)->inc(null)->val);
        self::assertSame('1.00000000000000000000', RealNum::new(1)->inc(null)->val);
        self::assertSame('1.00000000000000000000', RealNum::new(null)->inc(1)->val);
        self::assertSame('2.00000000000000000000', RealNum::new(1)->inc(1)->val);

        self::assertNull(RealNum::new(null)->dec(null)->val);
        self::assertSame('1.00000000000000000000', RealNum::new(1)->dec(null)->val);
        self::assertSame('-1.00000000000000000000', RealNum::new(null)->dec(1)->val);
        self::assertSame('0.00000000000000000000', RealNum::new(1)->dec(1)->val);

        self::assertTrue(RealNum::new(null)->isNull());
        self::assertFalse(RealNum::new(1)->isNull());
    }

    /**
     * Test RealNum calculations with many decimal places.
     *
     * @test
     *
     * @return void
     */
    #[Test]
    public function test_realnum_calculations_with_many_decimal_places(): void
    {
        self::assertSame(
            '8.73722735003025702283',
            RealNum::new('3.90234189023482')->mul('2.23897023781904')->val
        );
        self::assertSame(
            '3073985946.73750448327552961777',
            RealNum::new('131233.50237556398378')->mul('23423.78958949348734')->val
        );
        self::assertSame(
            '711773697729488716745198.72572677224267178534',
            RealNum::new('78663564536.12390478238901')->mul('9048327544356.67346466343363')->val
        );

        self::assertSame(
            '1.74291816135798874807',
            RealNum::new('3.90234189023482')->div('2.23897023781904')->val
        );
        self::assertSame(
            '5.60257348086952988108',
            RealNum::new('131233.50237556398378')->div('23423.78958949348734')->val
        );
        self::assertSame(
            '0.00869371319180254113',
            RealNum::new('78663564536.12390478238901')->div('9048327544356.67346466343363')->val
        );
    }

    /**
     * Test the different ways to render the RealNum value.
     *
     * @test
     *
     * @return void
     */
    #[Test]
    public function test_realnum_general_rendering(): void
    {
        // this fails, PHP's NumberFormatter format()'s the number to 15 decimal places
//        self::assertSame('5.12345678901234567890', (string) RealNum::new('5.12345678901234567890'));

        self::assertSame('', (string) new RealNum());

        self::assertSame('null', RealNum::new()->format('null="null"'));

        self::assertSame('5', RealNum::new(5)->format());
        self::assertSame('5', RealNum::new(5)->maxDecPl(2)->format());
        self::assertSame('5.99', RealNum::new(5.98765)->maxDecPl(2)->format());
        self::assertSame('5,000,000', RealNum::new(5000000)->format());
    }

    /**
     * Test the different ways to the RealNum value can be rendered.
     *
     * @test
     * @dataProvider localeRenderingDataProvider
     *
     * @param string            $locale        The locale to use.
     * @param float|null        $initialValue  The value to render.
     * @param integer           $maxDecPl      The maximum decimal places to use.
     * @param string|array|null $renderOptions The options to use while rendering.
     * @param string|null       $expectedValue The expected render output.
     * @return void
     */
    #[Test]
    #[DataProvider('localeRenderingDataProvider')]
    public function test_realnum_locale_rendering(
        string $locale,
        ?float $initialValue,
        int $maxDecPl,
        $renderOptions,
        ?string $expectedValue
    ): void {

        self::assertSame(
            $expectedValue,
            RealNum::new($initialValue)->locale($locale)->maxDecPl($maxDecPl)->format($renderOptions)
        );
    }

    /**
     * Test the __toString magic method.
     *
     * @test
     *
     * @return void
     */
    #[Test]
    public function test_realnum_locale_casting_to_string(): void
    {
        $cur1 = RealNum::new(1.234567890)->locale('en-AU');
        self::assertSame('1.23456789', (string) $cur1);
    }

    /**
     * Test how the RealNum class handles different decimal places, and rounding.
     *
     * @test
     *
     * @return void
     */
    #[Test]
    public function test_realnum_decimal_places(): void
    {
        self::assertSame('1.2346', RealNum::new()->maxDecPl(4)->val('1.234567890')->val);
        self::assertSame('1.23', RealNum::new()->maxDecPl(4)->val('1.234567890')->maxDecPl(2)->val);
        self::assertSame('1.2300', RealNum::new()->maxDecPl(2)->val('1.234567890')->maxDecPl(4)->val);

        self::assertSame(20, RealNum::new()->maxDecPl);
        self::assertSame(5, RealNum::new()->maxDecPl(5)->maxDecPl);
        self::assertSame(2, RealNum::new()->maxDecPl(5)->maxDecPl(2)->maxDecPl);

        // alter a RealNum's maxDecPl a few times
        $realNum = RealNum::new();
        $realNum = $realNum->maxDecPl(5);
        self::assertSame(5, $realNum->maxDecPl);

        $realNum = $realNum->maxDecPl(3);
        self::assertSame(3, $realNum->maxDecPl);

        // test rendering when decPl is specified explicitly
        $realNum = RealNum::new(5.983456789);
        self::assertSame('5.98345678900000000000', $realNum->format('decPl=null trailZeros'));
        self::assertSame('5.983456789', $realNum->format('decPl=null')); // defaults to !trailZeros
        self::assertSame('5.983456789', $realNum->format('decPl=null !trailZeros'));

        self::assertSame('5.983456789000000', $realNum->format('decPl=15 trailZeros'));
        self::assertSame('5.983456789000000', $realNum->format('decPl=15')); // defaults to trailZeros
        self::assertSame('5.983456789', $realNum->format('decPl=15 !trailZeros'));

        self::assertSame('5.9835', $realNum->format('decPl=4 trailZeros')); // rounded
        self::assertSame('5.9835', $realNum->format('decPl=4 !trailZeros')); // rounded

        self::assertSame('6.0', $realNum->format('decPl=1 trailZeros')); // rounded
        self::assertSame('6', $realNum->format('decPl=1 !trailZeros')); // rounded

        self::assertSame('6', $realNum->format('decPl=0 trailZeros')); // rounded
        self::assertSame('6', $realNum->format('decPl=0 !trailZeros')); // rounded
    }

    /**
     * Test how the RealNum class' default locale is set and used.
     *
     * @test
     *
     * @return void
     */
    #[Test]
    public function test_changing_of_realnum_locales(): void
    {
        self::assertSame('en', RealNum::new()->locale); // uses the default

        // set by calling the locale() method
        self::assertSame('en-NZ', RealNum::new()->locale('en-NZ')->locale);
        self::assertSame('en-US', RealNum::new()->locale('en-NZ')->locale('en-US')->locale);

        // set by using the magic __set method
        // $realNum = RealNum::new();
        // $realNum->locale = 'en-AU';
        // self::assertSame('en-AU', $realNum->locale);
    }

    /**
     * Test the locale resolver.
     *
     * @test
     *
     * @return void
     */
    #[Test]
    public function test_realnum_class_locale_resolver(): void
    {
        $closureWasRun = false;
        $localeResolver = function ($localeIdentifier) use (&$closureWasRun) {
            $closureWasRun = true;
            return ($localeIdentifier === 99 ? 'en-AU' : null);
        };

        RealNum::localeResolver($localeResolver);
        self::assertSame('en-AU', RealNum::new()->locale(99)->locale);
        self::assertTrue($closureWasRun);
    }

    /**
     * Test the different values that RealNum can use.
     *
     * @test
     *
     * @return void
     */
    #[Test]
    public function test_realnum_accepted_value_types(): void
    {
        self::assertSame(5, RealNum::new(5)->cast);
        self::assertSame(5, RealNum::new('5')->cast);
        self::assertSame(5.1, RealNum::new(5.1)->cast);

        $cur2 = new RealNum(5);
        self::assertSame(5, RealNum::new($cur2)->cast);

        // PHPUnit\Framework\Constraint\Exception is required by jchook/phpunit-assert-throws
        if (class_exists(ConstraintException::class)) {

            // initial value is invalid - boolean
            $caughtException = false;
            try {
                RealNum::new(true); // phpstan false positive
            } catch (InvalidValueException $e) {
                $caughtException = true;
            }
            self::assertTrue($caughtException);

            // initial value is invalid - non-numeric string
            $caughtException = false;
            try {
                RealNum::new('abc');
            } catch (InvalidValueException $e) {
                $caughtException = true;
            }
            self::assertTrue($caughtException);

            // initial value is invalid - object
            $caughtException = false;
            try {
                RealNum::new(new stdClass()); // phpstan false positive
            } catch (InvalidValueException $e) {
                $caughtException = true;
            }
            self::assertTrue($caughtException);
        }
    }

    /**
     * Test the ways RealNum generates exceptions.
     *
     * @test
     *
     * @return void
     */
    #[Test]
    public function test_realnum_exceptions(): void
    {
        // PHPUnit\Framework\Constraint\Exception is required by jchook/phpunit-assert-throws
        if (class_exists(ConstraintException::class)) {

            // (pseudo-)property abc doesn't exist to GET
            $caughtException = false;
            try {
                RealNum::new()->abc; // phpstan false positive
            } catch (UndefinedPropertyException $e) {
                $caughtException = true;
            }
            self::assertTrue($caughtException);

            // (pseudo-)property abc doesn't exist to SET
            $caughtException = false;
            try {
                $realNum = RealNum::new();
                $realNum->abc = true; // phpstan false positive
            } catch (UndefinedPropertyException $e) {
                $caughtException = true;
            }
            self::assertTrue($caughtException);

            // invalid value to add
            $caughtException = false;
            try {
                RealNum::new(1)->add(true); // phpstan false positive
            } catch (InvalidValueException $e) {
                $caughtException = true;
            }
            self::assertTrue($caughtException);

            // division by 0
            $exceptionClass = version_compare(phpversion(), '8.0', '>=')
                ? DivisionByZeroError::class
                : Warning::class;
            $caughtException = false;
            try {
                RealNum::new(1)->div(0);
            } catch (DivisionByZeroError $e) {
                $caughtException = (get_class($e) == $exceptionClass);
            } catch (Warning $e) {
                $caughtException = (get_class($e) == $exceptionClass);
            }
            self::assertTrue($caughtException);

            // unresolvable locale
            $caughtException = false;
            try {
                RealNum::new()->locale(1);
            } catch (InvalidLocaleException $e) {
                $caughtException = true;
            }
            self::assertTrue($caughtException);

            // invalid value to compare
            $caughtException = false;
            try {
                RealNum::new(1)->lt(); // no comparison value passed
            } catch (InvalidValueException $e) {
                $caughtException = true;
            }
            self::assertTrue($caughtException);
        }
    }
}
