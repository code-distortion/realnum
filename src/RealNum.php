<?php

namespace CodeDistortion\RealNum;

use CodeDistortion\Options\Options;
use CodeDistortion\RealNum\Base;
use NumberFormatter;

/**
 * Arbitrary-precision floating-point numbers with localised rendering.
 * Represents floating-point numbers, performs calculations & comparisons on them, and renders them.
 *
 * The Percent class extends from this.
 *
 * @property integer $maxDecPl
 */
class RealNum extends Base
{
    /**
     * The original default format-settings - used when resetting the class-level defaults
     */
    const ORIG_FORMAT_SETTINGS = [
        'thousands' => true,
        'showPlus' => false,
        'accountingNeg' => false,
        'nullString' => false,
        'nullZero' => false,
        'trailZeros' => false,
        'nbsp' => true,
        'locale' => 'en',
    ];



    /**
     * The default maximum number of decimal places available to use (at the class-level)
     *
     * Objects will pick this value up when instantiated.
     * @var integer
     */
    protected static $defaultMaxDecPl = 20;

    /**
     * The default immutable-setting (at the class-level).
     *
     * Objects will pick this value up when instantiated.
     * @var boolean
     */
    protected static $defaultImmutable = true;

    /**
     * The default settings to use when formatting the number (at the class-level).
     *
     * Objects will pick this value up when instantiated.
     * @var array
     */
    protected static $defaultFormatSettings = [
        'thousands' => true,
        'showPlus' => false,
        'accountingNeg' => false,
        'nullString' => false,
        'nullZero' => false,
        'trailZeros' => false,
        'nbsp' => true,
        'locale' => 'en',
    ];





    /**
     * Callback used to resolve localeIdentifiers
     *
     * It may for example understand database ids, and map them back to their 'en-AU' equivalent.
     * When this hasn't been set, the locales are assumed to be strings like 'en-AU' and treated as is.
     * @var ?callable
     */
    protected static $localeResolver = null;





    /**
     * Build a new RealNum object
     *
     * @param integer|float|string|self|null $value          The initial value to store.
     * @param boolean                        $throwException Should an exception be thrown if the $value is invalid?
     *                                                       (the value will be set to null upon error otherwise).
     * @return static
     */
    public static function new($value = null, bool $throwException = true)
    {
        return new static($value, $throwException);
    }





    /**
     * Retrieve the default maximum number of decimal places available to use
     *
     * @return integer
     */
    public static function getDefaultMaxDecPl(): int
    {
        return static::$defaultMaxDecPl;
    }

    /**
     * Update the default maximum number of decimal places available to use
     *
     * @param integer $maxDecPl The decimal places to set.
     * @return void
     */
    public static function setDefaultMaxDecPl(int $maxDecPl): void
    {
        static::$defaultMaxDecPl = $maxDecPl;
    }



    /**
     * Get various values stored in this object
     *
     * @param string $name The field to get.
     * @return mixed
     */
    public function __get(string $name)
    {
        switch ($name) {

            // return the maximum number of decimal places available to use
            case 'maxDecPl':
                return $this->maxDecPl;
        }

        // see if the parent can handle this
        return parent::__get($name);
    }





    /**
     * Format the current number in a readable way
     *
     * @param string|array|null $options The render options made up from RealNum constants (eg. RealNum::NO_THOUSANDS).
     * @param integer|null      $decPl   The number of decimal places to render to.
     * @return string
     */
    public function format($options = null, int $decPl = null): ?string
    {
        $options = Options::defaults($this->formatSettings)->resolve($options);

        // render nulls as 0 if desired
        $value = $this->getVal();
        if (((!is_string($value)) || (!mb_strlen($value)))
        && ($options['nullZero'])) {
            $value = '0';
        }

        if ((is_string($value)) && (mb_strlen($value))) {

            // allow locale to be specified by the caller
            // $locale = ($options['locale']
            //     ? $this->resolveLocaleCode($options['locale'])
            //     : $this->effectiveLocale());

            $locale = $this->resolveLocaleCode($options['locale']);
            $maxDecPl = $this->internalMaxDecPl();
            $type     = (static::$percentageMode ? NumberFormatter::PERCENT : NumberFormatter::DECIMAL);

            // if no decPl was explicitly specified then...
            if (is_null($decPl)) {

                // show decimal zeros if desired
                if ($options['trailZeros']) {
                    $decPl = $this->maxDecPl;

                // work out how many decimal places there actually are, because NumberFormatter seems to round
                // to 3 when ::FRACTION_DIGITS isn't set and there are > 3 decimal places
                } else {

                    // when checking how many decimal places to use for percentages, turn 0.1 into 10 (ie. 0.1 = 10%)
                    $checkAmount = (static::$percentageMode ? bcmul($value, '100', $maxDecPl) : $value);
                    $decPl = static::howManyDecimalPlaces($checkAmount);
                }
            }



            $numberFormatter = new NumberFormatter($locale, $type);
            $numberFormatter->setAttribute(NumberFormatter::FRACTION_DIGITS, $decPl);

            // remove the thousands separator if desired
            if (!$options['thousands']) {
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
                (bool) $options['accountingNeg'],
                (bool) $options['showPlus'],
                (bool) $options['nbsp'],
                $numberFormatter,
                $callback
            );

            return $return;
        }

        return ($options['nullString'] ? 'null' : null);
    }
}
