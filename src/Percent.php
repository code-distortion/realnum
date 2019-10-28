<?php

namespace CodeDistortion\RealNum;

/**
 * Manage percentage numbers with accuracy, renderable in different locales
 *
 * Represent floating-point numbers, allow calculations & comparisons to be performed on them, and render them.
 * The bcmath functions are used internally.
 */
class Percent extends RealNum
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
     * An internal setting - This will add an extra 2 decPl internally when rounding, and will cause it to be rendered
     * as a percentage value
     *
     * This is because percent values are actually between 0 & 1, so a value of 0.12345 should be output as  12.345%.
     * @var boolean
     */
    protected static $percentageMode = true;
}
