<?php

namespace CodeDistortion\RealNum;

use CodeDistortion\RealNum\Base;
use NumberFormatter;

/**
 * Arbitrary-precision floating-point numbers with localised rendering
 *
 * Represents floating-point numbers, performs calculations & comparisons on them, and renders them.
 * PHP's bcmath functions are used internally.
 * @property ?integer  $maxDecPl
  */
class RealNum extends Base
{
    /**
     * The default locale (at the class-level)
     *
     * Objects will pick this value up when instantiated.
     * @var integer|string
     */
    protected static $defaultLocale = 'en';

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
     * The default non-breaking-whitespace setting (at the class-level).
     *
     * Used when formatting a number.
     * Objects will pick this value up when instantiated.
     * @var boolean
     */
    protected static $defaultNoBreakWhitespace = false;





    /**
     * Callback used to resolve localeIdentifiers
     *
     * It may for example understand database ids, and map them back to their 'en-AU' equivalent.
     * When this hasn't been set, the locales are assumed to be strings like 'en-AU' and treated as is.
     * @var ?callable
     */
    protected static $localeResolver = null;





    /**
     * Affects formatting - don't show the decimal places when there is no decimal place value
     */
    // const NO_ZEROS = 1;

    /**
     * Affects formatting - show the trailing zeros at the end of the decimal numbers
     */
    const ALL_DEC_PL = 2;

    /**
     * Affects formatting - removes the thousands separator
     */
    const NO_THOUSANDS = 4;

    /**
     * Affects formatting - the plus is normally omitted (unlike a negative minus),
     * show the plus sign for positive values
     */
    const SHOW_PLUS = 8;

    /**
     * Affects formatting - show positive and negative values in accounting format
     * (ie. show negative numbers in brackets)
     */
    const ACCT_NEG = 16;

    /**
     * Affects formatting - will return 0 instead of null
     */
    const NULL_AS_ZERO = 32;

    /**
     * Affects formatting - should (the string) "null" be rendered for null values (otherwise actual null is returned)
     */
    const NULL_AS_STRING = 64;

    /**
     * Affects formatting - normally non-breaking spaces and other characters are returned as regular spaces. using this
     * will leave them as they were
     *
     */
    const NO_BREAK_WHITESPACE = 128;

    /**
     * Affects formatting - don't use the currency symbol
     */
    // const NO_SYMBOL = 256;





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
     * @param integer|null $options The render options made up from RealNum constants (eg. RealNum::NO_THOUSANDS).
     * @param integer|null $decPl   The number of decimal places to render to.
     * @return string
     */
    public function format(?int $options = 0, int $decPl = null): ?string
    {
        $value = $this->getVal();
        $options = (int) $options;

        // render nulls as 0 if desired
        if (((!is_string($value)) || (!mb_strlen($value)))
        && ((bool) ($options & static::NULL_AS_ZERO))) {
            $value = '0';
        }

        if ((is_string($value)) && (mb_strlen($value))) {

            $trailingDecimalZeros  = (bool) ($options & static::ALL_DEC_PL);
            $noThousands           = (bool) ($options & static::NO_THOUSANDS);
            $showPlus              = (bool) ($options & static::SHOW_PLUS);
            $accountingNegative    = (bool) ($options & static::ACCT_NEG);
            // otherwise fall back to the current non-breaking-whitespace setting
            $noBreakWhitespace = (($options & static::NO_BREAK_WHITESPACE)
                                        ? true
                                        : $this->effectiveNoBreakWhitespace());

            $locale   = $this->effectiveLocale();
            $maxDecPl = $this->internalMaxDecPl();
            $type     = (static::$percentageMode ? NumberFormatter::PERCENT : NumberFormatter::DECIMAL);

            // if no decPl was explicitly specified then...
            if (is_null($decPl)) {

                // show decimal zeros if desired
                if ($trailingDecimalZeros) {
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
            if ($noThousands) {
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
                $accountingNegative,
                $showPlus,
                $noBreakWhitespace,
                $numberFormatter,
                $callback
            );

            return $return;
        }

        $showNull = (bool) ($options & static::NULL_AS_STRING);
        return ($showNull ? 'null' : null);
    }
}
