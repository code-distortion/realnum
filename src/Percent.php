<?php

namespace CodeDistortion\RealNum;

/**
 * Arbitrary-precision percentages with localised rendering.
 *
 * Represents percentage numbers, performs calculations & comparisons on them, and renders them.
 */
class Percent extends RealNum
{
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
