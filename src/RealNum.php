<?php

namespace CodeDistortion\RealNum;

use CodeDistortion\Options\Options;
use CodeDistortion\RealNum\Exceptions\InvalidValueException;
use CodeDistortion\RealNum\Exceptions\InvalidLocaleException;
use CodeDistortion\RealNum\Exceptions\UndefinedPropertyException;
use NumberFormatter;
use Throwable;

/**
 * Arbitrary-precision floating-point numbers with localised rendering.
 *
 * Represents floating-point numbers, performs calculations & comparisons on them, and renders them.
 *
 * The Percent class extends from this.
 */
class RealNum extends Base
{
    /**
     * The original default format-settings - used when resetting the class-level defaults.
     */
    const ORIG_FORMAT_SETTINGS = [
        'null' => null,
        'trailZeros' => false,
        'decPl' => null,
        'thousands' => true,
        'showPlus' => false,
        'accountingNeg' => false,
        'locale' => 'en',
        'breaking' => false,
    ];



    /**
     * The default maximum number of decimal places available to use (at the class-level).
     *
     * Objects will pick this value up when instantiated.
     *
     * @var integer
     */
    protected static $defaultMaxDecPl = 20;

    /**
     * The default immutable-setting (at the class-level).
     *
     * Objects will pick this value up when instantiated.
     *
     * @var boolean
     */
    protected static $defaultImmutable = true;

    /**
     * The default settings to use when formatting the number (at the class-level).
     *
     * Objects will pick this value up when instantiated.
     *
     * @var array
     */
    protected static $defaultFormatSettings = [
        'null' => null,
        'trailZeros' => false,
        'decPl' => null,
        'thousands' => true,
        'showPlus' => false,
        'accountingNeg' => false,
        'locale' => 'en',
        'breaking' => false,
    ];





    /**
     * Callback used to resolve localeIdentifiers.
     *
     * It may for example understand database ids, and map them back to their 'en-AU' equivalent.
     * When this hasn't been set, the locales are assumed to be strings like 'en-AU' and treated as is.
     *
     * @var callable|null
     */
    protected static $localeResolver = null;

    /**
     * An internal setting - This will add an extra 2 decPl internally when rounding, and will cause it to be rendered
     * as a percentage value.
     *
     * This is because percent values are actually between 0 & 1, so a value of 0.12345 should be output as  12.345%.
     * This value is to be overridden by the child Percent class.
     *
     * @var boolean
     */
    protected static $percentageMode = false;


    /**
     * Build a new RealNum object.
     *
     * @param integer|float|string|self|null $value          The initial value to store.
     * @param boolean                        $throwException Should an exception be thrown if the $value is invalid?
     *                                                       (the value will be set to null upon error otherwise).
     *
     * @return static
     * @throws InvalidValueException Thrown when the given value is invalid (and $throwException is true).
     */
    public static function new($value = null, bool $throwException = true)
    {
        return new static($value, $throwException);
    }





    /**
     * Retrieve the default maximum number of decimal places available to use.
     *
     * @return integer
     */
    public static function getDefaultMaxDecPl(): int
    {
        return static::$defaultMaxDecPl;
    }

    /**
     * Update the default maximum number of decimal places available to use.
     *
     * @param integer $maxDecPl The decimal places to set.
     * @return void
     */
    public static function setDefaultMaxDecPl(int $maxDecPl): void
    {
        static::$defaultMaxDecPl = $maxDecPl;
    }


    /**
     * Get various values stored in this object.
     *
     * @param string $name The field to get.
     * @return mixed
     * @throws UndefinedPropertyException Thrown when accessing an invalid field.
     * @throws InvalidLocaleException     Thrown when the locale cannot be resolved.
     */
    public function __get(string $name)
    {
        switch ($name) {

            // return the maximum number of decimal places available to use
            case 'maxDecPl':
                return $this->maxDecPl;

            // see if the parent can handle this
            default:
                return parent::__get($name);
        }
    }





