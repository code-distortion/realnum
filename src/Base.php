<?php

namespace CodeDistortion\RealNum;

use CodeDistortion\Options\Options;
use ErrorException;
use InvalidArgumentException;
use NumberFormatter;

/**
 * Arbitrary-precision floating-point numbers with localised rendering.
 * Represents floating-point numbers, performs calculations & comparisons on them, and renders them.
 *
 * This is the base class. RealNum (and CodeDistortion/Currency/Currency) extend from this.
 * Percent extends from RealNum.
 *
 * PHP's bcmath functions are used internally for the maths calculations.
 * PHP's NumberFormatter is used to format the readable output.
 * @property ?callable $localeResolver
 * @property integer   $maxDecPl
 * @property string    $locale
 * @property boolean   $immutable
 * @property array     $formatSettings
 * @property ?string   $val
 * @property ?float    $cast
 */
abstract class Base
{
    /**
     * The original default maxDecPl - used when resetting the class-level defaults
     */
    const ORIG_MAX_DEC_PL = 20;

    /**
     * The original default immuatble-setting - used when resetting the class-level defaults
     */
    const ORIG_IMMUTABLE = true;

    /**
     * The original default format-settings - used when resetting the class-level defaults
     */
    const ORIG_FORMAT_SETTINGS = null;



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
        'locale' => 'en',
    ];



    /**
     * The maximum number of decimal places available to use
     *
     * @var ?integer
     */
    protected $maxDecPl = null;

    /**
     * Whether this object should act in an immutable way or not
     *
     * @var ?boolean
     */
    protected $immutable = null;

    /**
     * This object's current format settings
     *
     * @var ?array
     */
    protected $formatSettings = null;



    /**
     * The value this object represents
     *
     * @var ?string
     */
    protected $value = null;





    /**
     * Callback used to resolve localeIdentifiers
     *
     * It may for example understand database ids, and map them back to their 'en-AU' equivalent.
     * When this hasn't been set, the locales are assumed to be strings like 'en-AU' and treated as is.
     * @var ?callable
     */
    protected static $localeResolver = null;





    /**
     * Constructor
     *
     * @param integer|float|string|self|null $value          The initial value to store.
     * @param boolean                        $throwException Should an exception be thrown if the $value is invalid?
     *                                                       (the value will be set to null upon error otherwise).
     */
    public function __construct($value = null, bool $throwException = true)
    {
        // copy the default settings into this object
        // (they may already have values from child class constructors)
        if (is_null($this->maxDecPl)) {
            $this->maxDecPl = static::$defaultMaxDecPl;
        }
        if (is_null($this->immutable)) {
            $this->immutable = static::$defaultImmutable;
        }
        if (is_null($this->formatSettings)) {
            $this->formatSettings = static::$defaultFormatSettings;
        }

        $this->init();

        // only store the value if it's acceptable
        // (by default this will throw an exception if it isn't)
        if ($this->ensureCompatibleArgs([$value], $throwException)) {

            // if the given value is another RealNum, use its decimal-places as well
            if ($value instanceof self) {
                $this->maxDecPl = $value->maxDecPl;
            }

            $this->setValue($value, $throwException);
        }
    }

    /**
     * Called upon instantiation - allows for child classes to perform some set-up
     *
     * @return void
     */
    protected function init(): void
    {
    }

    /**
     * return a clone of this object
     *
     * @return static
     */
    public function copy(): self
    {
        return $this->immute(true); // force immute
    }







    /**
     * Set the default values back to their original value
     *
     * This is used during unit tests as these default values are static properties
     * @return void
     */
    public static function resetDefaults(): void
    {
        static::$defaultMaxDecPl = static::ORIG_MAX_DEC_PL;
        static::$defaultImmutable = static::ORIG_IMMUTABLE;
        static::$defaultFormatSettings = static::ORIG_FORMAT_SETTINGS;

        static::$localeResolver = null;
    }

    /**
     * Retrieve the default locale
     *
     * @return integer|string
     */
    public static function getDefaultLocale()
    {
        return static::$defaultFormatSettings['locale'];
    }

    /**
     * Update the default locale
     *
     * @param integer|string $locale The locale to set.
     * @return void
     */
    public static function setDefaultLocale($locale): void
    {
        static::$defaultFormatSettings['locale'] = static::resolveLocaleCode($locale);
    }

    /**
     * Retrieve the default immutable-setting
     *
     * @return boolean
     */
    public static function getDefaultImmutability(): bool
    {
        return static::$defaultImmutable;
    }

    /**
     * Update the default immutable-setting
     *
     * @param boolean $immutable The immutable setting to set.
     * @return void
     */
    public static function setDefaultImmutability(bool $immutable): void
    {
        static::$defaultImmutable = $immutable;
    }

    /**
     * Retrieve the default format settings
     *
     * @return array
     */
    public static function getDefaultFormatSettings(): array
    {
        return static::$defaultFormatSettings;
    }

    /**
     * Update the default format settings
     *
     * @param string|array|null $formatSettings The immutable setting to set.
     * @return void
     */
    public static function setDefaultFormatSettings($formatSettings = null): void
    {
        static::$defaultFormatSettings = Options::defaults(static::$defaultFormatSettings)->resolve($formatSettings);
    }







    /**
     * Get various values stored in this object
     *
     * @param string $name The field to get.
     * @return mixed
     * @throws \ErrorException Thrown when accessing an invalid field.
     */
    public function __get(string $name)
    {
        switch ($name) {

            // return the locale
            case 'locale':
                return $this->effectiveLocale();

            // return the immutable-setting
            case 'immutable':
                return $this->effectiveImmutable();

            // return the formatSettings
            case 'formatSettings':
                return $this->formatSettings;



            // return the localeResolver
            case 'localeResolver':
                return static::$localeResolver;



            // return the value as a string (ie. full precision)
            case 'val':
                return $this->getVal();

            // return the value as either an int, float or null (whichever is most appropriate based on the value)
            // NOTE: this will be lossy when returning float values
            case 'cast':
                return $this->getValCast();
        }
        throw new ErrorException('Undefined property: '.static::class.'::$'.$name);
    }

    /**
     * Update various values stored in this object
     *
     * @param string $name  The name of the value to set.
     * @param mixed  $value The value to store.
     * @return void
     * @throws \ErrorException Thrown when accessing an invalid field.
     */
    public function __set(string $name, $value)
    {
        // this object may be immutable so don't allow it to be updated like this
        // switch ($name) {

        //     // set the locale this object uses
        //     case 'locale':
        //         $this->formatSettings['locale'] = static::resolveLocaleCode($value);
        //         return;

        //     // set the immutable-setting this object uses
        //     case 'immutable':
        //         $this->immutable = (!is_null($value) ? (bool) $value : null);
        //         return;

        //     // set the format-settings-setting this object uses
        //     case 'formatSettings':
        //         $this->formatSettings = Options::defaults(static::$defaultFormatSettings)->resolve($value);
        //         return;

        //     // set the localeResolver
        //     case 'localeResolver':
        //         static::$localeResolver = $value;
        //         return;



        //     // set the value this object represents
        //     case 'val':
        //     case 'str':
        //         $this->setValue($value);
        //         return;
        // }

        throw new ErrorException('Undefined property: '.static::class.'::$'.$name);
    }

    /**
     * Return the value as a string
     *
     * @return string
     */
    public function __toString(): string
    {
        return (string) $this->format();
    }







    /**
     * Update the localeResolver
     *
     * @param callable|null $localeResolver A closure used to resolve locale-identifiers.
     * @return void
     */
    public static function localeResolver(?callable $localeResolver): void
    {
        static::$localeResolver = $localeResolver;
    }

    /**
     * Return the localeResolver
     *
     * @return ?callable
     */
    public static function getLocaleResolver(): ?callable
    {
        return static::$localeResolver;
    }

    /**
     * Take the given $localeIdentifier and return the relevant locale-code
     *
     * @param integer|string|null $localeIdentifier Identifier of the locale to resolve.
     * @return string
     * @throws InvalidArgumentException Thrown when the $localeIdentifier can not be resolved.
     */
    protected static function resolveLocaleCode($localeIdentifier): string
    {
        if (!is_null($localeIdentifier)) {

            // via callback
            if (is_callable(static::$localeResolver)) {
                $locale = (static::$localeResolver)($localeIdentifier);
                if ((is_string($locale)) && (mb_strlen($locale))) {
                    return $locale;
                }
            }
        }

        if ((is_string($localeIdentifier)) && (mb_strlen($localeIdentifier))) {
            return $localeIdentifier;
        }

        throw new InvalidArgumentException('Locale code "'.$localeIdentifier.'" could not be resolved');
    }







    /**
     * Set the locale this object uses
     *
     * @param integer|string $locale The new locale to use.
     * @return static
     */
    public function locale($locale): self
    {
        $realNum = $this->immute();
        $realNum->formatSettings['locale'] = static::resolveLocaleCode($locale);
        return $realNum; // chainable - immutable
    }

    /**
     * Set the immutable-setting this object uses
     *
     * @param boolean $immutable The new immutable-setting to use.
     * @return static
     */
    public function immutable(bool $immutable): self
    {
        $realNum = $this->immute();
        $realNum->immutable = $immutable;
        return $realNum; // chainable - immutable
    }

    /**
     * This object's current format settings
     *
     * @param string|array|null $formatSettings The immutable setting to set.
     * @return static
     */
    public function formatSettings($formatSettings): self
    {
        $realNum = $this->immute();
        $realNum->formatSettings = Options::defaults(static::$defaultFormatSettings)->resolve($formatSettings);
        return $realNum; // chainable - immutable
    }

    /**
     * Set the value this object represents
     *
     * @param  integer|float|string|self|null $value          The value to set.
     * @param  boolean                        $throwException Throw an exception if the given value isn't valid?.
     * @return static
     */
    public function val($value = null, bool $throwException = true): self
    {
        return $this->immute()->setValue($value, $throwException); // chainable - immutable
    }

    /**
     * Set the value this object represents
     *
     * @param integer|float|string|self|null $value          The value to set.
     * @param boolean                        $throwException Throw an exception if the given value isn't valid?.
     * @return static
     */
    public function cast($value = null, bool $throwException = true): self
    {
        return $this->val($value, $throwException); // chainable - immutable
    }







    /**
    * Perform a round calculation on the value contained in this object.
    *
    * @param integer $decPl The decimal places to round to.
    * @return static
    */
    public function round(int $decPl = 0): self
    {
        $realNum = $this->immute();
        $realNum->value = static::roundCalculation(
            $realNum->value,
            $this->internalMaxDecPl($decPl),
            $this->internalMaxDecPl()
        );
        return $realNum;
    }

    /**
    * Perform a floor calculation on the value contained in this object
    *
    * @return static
    */
    public function floor(): self
    {
        $realNum = $this->immute();
        $realNum->value = static::floorCalculation(
            $realNum->value,
            $this->internalMaxDecPl(0),
            $this->internalMaxDecPl()
        );
        return $realNum;
    }

    /**
    * Perform a ceil calculation on the value contained in this object
    *
    * @return static
    */
    public function ceil(): self
    {
        $realNum = $this->immute();
        $realNum->value = static::ceilCalculation(
            $realNum->value,
            $this->internalMaxDecPl(0),
            $this->internalMaxDecPl()
        );
        return $realNum;
    }

    /**
    * Perform an add calculation on the value contained in this object
    *
    * @param self|integer|float|string|null ...$args The values to add.
    * @return static
    */
    public function add(...$args): self
    {
        $this->ensureCompatibleArgs($args);
        $realNum = $this->immute();
        static::runBcFunction($realNum, 'bcadd', $args);
        return $realNum;
    }

    /**
    * Perform a sum calculation on the value contained in this object
    *
    * @param self|integer|float|string|null ...$args The values to add.
    * @return static
    */
    // public function sum(...$args): self
    // {
    // }

    /**
    * Perform a div calculation on the value contained in this object
    *
    * @param self|integer|float|string|null ...$args The values to divide.
    * @return static
    */
    public function div(...$args): self
    {
        $this->ensureCompatibleArgs($args);
        $realNum = $this->immute();
        static::runBcFunction($realNum, 'bcdiv', $args);
        return $realNum;
    }

    /**
    * Perform a mod calculation on the value contained in this object
    *
    * @param self|integer|float|string|null ...$args The values to perform modulo with.
    * @return static
    */
    // public function mod(...$args): self
    // {
    // }

    /**
    * Perform a mul calculation on the value contained in this object
    *
    * @param self|integer|float|string|null ...$args The values to multiply.
    * @return static
    */
    public function mul(...$args): self
    {
        $this->ensureCompatibleArgs($args);
        $realNum = $this->immute();
        static::runBcFunction($realNum, 'bcmul', $args);
        return $realNum;
    }

    /**
    * Perform a pow calculation on the value contained in this object
    *
    * @param self|integer|float|string|null ...$args The values to pow.
    * @return static
    */
    // public function pow(...$args): self
    // {
    // }

    /**
    * Perform a sub calculation on the value contained in this object
    *
    * @param self|integer|float|string|null ...$args The values to subtract.
    * @return static
    */
    public function sub(...$args): self
    {
        $this->ensureCompatibleArgs($args);
        $realNum = $this->immute();
        static::runBcFunction($realNum, 'bcsub', $args);
        return $realNum;
    }

    /**
    * Perform an avg calculation on the value contained in this object
    *
    * @param self|integer|float|string|null ...$args The values to average.
    * @return static
    */
    // public function avg(...$args): self
    // {
    // }

    /**
    * Perform an increment calculation on the value contained in this object
    *
    * @param self|integer|float|string|null $value The amount to add.
    * @return static
    */
    public function inc($value = 1): self
    {
        return $this->add($value);
    }

    /**
    * Perform a decrement calculation on the value contained in this object
    *
    * @param self|integer|float|string|null $value The values to subtract.
    * @return static
    */
    public function dec($value = 1): self
    {
        return $this->sub($value);
    }







    /**
     * Check if the current value is < the given values
    *
    * @param mixed ...$comparisonValues The values to compare.
    * @return boolean
     */
    public function lt(...$comparisonValues): bool
    {
        $this->ensureCompatibleArgs($comparisonValues);
        return static::compare($this->value, $comparisonValues, [-1], $this->internalMaxDecPl());
    }

    /**
     * Check if the current value is < the given values
    *
    * @param mixed ...$comparisonValues The values to compare.
    * @return boolean
     */
    public function lessThan(...$comparisonValues): bool
    {
        $this->ensureCompatibleArgs($comparisonValues);
        return static::compare($this->value, $comparisonValues, [-1], $this->internalMaxDecPl());
    }

    /**
     * Check if the current value is <= the given values
    *
    * @param mixed ...$comparisonValues The values to compare.
    * @return boolean
     */
    public function lte(...$comparisonValues): bool
    {
        $this->ensureCompatibleArgs($comparisonValues);
        return static::compare($this->value, $comparisonValues, [-1, 0], $this->internalMaxDecPl());
    }

    /**
     * Check if the current value is <= the given values
    *
    * @param mixed ...$comparisonValues The values to compare.
    * @return boolean
     */
    public function lessThanOrEqualTo(...$comparisonValues): bool
    {
        $this->ensureCompatibleArgs($comparisonValues);
        return static::compare($this->value, $comparisonValues, [-1, 0], $this->internalMaxDecPl());
    }

    /**
     * Check if the current value is = the given values
    *
    * @param mixed ...$comparisonValues The values to compare.
    * @return boolean
     */
    public function eq(...$comparisonValues): bool
    {
        $this->ensureCompatibleArgs($comparisonValues);
        return static::compare($this->value, $comparisonValues, [0], $this->internalMaxDecPl());
    }

    /**
     * Check if the current value is = the given values
    *
    * @param mixed ...$comparisonValues The values to compare.
    * @return boolean
     */
    public function equalTo(...$comparisonValues): bool
    {
        $this->ensureCompatibleArgs($comparisonValues);
        return static::compare($this->value, $comparisonValues, [0], $this->internalMaxDecPl());
    }

    /**
     * Check if the current value is >= the given values
    *
    * @param mixed ...$comparisonValues The values to compare.
    * @return boolean
     */
    public function gte(...$comparisonValues): bool
    {
        $this->ensureCompatibleArgs($comparisonValues);
        return static::compare($this->value, $comparisonValues, [0, 1], $this->internalMaxDecPl());
    }

    /**
     * Check if the current value is >= the given values
    *
    * @param mixed ...$comparisonValues The values to compare.
    * @return boolean
     */
    public function greaterThanOrEqualTo(...$comparisonValues): bool
    {
        $this->ensureCompatibleArgs($comparisonValues);
        return static::compare($this->value, $comparisonValues, [0, 1], $this->internalMaxDecPl());
    }

    /**
     * Check if the current value is > the given values
    *
    * @param mixed ...$comparisonValues The values to compare.
    * @return boolean
     */
    public function gt(...$comparisonValues): bool
    {
        $this->ensureCompatibleArgs($comparisonValues);
        return static::compare($this->value, $comparisonValues, [1], $this->internalMaxDecPl());
    }

    /**
     * Check if the current value is > the given values
    *
    * @param mixed ...$comparisonValues The values to compare.
    * @return boolean
     */
    public function greaterThan(...$comparisonValues): bool
    {
        $this->ensureCompatibleArgs($comparisonValues);
        return static::compare($this->value, $comparisonValues, [1], $this->internalMaxDecPl());
    }

    /**
     * Check if the current value is between the given values
    *
    * @param integer|float|string|self|null $start          The start bound.
    * @param integer|float|string|self|null $end            The end bound.
    * @param boolean                        $inclusive      Whether the bounds are inclusive or not.
    * @param boolean                        $throwException Should an exception be thrown if $start/$end are invalid?.
    * @return boolean
     */
    public function between($start, $end, bool $inclusive = true, bool $throwException = true): bool
    {
        $this->ensureCompatibleArgs([$start, $end]);
        return static::betweenCalculation(
            $this->value,
            $start,
            $end,
            $inclusive,
            $this->internalMaxDecPl(),
            $throwException
        );
    }







    /**
     * Perform a rounding operation on the given $value
     *
     * This is to make up for the fact that a bcmath bcround function doesn't exist.
     * Based on https://stackoverflow.com/questions/1642614/how-to-ceil-floor-and-round-bcmath-numbers .
     * @param string|null $value    The number to round.
     * @param integer     $decPl    The number of decimal places to round to.
     * @param integer     $maxDecPl The bcmath decimal places to leave in the number afterwards.
     * @return string|null
     */
    protected static function roundCalculation(?string $value, ?int $decPl, int $maxDecPl): ?string
    {
        if ((is_string($value)) && (mb_strlen($value))) {
            if (mb_strpos($value, '.') !== false) {

                $decPl = (!is_null($decPl) ? min($decPl, $maxDecPl) : $maxDecPl);

                $delta = ($decPl <= -1
                    ? '5'.str_repeat('0', (-$decPl) - 1)
                    : '0.'.str_repeat('0', $decPl).'5');

                // if it's positive
                if ($value[0] != '-') {
                    $value = bcadd($value, $delta, $decPl);
                // or if it's negative
                } else {
                    $value = bcsub($value, $delta, $decPl);
                }
            }
            return static::ensureDecimalPlaces($value, $maxDecPl);
        }
        return null;
    }

    /**
     * Perform a floor operation on the given $value
     *
     * This is to make up for the fact that a bcmath bcfloor function doesn't exist
     * Based on https://stackoverflow.com/questions/1642614/how-to-ceil-floor-and-round-bcmath-numbers .
     * @param string|null $value    The number floor.
     * @param integer     $decPl    The number of decimal places to 'floor' to.
     * @param integer     $maxDecPl The bcmath decimal places to leave in the number afterwards.
     * @return string|null
     */
    protected static function floorCalculation(?string $value, ?int $decPl, int $maxDecPl): ?string
    {
        if ((is_string($value)) && (mb_strlen($value))) {
            if (mb_strpos($value, '.') !== false) {

                $decPl = (!is_null($decPl) ? min($decPl, $maxDecPl) : $maxDecPl);

                // if it contains a decimal point
                if (preg_match("~\.[0]+$~", $value)) {
                    return static::roundCalculation($value, $decPl, $maxDecPl);
                // if it's positive
                } elseif ($value[0] != '-') {
                    $value = bcadd($value, '0', $decPl);
                // or if it's negative
                } else {

                    $delta = ($decPl <= 0
                        ? '1'.str_repeat('0', -$decPl)
                        : '0.'.str_repeat('0', $decPl - 1).'1');

                    $value = bcsub($value, $delta, $decPl);
                }
            }
            return static::ensureDecimalPlaces($value, $maxDecPl);
        }
        return null;
    }

    /**
    * Perform a ceil operation on the given $value
    * This is to make up for the fact that a bcmath bcceil function doesn't exist
     * Based on https://stackoverflow.com/questions/1642614/how-to-ceil-floor-and-round-bcmath-numbers .
     * @param string|null $value    The number ceil.
     * @param integer     $decPl    The number of decimal places to 'ceil' to.
     * @param integer     $maxDecPl The bcmath decimal places to leave in the number afterwards.
     * @return string|null
    */
    protected static function ceilCalculation(?string $value, ?int $decPl, int $maxDecPl): ?string
    {
        if ((is_string($value)) && (mb_strlen($value))) {
            if (mb_strpos($value, '.') !== false) {

                $decPl = (!is_null($decPl) ? min($decPl, $maxDecPl) : $maxDecPl);

                // if it contains a decimal point
                if (preg_match("~\.[0]+$~", $value)) {
                    return static::roundCalculation($value, $decPl, $maxDecPl);
                // if it's positive
                } elseif ($value[0] != '-') {

                    $delta = ($decPl <= 0
                        ? '1'.str_repeat('0', -$decPl)
                        : '0.'.str_repeat('0', $decPl - 1).'1');

                    $value = bcadd($value, $delta, $decPl);
                // or if it's negative
                } else {
                    $value = bcsub($value, '0', $decPl);
                }
            }
            return static::ensureDecimalPlaces($value, $maxDecPl);
        }
        return null;
    }

    /**
     * Run the given bc-function on the given values - updating the $realNum with the result of each step
     *
     * @param Base   $realNum    The initial realnum to perform the calculations on.
     * @param string $bcFunction The bcmath function to run.
     * @param array  $args       The values to preform the calculations with.
     * @return static
     */
    private static function runBcFunction(
        Base $realNum,
        string $bcFunction,
        array $args
    ): self {

        // allow these bcmath functions to work this way (they accept two values, and a 3rd $scale (decimal places)
        // parameter)
        // if (in_array($bcFunction, ['bcadd', 'bcdiv', 'bcmod', 'bcmul', 'bcpow', 'bcsub', ])) {
        if (in_array($bcFunction, ['bcadd', 'bcdiv',          'bcmul', 'bcpow', 'bcsub', ])) {

            // apply the calculation to each given
            foreach ($args as $value) {

                // if a RealNum was given, turn it into a string (or null)
                if ($value instanceof self) {
                    $value = $value->getVal();
                }

                if ((!is_null($realNum->value)) || (!is_null($value))) {

                    $maxDecPl = (int) $realNum->maxDecPl;

                    // don't adjust the given operand, leave as is
                    $value = $realNum->extractBasicValue($value, $maxDecPl);
                    // perform the calculation
                    $newValue = (is_callable($bcFunction) // to please phpstan
                        ? call_user_func_array($bcFunction, [$realNum->value, $value, $maxDecPl])
                        : null
                    );
                    // round the result
                    $realNum->value = (!is_null($newValue)
                        ? (string) $realNum->roundCalculation($newValue, $maxDecPl, $maxDecPl)
                        : null
                    );
                }
            }
        }
        return $realNum;
    }

    /**
     * Compare the given $comparisonValues to the $value, and see if all of them pass the comparison rule
     * (govened by $allowedComparisons)
     *
     * @param string|null $value              The source value to compare.
     * @param array       $comparisonValues   The values to compare to $value.
     * @param array       $allowedComparisons An array representing lt/eq/gt [-1, 0, 1].
     * @param integer     $maxDecPl           The max bcmath decimal-places to use.
     * @return boolean
     * @throws InvalidArgumentException Thrown when no comparison values were passed.
     */
    private static function compare(
        $value,
        array $comparisonValues,
        array $allowedComparisons,
        int $maxDecPl
    ): bool {

        if (count($comparisonValues)) {
            foreach ($comparisonValues as $comparisonValue) {

                // if a RealNum was given, turn it into a string (or null)
                if ($comparisonValue instanceof self) {
                    $comparisonValue = $comparisonValue->getVal();
                }

                if ((!is_null($value)) || (!is_null($comparisonValue))) {
                    $comparison = (bccomp((string) $value, $comparisonValue, $maxDecPl));

                    if (!in_array($comparison, $allowedComparisons)) {
                        return false;
                    }
                }
            }
            return true;
        }
        throw new InvalidArgumentException('No comparison values were passed');
    }

    /**
     * See if stored value is between $start and $end (it doesn't matter the order of $start and $end)
     *
     * if $start or $end is null, they aren't used,
     * if both are null, no comparison is made (true will be returned)
     * if $value is null, false will be returned
     *
     * @param string|null                    $value          The source value to compare.
     * @param integer|float|string|self|null $start          The start bound.
     * @param integer|float|string|self|null $end            The end bound.
     * @param boolean                        $inclusive      Whether the bounds are inclusive or not.
     * @param integer                        $decPl          The bcmath decimal-places to use.
     * @param boolean                        $throwException Should an exception be thrown if $start/$end are invalid?.
     * @return boolean
     */
    private static function betweenCalculation(
        $value,
        $start,
        $end,
        bool $inclusive,
        int $decPl,
        bool$throwException = true
    ): bool {

        if (!is_null($value)) {

            $start = static::extractBasicValue($start, $decPl, true, $throwException);
            $end = static::extractBasicValue($end, $decPl, true, $throwException);
            $min = $max = null;

            // between $start and $end (doesn't matter the order)
            if ((!is_null($start)) && (!is_null($end))) {

                // swap start and end if needed
                switch (bccomp($start, $end, $decPl)) {
                    case -1:
                        $min = $start;
                        $max = $end;
                        break;
                    case 1:
                        $min = $end;
                        $max = $start;
                        break;
                    default: // case 0:
                        $min = $max = $start;
                }
            } elseif (!is_null($start)) { // > or >= $start
                $min = $start;
            } elseif (!is_null($end)) { // <= or < $end
                $max = $end;
            }

            // compare $value to the bounds
            if ((!is_null($min))
            && (!in_array(bccomp($value, $min, $decPl), ($inclusive ? [0, 1] : [1])))) {
                return false;
            }
            if ((!is_null($max))
            && (!in_array(bccomp($value, $max, $decPl), ($inclusive ? [-1, 0] : [-1])))) {
                return false;
            }

            return true;
        }
        return false;
    }

    /**
     * Internal method used by render(..) to perform part of the rendinging
     *
     * @param string          $value              The value to render.
     * @param integer         $maxDecPl           The decimal places to use when checking the value.
     * @param string          $locale             The locale to use when rendering the number.
     * @param boolean         $accountingNegative Render negatives with brackets.
     * @param boolean         $showPlus           Show a '+' character for positive values.
     * @param boolean         $breaking           Replace non-breaking spaces etc with regular spaces?.
     * @param NumberFormatter $numberFormatter    The object used to render the number.
     * @param callable        $renderNumber       A callback to render a value.
     * @return string
     */
    protected function renderNumber(
        string $value,
        int $maxDecPl,
        string $locale,
        bool $accountingNegative,
        bool $showPlus,
        bool $breaking,
        NumberFormatter $numberFormatter,
        callable $renderNumber
    ): string {

        // render accounting formatted negatives
        $return = '';
        if (($accountingNegative) && (bccomp($value, '0', $maxDecPl) < 0)) {

            $absoluteAmount = bcmul($value, '-1', $maxDecPl);
            $return = '('.$renderNumber($absoluteAmount).')';

        // or continue for positive and normal (non-accounting) negatives
        } else {

            // show the plus '+' for positive numbers
            if (($showPlus) && (bccomp($value, '0', $maxDecPl) >= 0)) {

                // render the number as a negative, and then replace the 'minus' symbol with the 'plus' symbol
                $plusSymbol = $numberFormatter->getSymbol(NumberFormatter::PLUS_SIGN_SYMBOL);
                $minusSymbol = $numberFormatter->getSymbol(NumberFormatter::MINUS_SIGN_SYMBOL);

                // first render as a negative number
                $negativeAmount = bcmul($value, '-1', $maxDecPl);
                $renderedNegative = $renderNumber($negativeAmount);

                // and derive the positive version from that
                $renderedPositive = str_replace($minusSymbol, $plusSymbol, $renderedNegative);

                // use the new positive number string
                if ($renderedPositive != $renderedNegative) {
                    $return = $renderedPositive;
                // if for some reason the positive version couldn't be determined from the negative number,
                // just add a plus in front
                } else {
                    $return = $plusSymbol.$renderNumber($value);
                }
            // render as normal
            } else {
                $return = $renderNumber($value);
            }
        }

        // replace non-breaking whitespace if desired
        if ($breaking) {
            // replace NON-BREAKING SPACE and NARROW NO-BREAK SPACE with regular spaces
            $return = str_replace(["\xc2\xa0", "\xe2\x80\xaf"], ' ', $return);
        }

        return $return;
    }

    /**
     * Format the current number in a readable way
     *
     * @param string|array|null $options The options to use when rendering the number.
     * @return string
     */
    abstract public function format($options = null): ?string;







    /**
     * Returns an immutable version of this object (if enabled)
     *
     * @param boolean $force Will allways immute when true.
     * @return static
     */
    protected function immute(bool $force = false): Base
    {
        return (($force) || ($this->effectiveImmutable()) ? clone $this : $this);
    }

    /**
     * Retrieve the str value
     *
     * @return string|null
     */
    protected function getVal(): ?string
    {
        // make sure it's got the correct number of decimal places
        return (!is_null($this->value)
            ? bcadd($this->value, '0', $this->internalMaxDecPl())
            : null);
    }

    /**
     * Retrieve the val value
     *
     * @return integer|float|null
     */
    protected function getValCast()
    {
        if (is_null($this->value)) {
            return null;
        }
        if (mb_strpos($this->value, '.') !== false) {
            $temp = rtrim($this->value, '0');
            return ((mb_substr($temp, -1) == '.') ? (int) $this->value : (float) $this->value);
        }
        return (int) $this->value;
    }

    /**
     * Store the given value
     *
     * This will round the value to match the current number of decimal-places
     * @param integer|float|string|self|null $value          The value to store.
     * @param boolean                        $throwException Throw an exception if the given value isn't valid?.
     * @return static
     */
    protected function setValue($value, bool $throwException = true): self
    {
        $decPl = $maxDecPl = $this->internalMaxDecPl();
        $value = static::extractBasicValue($value, $decPl, true, $throwException);
        $this->value = static::roundCalculation($value, $decPl, $maxDecPl);
        return $this; // chainable - NOT immutable
    }

    /**
     * Update the decimal-places to round to
     *
     * This will round the current value (if needed) to match.
     * @param integer $decPl The new decPl to use.
     * @return static
     */
    protected function setDecPl(int $decPl): self
    {
        $oldMaxDecPl = $this->maxDecPl;
        $this->maxDecPl = $decPl;

        // re-calculate the stored number if necessary
        if ($this->maxDecPl < $oldMaxDecPl) {
            $decPl = $maxDecPl = $this->internalMaxDecPl();
            $this->value = static::roundCalculation($this->value, $decPl, $maxDecPl);
        }
        return $this; // chainable - NOT immutable
    }



    /**
     * Check the given value to see that it can be worked with, and return the value (int, float, string, null) to be
     * used
     *
     * Return it as a string for bcmath (or null)
     * @param integer|float|string|self|null $value           The value to check.
     * @param integer                        $decPl           The bcmath decimal places to use.
     * @param boolean                        $allowNullString When true a 'null' string will be picked up as null.
     * @param boolean                        $throwException  Should an exception be thrown if the $value is invalid?.
     * @return string|null
     * @throws \InvalidArgumentException Thrown when $value is invalid.
     */
    protected static function extractBasicValue(
        $value,
        int $decPl,
        bool $allowNullString = true,
        bool $throwException = true
    ): ?string {

        // just regular null
        if (is_null($value)) {
            return null;
        }

        // another Base object
        if ($value instanceof self) {
            $value = $value->getVal();
        }

        // an int/float/numeric-string
        if (is_numeric($value)) {
            return (string) $value; // turn it into a string
        }

        // pick up a 'null' string as null
        if (($allowNullString) && (is_string($value)) && (mb_strtolower($value) == 'null')) {
            return null;
        }

        // otherwise fail
        if ($throwException) {
            throw new InvalidArgumentException('The given value \''.$value.'\' is not numeric');
        }

        return null;
    }

    /**
     * Ensure that the given string number has the desired number of decimal places in it
     *
     * @param string  $value The number to adjust.
     * @param integer $decPl The number of decimal places to ensure exist.
     * @return string|null
     */
    private static function ensureDecimalPlaces(?string $value, int $decPl): ?string
    {
        if ((is_string($value)) && (mb_strlen($value))) {
            return bcadd($value, '0', $decPl);
        }
        return null;
    }

    /**
     * Detect how many decimal places the given string has
     *
     * @param string $value The value to inspect.
     * @return integer
     */
    protected function howManyDecimalPlaces(string $value): int
    {
        $decimals = (string) strrchr($value, '.');
        $decimals = rtrim($decimals, '0');
        return max(0, mb_strlen($decimals) - 1);
    }

    /**
     * Check if the passed arguments are compatible for operations on this object
     *
     * @param array   $args           The arguments to check against this.
     * @param boolean $throwException Should an exception be raised if the given value isn't valid?.
     * @return boolean
     */
    protected function ensureCompatibleArgs(array $args, bool $throwException = true): bool
    {
        $argsOk = true;
        foreach ($args as $arg) {
            $argsOk &= $this->ensureCompatibleValue($arg, $throwException);
        }
        return (bool) $argsOk;
    }

    /**
     * Check if the passed value is compatible for operations on this object
     *
     * (This may be overridden by child classes)
     * @param mixed   $value          The value to check against the value stored in this object.
     * @param boolean $throwException Should an exception be raised if the given value isn't valid?.
     * @return boolean
     * @throws InvalidArgumentException Thrown when the given value is invalid (and $throwException is true).
     */
    protected function ensureCompatibleValue($value, bool $throwException = true): bool
    {
        // objects
        $exceptionMsg = null;
        if (is_object($value)) {
            // this object is compatible with other objects of the same type
            if (!$value instanceof static) {
                $exceptionMsg = 'Object of type '.get_class($value).' is not compatible '
                                .'for operations with '.static::class;
            }
        // strings
        } elseif (is_string($value)) {
            if ((mb_strtolower($value) != 'null') && (!is_numeric($value))) {
                $exceptionMsg = 'String value "'.$value.'" is not numeric';
            }
        // int / float / null
        } elseif ((!is_int($value)) && (!is_float($value)) && (!is_null($value))) {
            $exceptionMsg = 'The given value "'.$value.'" is not valid';
        }

        // check if an error was found
        if (is_string($exceptionMsg)) {

            // throw an exception if necessary
            if ($throwException) {
                throw new InvalidArgumentException($exceptionMsg);
            }
            return false;
        }
        return true;
    }



    /**
     * Use the given locale, but use the current one if needed
     *
     * @param integer|string|null $localeIdentifier The locale to force (otherwise the current one is used).
     * @return string
     */
    protected function effectiveLocale($localeIdentifier = null): string
    {
        if (!is_null($localeIdentifier)) {
            $locale = static::resolveLocaleCode($localeIdentifier);
            if (mb_strlen($locale)) {
                return $locale;
            }
        }
        return (string) $this->formatSettings['locale'];
    }

    /**
     * Use the given maxDecPl, but use the current one if needed
     *
     * Adjusts for percentage mode
     * @param integer $maxDecPl The decimal places to use (otherwise the current one is used).
     * @return integer
     */
    protected function internalMaxDecPl(int $maxDecPl = null): int
    {
        $maxDecPl = (!is_null($maxDecPl) ? $maxDecPl : $this->maxDecPl);
        return (int) $maxDecPl;
    }

    /**
     * Use the given immutable-setting, but use the current one if needed
     *
     * @param boolean|null $immutable The $immutable to force (otherwise the current one is used).
     * @return boolean
     */
    protected function effectiveImmutable(bool $immutable = null): bool
    {
        return (!is_null($immutable) ? $immutable : (bool) $this->immutable);
    }
}
