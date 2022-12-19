# RealNum

[![Latest Version on Packagist](https://img.shields.io/packagist/v/code-distortion/realnum.svg?style=flat-square)](https://packagist.org/packages/code-distortion/realnum)
![PHP Version](https://img.shields.io/badge/PHP-7.1%20to%208.2-blue?style=flat-square)
![Laravel](https://img.shields.io/badge/laravel-5%2C%206%2C%207%2C%208%20%26%209-blue?style=flat-square)
[![GitHub Workflow Status](https://img.shields.io/github/actions/workflow/status/code-distortion/realnum/run-tests.yml?branch=master&style=flat-square)](https://github.com/code-distortion/realnum/actions)
[![Buy The World a Tree](https://img.shields.io/badge/treeware-%F0%9F%8C%B3-lightgreen?style=flat-square)](https://plant.treeware.earth/code-distortion/realnum)
[![Contributor Covenant](https://img.shields.io/badge/contributor%20covenant-v2.0%20adopted-ff69b4.svg?style=flat-square)](CODE_OF_CONDUCT.md)

***code-distortion/realnum*** is a PHP library for arbitrary-precision floating-point maths with locale-aware formatting. It integrates with Laravel 5 - 9 but works stand-alone as well.


| locale| RealNum | Percent |
| :----: | :----: | :----: |
| en-US | 1,234,567.89 | 100.98% |
| de-DE | 1.234.567,89 | 100,98 % |
| sv-SE | 1 234 567,89 | 100,98 % |
| hi-IN | 12,34,567.89 | 100.98% |
| ar-EG | ١٬٢٣٤٬٥٦٧٫٨٩ | ١٠٠٫٩٨٪؜ |

Here is an example of why you might want arbitrary precision calculations:

``` php
// an example of floating-point inaccuracy
var_dump(0.1 + 0.2 == 0.3); // bool(false)
// for more details see:
// The Floating-Point Guide - https://floating-point-gui.de/
```

The ***Percent*** class is also available alongside the ***RealNum*** class to perform all of the same actions as RealNum but for percentage values. See the [percentage values](#percentage-values) section below for more details.

If you would like to work with *currency* values, please consider the [code-distortion/currency](https://github.com/code-distortion/currency) package.



## Installation

Install the package via composer:

``` bash
composer require code-distortion/realnum
```



## Usage

Instantiate a RealNum object and you can start performing calculations with it, perform comparisons, and render it as a readable string:

``` php
use CodeDistortion\RealNum\RealNum;

$num1 = new RealNum(5555.55);  // normal instantiation
$num1 = RealNum::new(5555.55); // static instantiation which is more readable when chaining

$num2 = $num1->add(4444.44); // (it's immutable so a new object is created)
$num2->between(8000, 10000); // true
print $num2->format();       // "9,999.99"
```



### Setting values

You may set the value explicitly:

``` php
$num1 = RealNum::new(5); // the value is set to 5 upon instantiation
$num2 = $num1->val(10);  // and is then set to 10 (it's immutable so a new object is created)
```

The types of values you can pass to RealNum are:

``` php
$num1 = RealNum::new(5);       // an integer
$num2 = RealNum::new(5.5);     // a float
$num3 = RealNum::new('6.789'); // a numeric string
$num4 = RealNum::new($num3);   // another RealNum object
$num5 = RealNum::new(null);    // null
$num6 = RealNum::new();        // (will default to null)
```

***TIP:*** To maintain precision when passing values, pass them as strings instead of floating-point numbers:

``` php
RealNum::new(0.12345678901234567890);   // "0.12345678901235" (precision lost because the number passed is a PHP float)
RealNum::new('0.12345678901234567890'); // "0.12345678901234567890" (passed as a string)
```

You may also set other settings that RealNum uses:

``` php
RealNum::new()->locale('en-US');              // sets the locale this object uses (see the 'locale' section below)
RealNum::new()->maxDecPl(30);                 // sets the maximum number of decimal places used (see the 'precision (maximum decimal places)' section below)
RealNum::new()->immutable(false);             // sets whether this object is immutable or not (see the 'immutability' section below)
RealNum::new()->formatSettings('!thousands'); // alters the default options used when format() is called (see the 'formatting output' section below)
```



### Retrieving values

To retrieve the value contained in a RealNum you may read the `val` and `cast` properties. The `val` property maintains precision and in contrast, `cast` will loose some precision so use them depending on your needs:

``` php
$num = RealNum::new('0.12345678901234567890');
print $num->val;  // "0.12345678901234567890" (returned as a string, or null)
print $num->cast; // 0.12345678901235 (cast to either an integer, float or null - this is less accurate)
```

You may also read other settings that RealNum uses:

``` php
$num = RealNum::new();
print $num->locale;             // "en"
print $num->maxDecPl;           // 20 (the maximum number of decimal places used)
print $num->immutable;          // true
var_dump($num->formatSettings); // ['null' => null, 'trailZeros' => null … ]
```

***Note:*** See the [formatting output](#formatting-output) section below for more details about how to render the value as a readable string.



### Calculations

The calculations available are:

``` php
$num = RealNum::new(5);
$num = $num->inc();    // increment
$num = $num->dec();    // decrement
$num = $num->add(2);   // add x
$num = $num->sub(2);   // subtract x
$num = $num->div(2);   // divide by x
$num = $num->mul(2);   // multiply by x
$num = $num->round();  // round to zero decimal places
$num = $num->round(2); // round to x decimal places
$num = $num->floor();  // use the floor of the current value
$num = $num->ceil();   // use the ceiling of the current value
```

The `add()`, `sub()`, `div()` and `mul()` methods accept multiple values:

``` php
RealNum::new(5)->add(4, 3, 2, 1); // 15
RealNum::new(5)->sub(4, 3, 2, 1); // -5
RealNum::new(5)->div(4, 3, 2, 1); // 0.2083333…
RealNum::new(5)->mul(4, 3, 2, 1); // 120
```

*Integer*, *float*, *numeric string* and *null* values, as well as other *RealNum* objects may be passed:

``` php
$num1 = RealNum::new(5);
$num1 = $num1->add(2);       // pass an integer
$num1 = $num1->add(2.0);     // pass a float
$num1 = $num1->add('2.345'); // pass a numeric string
$num1 = $num1->add(null);    // pass null (adds nothing)

$num2 = RealNum::new(2);
$num1 = $num1->add($num2);   // pass another RealNum object
```



### Comparisons

You can compare numbers to other values with bound checking:
``` php
RealNum::new(5)->lessThan(10);             // alias of lt(..)
RealNum::new(5)->lessThanOrEqualTo(10);    // alias of lte(..)
RealNum::new(5)->equalTo(10);              // alias of eq(..)
RealNum::new(5)->greaterThanOrEqualTo(10); // alias of gte(..)
RealNum::new(5)->greaterThan(10);          // alias of gt(..)

$num1 = RealNum::new(5);
$num2 = RealNum::new(10);
$num1->lt($num2); // you can compare a RealNum with others
```

You may pass multiple values to these comparison methods. eg.

``` php
RealNum::new(5)->lt(10, 15, 20); // will return true if 5 is less-than 10, 15 and 20
```

You can check if a RealNum's value is between given bounds:

``` php
RealNum::new(5)->between(2, 8);        // check if 5 is between x and y (inclusively)
RealNum::new(5)->between(2, 8, false); // check if 5 is between x and y (NOT inclusively)
```

And you can check if the value is null:

``` php
RealNum::new(5)->isNull();
```



### Formatting output

Use the `format()` method to generate a readable-string version of the current value:

``` php
$num = RealNum::new(1234567.89);
print $num->format(); // "1,234,567.89"
```

You may alter the way `format()` renders the output by passing options. The options you can alter are:

`null=x`, `trailZeros`, `decPl=x`, `thousands`, `showPlus`, `accountingNeg`, `locale=x` and `breaking`.

Boolean options (those without an equals sign) can be negated by adding `!` before it.

***Note:*** `format()` options are processed using the [code-distortion/options](https://github.com/code-distortion/options) package so they may be passed as expressive strings or associative arrays.

``` php
print RealNum::new(null)->format('null=null');   // null (actual null - default)
print RealNum::new(null)->format('null="null"'); // "null" (returned as a string)
print RealNum::new(null)->format('null=0');      // "0"

print RealNum::new(1.23)->maxDecPl(5)->format('!trailZeros'); // "1.23" (cuts off trailing decimal 0's - default)
print RealNum::new(1.23)->maxDecPl(5)->format('trailZeros');  // "1.23000" (shows the maximum available decimal-places)

// the number can be rounded and shown to a specific number of decimal places (this is different to the internal maxDecPl setting)
print RealNum::new(1.9876)->format('decPl=null'); // "1.9876" (no rounding - default)
print RealNum::new(1.9876)->format('decPl=0');    // "2" (rounded and shown to 0 decimal places)
print RealNum::new(1.9876)->format('decPl=1');    // "2.0" (rounded and shown to 1 decimal place)
print RealNum::new(1.9876)->format('decPl=2');    // "1.99" (rounded and shown to 2 decimal places)
print RealNum::new(1.9876)->format('decPl=6');    // "1.987600" (rounded and shown to 6 decimal places)
// the extra trailing zeros can be removed again with !trailZeros
print RealNum::new(1.9876)->format('decPl=6 !trailZeros');  // "1.9876" (rounded to 6 decimal places with the trailing zeros removed)

print RealNum::new(1234567.89)->format('thousands');  // "1,234,567.89" (default)
print RealNum::new(1234567.89)->format('!thousands'); // "1234567.89" (removes the thousands separator)

print RealNum::new(1234)->format('showPlus');  // "+1,234" (adds a '+' for positive values)
print RealNum::new(1234)->format('!showPlus'); // "1,234" (default)

print RealNum::new(-1234)->format('accountingNeg');  // "(1,234)" (accounting negative - uses brackets for negative numbers)
print RealNum::new(-1234)->format('!accountingNeg'); // "-1,234" (default)

// the locale can be chosen at the time of formatting - see the 'local' section below for more details
print RealNum::new(1234567.89)->format('locale=en');    // "1,234,567.89" (English - default)
print RealNum::new(1234567.89)->format('locale=en-AU'); // "1,234,567.89" (Australian English)
print RealNum::new(1234567.89)->format('locale=en-IN'); // "12,34,567.89" (Indian English)
print RealNum::new(1234567.89)->format('locale=de');    // "1.234.567,89" (German)
print RealNum::new(1234567.89)->format('locale=sv');    // "1 234 567,89" (Swedish)
print RealNum::new(1234567.89)->format('locale=ar');    // "١٬٢٣٤٬٥٦٧٫٨٩" (Arabic)

// non-breaking spaces can be returned instead of regular spaces - see the 'non-breaking whitespace' section below for more details
print htmlentities(RealNum::new(1234567.89)->format('locale=sv-SE !breaking')); // "1&nbsp;234&nbsp;567,89" (default)
print htmlentities(RealNum::new(1234567.89)->format('locale=sv-SE breaking'));  // "1 234 567,89" (regular spaces)
```

Multiple settings can be used together:

``` php
print RealNum::new(1234567.89)->format('!thousands showPlus locale=de-DE'); // "+1234567,89"
```

Casting a RealNum to a string is equivalent to calling `format()` with no arguments:

``` php
print (string) RealNum::new(1234567.89); // "1,234,567.89"
```

***NOTE***: RealNum uses PHP's NumberFormatter to render the readable output, which currently has a limitation of being able to only show about 17 digits (including before the decimal place). So `format()`'s output will act a bit strangely if there are too many digits. The number stored inside will maintain its full accuracy, however. You may access the full number by reading the `val` property (see the [retrieving values](#retrieving-values) section above).



### Default format settings

RealNum uses these default settings when `format()` is called: `"null=null !trailZeros decPl=null thousands !showPlus !accountingNeg locale=en !breaking"`

***Note:*** When using Laravel you may change this in the package config file. See the [Laravel](#laravel) section below.

***Note:*** `format()` options are processed using the [code-distortion/options](https://github.com/code-distortion/options) package so they may be passed as expressive strings or associative arrays.

These can be adjusted per-object:

``` php
$num1 = RealNum::new(1234567.89)->formatSettings('!thousands showPlus');
print $num1->format(); // "+1234567.89" (no thousands separator, show-plus)
```

The default format-settings can be adjusted. All ***new*** RealNum objects will then start with this setting:

``` php
var_dump(RealNum::getDefaultFormatSettings()); // ['null' => null, 'trailZeros' => false … ] (default)
RealNum::setDefaultFormatSettings('null="NULL" trailZeros');
var_dump(RealNum::getDefaultFormatSettings()); // ['null' => 'NULL', 'trailZeros' => true … ]
```



### Locale

***Note:*** When using Laravel this will be set automatically. See the [Laravel](#laravel) section below.

RealNum's default locale is "en" (English) but you can choose which one to use.

You may choose the locale at the time of formatting:

``` php
print RealNum::new(1234567.89)->format('locale=fr-FR'); // "1 234 567,89"
```

You may change the locale per-object:

``` php
$num1 = RealNum::new(1234567.89)->locale('fr-FR'); // (it's immutable so a new object is created)
print $num1->locale;   // "fr-FR"
print $num1->format(); // "1 234 567,89"
```

The default locale may be changed. All ***new*** RealNum objects will then start with this setting:

``` php
RealNum::setDefaultLocale('fr-FR');
print RealNum::getDefaultLocale(); // "fr-FR"
```



### Precision (maximum decimal places)

***Note:*** When using Laravel you may change this in the package config file. See the [Laravel](#laravel) section below.

The `maxDecPl` precision setting is the maximum number of decimal places you would like RealNum to handle. A maxDecPl of 20 is used by default but you may change this per-object:

``` php
$num = RealNum::new('0.123456789012345678901234567890'); // passed as a string to maintain precision
print $num->val; // "0.12345678901234567890" ie. rounded to the default 20 decimal places
$num = RealNum::new()->maxDecPl(30)->val('0.123456789012345678901234567890');
print $num->val; // "0.123456789012345678901234567890" the full 30 decimal places
```

The default precision may be changed. All ***new*** RealNum objects will then start with this setting:

``` php
RealNum::setDefaultMaxDecPl(30);
print RealNum::getDefaultMaxDecPl(); // 30
```



### Immutability

***Note:*** When using Laravel you may change this in the package config file. See the [Laravel](#laravel) section below.

RealNum is immutable by default which means that once an object is created it won't change. Anything that changes its contents will return a new RealNum instead. This way you can pass a RealNum object to other parts of your code and be sure that it won't be changed unexpectedly:

``` php
$num1 = RealNum::new(1);
$num2 = $num1->add(2); // $num1 remains unchanged and $num2 is a new object containing the new value
print $num1->format(); // "1"
print $num2->format(); // "3"
```

Immutability may be turned off per-object:

``` php
$num1 = RealNum::new(1)->immutable(false);
$num2 = $num1->add(2); // $num1 is changed and $num2 points to the same object
print $num1->format(); // "3"
print $num2->format(); // "3"
```

Immutability may be turned off by default. All ***new*** RealNum objects will then start with this setting:

``` php
RealNum::setDefaultImmutability(false);
var_dump(RealNum::getDefaultImmutability()); // "bool(false)"
```

You can explicitly make a clone of a RealNum object:

``` php
$num1 = RealNum::new();
$num2 = $num1->copy(); // this will return a clone regardless of the immutability setting
```



### Non-breaking whitespace

Some locales use spaces when rendering numbers (eg. Swedish uses spaces for the thousands separator). `format()` can return strings containing either non-breaking whitespace characters,  or regular space characters.

An example of non-breaking whitespace is UTF-8's `\xc2\xa0` character which is used instead of a regular `\x20` space character. There are others like `\xe2\x80\xaf` which is a 'narrow no-break space'.

The `\xc2\xa0` UTF-8 character will become the familiar `&nbsp;` when turned into an html-entity.

Because `format()` is designed to produce readable numbers for humans, RealNum uses non-breaking whitespace by default, but you can instruct it to return regular spaces:

``` php
$num = RealNum::new(1234567.89)->locale('sv-SE'); // Swedish
print htmlentities($num->format('!breaking'));    // "1&nbsp;234&nbsp;567,89" (contains non-breaking whitespace - default)
print htmlentities($num->format('breaking'));     // "1 234 567,89" (regular spaces)
```

***Tip:*** The non-breaking whitespace setting can be changed per-object and by default. See the [formatting output](#formatting-output) and [default format settings](#default-format-settings) sections above.



### Chaining

The *setting* and *calculation* methods above may be chained together. eg.

``` php
print RealNum::new(1)
->locale('en-US')->val(5)->maxDecPl(3) // some "setting" methods
->add(4)->mul(3)->div(2)->sub(1)       // some "calculation" methods
->format(); // "12.5"
```



### Percentage values

Whilst the `RealNum` class is used for normal floating-point numbers, you may use the `Percent` class to perform all of the same actions as RealNum, but for percentage values. The difference is in the values you pass to Percent, and its `format()` output will show the percent symbol:

``` php
use CodeDistortion\RealNum\Percent;

$percent = new Percent(1);  // normal instantiation
$percent = Percent::new(1); // static instantiation which is more readable when chaining

print Percent::new(0)->format();    // "0%"
print Percent::new(1)->format();    // "100%" (note that 1 was passed, not 100)
print Percent::new(0.5)->format();  // "50%"
print Percent::new(0.01)->format(); // "1%"
print Percent::new(100)->format();  // "10,000%" (this happens if you pass 100)
print Percent::new(-1)->format();   // "-100%"
print Percent::new(null)->format(); // null
```

You can also use Percent objects with RealNums:

``` php
$num = RealNum::new(20);
$percent = Percent::new(0.5); // 50%
print $num->mul($percent);    // 10
```



### Laravel

The RealNum package is framework agnostic and works well on its own, but it also integrates with Laravel 5, 6, 7, 8 & 9.



#### Service-provider

RealNum integrates with Laravel 5.5+ automatically thanks to Laravel's package auto-detection.

Laravel's locale is registered with RealNum and Percent, and updated later if it changes.

<details><summary>(Click here for Laravel <= 5.4)</summary>
<p>
For Laravel 5.0 - 5.4, add the following line to <b>config/app.php</b>:

``` php
'providers' => [
    …
    CodeDistortion\RealNum\Laravel\ServiceProvider::class,
    …
],
```
</p>
</details>



#### Config

You may specify default max-dec-pl, immutability and format-settings by publishing the **config/code-distortion.realnum.php** config file and updating it:

``` bash
php artisan vendor:publish --provider="CodeDistortion\RealNum\Laravel\ServiceProvider" --tag="config"
```



## Testing

``` bash
composer test
```



## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.



### SemVer

This library uses [SemVer 2.0.0](https://semver.org/) versioning. This means that changes to `X` indicate a breaking change: `0.0.X`, `0.X.y`, `X.y.z`. When this library changes to version 1.0.0, 2.0.0 and so forth it doesn't indicate that it's necessarily a notable release, it simply indicates that the changes were breaking.



## Treeware

This package is [Treeware](https://treeware.earth). If you use it in production, then we ask that you [**buy the world a tree**](https://plant.treeware.earth/code-distortion/realnum) to thank us for our work. By contributing to the Treeware forest you’ll be creating employment for local families and restoring wildlife habitats.



## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.



### Code of Conduct

Please see [CODE_OF_CONDUCT](CODE_OF_CONDUCT.md) for details.



### Security

If you discover any security related issues, please email tim@code-distortion.net instead of using the issue tracker.



## Credits

- [Tim Chandler](https://github.com/code-distortion)



## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