    /**
     * Set the maximum number of decimal places available for this object to use.
     *
     * @param integer $maxDecPl The new decimal-places to use.
     * @return static
     */
    public function maxDecPl(int $maxDecPl): Base
    {
        return $this->immute()->setDecPl($maxDecPl); // chainable - immutable
    }


    /**
     * Format the current number in a readable way.
     *
     * @param string|array|null $options The options to use when rendering the number.
     * @return string
     * @throws InvalidLocaleException Thrown when the locale cannot be resolved.
     */
    public function format($options = null): ?string
    {
        $value = $this->getVal();
        $parsedOptions = Options::parse($options);
        $resolvedOptions = Options::defaults($this->formatSettings)->resolve($parsedOptions)->all();

        // customise what happens when the value is null
        if ((!is_string($value)) || (!mb_strlen($value))) {
            try {
                $value = static::extractBasicValue(
                    $resolvedOptions['null'],
                    $this->internalMaxDecPl(),
                    false // don't pick up a 'null' string as null
                );
            } catch (Throwable $e) {
                return $resolvedOptions['null']; // it could be a string like 'null'
            }
        }

        // render the value if it's a number
        if ((is_string($value)) && (mb_strlen($value))) {

            $locale = $this->resolveLocaleCode($resolvedOptions['locale']); // locale can be specified by the caller
            $decPl = $resolvedOptions['decPl'];
            $maxDecPl = $this->internalMaxDecPl();
            $type = (static::$percentageMode ? NumberFormatter::PERCENT : NumberFormatter::DECIMAL);

            // if decPl was specified then force trailZeros to be on
            if (!is_null($decPl)) {
                // (as long as the caller didn't explicitly pass a trailZeros setting in the first place)
                if (!array_key_exists('trailZeros', $parsedOptions)) {
                    $resolvedOptions['trailZeros'] = true;
                }
            // otherwise include all the digits, and leave trailZeros alone
            } else {
                $decPl = $this->maxDecPl;
            }

            // remove trailing zeros if desired
            // work out how many decimal places there actually are, because NumberFormatter seems to round
            // to 3 when ::FRACTION_DIGITS isn't set and there are > 3 decimal places
            if (!$resolvedOptions['trailZeros']) {

                // when checking how many decimal places to use for percentages, turn 0.1 into 10 (ie. 0.1 = 10%)
                $checkAmount = (static::$percentageMode ? bcmul($value, '100', $maxDecPl) : $value);

                // make sure the value we're checking has been rounded to the desired number of decimal places
                $checkAmount = (string) static::roundCalculation($checkAmount, $decPl, (int) $this->maxDecPl);

                // see how many decimal places $checkAmount has
                $decPl = min($decPl, static::howManyDecimalPlaces($checkAmount));
            }



            $numberFormatter = new NumberFormatter($locale, $type);
            $numberFormatter->setAttribute(NumberFormatter::FRACTION_DIGITS, $decPl);

            // remove the thousands separator if desired
            if (!$resolvedOptions['thousands']) {
                $numberFormatter->setAttribute(NumberFormatter::GROUPING_SEPARATOR_SYMBOL, null);
            }



            // render the number
            $callback = function ($value) use ($numberFormatter) {
                return $numberFormatter->format($value);
            };

            $return = $this->renderNumber(
                $value,
                $maxDecPl,
                $locale,
                (bool) $resolvedOptions['accountingNeg'],
                (bool) $resolvedOptions['showPlus'],
                (bool) $resolvedOptions['breaking'],
                $numberFormatter,
                $callback
            );

            return $return;
        }

        return null;
    }

    /**
     * Use the given maxDecPl, but use the current one if needed.
     *
     * Adjusts itself for percentage mode.
     *
     * @param integer $maxDecPl The decimal places to use (otherwise the current one is used).
     * @return integer
     */
    protected function internalMaxDecPl(int $maxDecPl = null): int
    {
        $maxDecPl = parent::internalMaxDecPl($maxDecPl);
        $maxDecPl = (static::$percentageMode ? $maxDecPl + 2 : $maxDecPl); // adjust for percent mode
        return (int) $maxDecPl;
    }
}
