<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Decimal places
    |--------------------------------------------------------------------------
    |
    | This value sets the maximum number of decimal places used by RealNum
    | and Percent by default. This is their precision.
    |
    */

    'max_dec_pl' => 20,

    /*
    |--------------------------------------------------------------------------
    | Immutability
    |--------------------------------------------------------------------------
    |
    | This value determines whether RealNum and Percent are immutable by
    | default or not.
    |
    */

    'immutable' => true,

    /*
    |--------------------------------------------------------------------------
    | Format settings
    |--------------------------------------------------------------------------
    |
    | RealNum and Percent will use these default settings when format() is
    | called. You can adjust these by adding values to the string below.
    | You may choose from the possible values below.
    |
    */

    'format_settings' => null, // 'thousands !showPlus !accountingNeg !nullString !nullZero !trailZeros nbsp',

];
