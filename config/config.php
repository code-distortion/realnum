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
    | Non-breaking whitespace
    |--------------------------------------------------------------------------
    |
    | RealNum and Percent can either return strings when formatting with
    | regular whitespace (like regular space characters), or non-breaking
    | whitespace. TThis value determines what will be returned by default.
    |
    */

    'no_break_whitespace' => false,

];
