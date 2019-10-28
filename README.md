# RealNum

[![Latest Version on Packagist](https://img.shields.io/packagist/v/code-distortion/realnum.svg?style=flat-square)](https://packagist.org/packages/code-distortion/realnum) ![PHP from Packagist](https://img.shields.io/packagist/php-v/code-distortion/realnum?style=flat-square) ![Laravel](https://img.shields.io/badge/laravel-5%20%26%206-blue?style=flat-square) [![contributor covenant](https://img.shields.io/badge/contributor%20covenant-v1.4%20adopted-ff69b4.svg?style=flat-square)](code-of-conduct.md)

***code-distortion/realnum*** is a PHP library for arbitrary-precision floating-point maths with locale-aware formatting. It integrates with Laravel 5 & 6 but works stand-alone as well.

RealNum uses PHP's [BCMath](https://www.php.net/manual/en/book.bc.php) extension to avoid inaccurate floating point calculations:

``` php
// an example of floating-point inaccuracy
var_dump(0.1 + 0.2 == 0.3); // bool(false)
// for more details see The Floating-Point Guide - https://floating-point-gui.de/
```

Numbers are formatted in different locales using PHP's [NumberFormatter](https://www.php.net/manual/en/class.numberformatter.php). Some examples include:

| | en-US | de-DE | sv-SE | hi-IN | ar-EG |
| :----: | :----: | :----: | :----: | :----: | :----: |
| RealNum | 1,234,567.89 | 1.234.567,89 | 1 234 567,89 | 12,34,567.89 | ١٬٢٣٤٬٥٦٧٫٨٩ |
| Percent | 123.45% | 123,45 % | 123,45 % | 123.45% | ١٢٣٫٤٥٪؜ |

The ***Percent*** class is also available alongside the ***RealNum*** class to perform all of the same actions as RealNum but for percentage values. See the [percentage values](#percentage-values) section below for more details.

If you would like to work with *currency* values, please consider the [code-distortion/currency](https://github.com/code-distortion/currency) package.

## Installation

You can install the package via composer:

```bash
composer require code-distortion/realnum
```

## Usage

Instantiate a RealNum object and you can start performing calculations with it, perform comparisons, and render it as a readable string:
``` php
use CodeDistortion\RealNum\RealNum;

$num1 = new RealNum(5555.55); // normal instantiation
$num1 = RealNum::new(5555.55); // static instantiation which is more readable when chaining

$num2 = $num1->add(4444.44); // (it's immutable so a new object is created)
$num2->between(8000, 10000); // true
print $num2->format(); // "9,999.99"
```

### Setting values

You may set the value explicitly:
``` php
$num1 = RealNum::new(5); // the value is set to 5 straight away
$num2 = $num1->val(10); // and is then set to 10 (it's immutable so a new object is created)
```

The types of values you can pass to RealNum are:

``` php
$num1 = RealNum::new(5); // an integer
$num2 = RealNum::new(5.5); // a float
$num3 = RealNum::new('6'); // a numeric string
$num4 = RealNum::new($num3); // another RealNum object
$num5 = RealNum::new(null); // null
$num6 = RealNum::new(); // (will default to null)
```

***TIP:*** To maintain precision when setting values, pass them as strings instead of floating-point numbers:

``` php
RealNum::new(0.12345678901234567890); // "0.12345678901235" (precision lost because the passed number is really a float)
RealNum::new('0.12345678901234567890'); // "0.12345678901234567890" (passed as a string)
```

You may also set other settings that RealNum uses:

``` php
RealNum::new(1)->locale('en-US'); // sets the locale this object uses (see the 'locale' section below)
RealNum::new(1)->maxDecPl(30); // sets the maximum number of decimal places used (see the 'precision (maximum decimal places)' section below)
RealNum::new(1)->immutable(false); // sets whether this object is immutable or not (see the 'immutability' section below)
RealNum::new(1)->noBreakWhitespace(true); // sets whether this object will use non-breaking whitespace when format() is called or not (see the 'non-breaking whitespace' section below)
```

### Retrieving values

To retrieve the value contained in a RealNum you may read the `val` and `cast` properties. The `val` property maintains precision and in contrast, `cast` will loose some precision so use them depending on your needs:

``` php
$num = RealNum::new('0.12345678901234567890');
print $num->val; // "0.12345678901234567890" (returned as a string, or null)
print $num->cast; // 0.12345678901235 (cast to either an integer, float or null - this is less accurate)
```

You may also read other settings that RealNum uses:

``` php
$num = RealNum::new();
print $num->maxDecPl; // 20 (the maximum number of decimal places used)
print $num->locale; // "en"
print $num->immutable; // true
print $num->noBreakWhitespace; // false
```

***Note:*** See the [formatting output](#formatting-output) section below for more details about how to render the value as a readable string.

### Calculations

The calculations you may perform are:

``` php
$num = RealNum::new(5);
$num = $num->inc(); // increment
$num = $num->dec(); // decrement
$num = $num->add(2); // add x
$num = $num->sub(2); // subtract x
$num = $num->div(2); // divide by x
$num = $num->mul(2); // multiply by x
$num = $num->round(); // round to zero decimal places
$num = $num->round(2); // round to x decimal places
$num = $num->floor(); // use the floor of the current value
$num = $num->ceil(); // use the ceiling of the current value
```

You may pass multiple values to `add()`, `sub()`, `div()` and `mul()`:

```php
RealNum::new(5)->add(4, 3, 2, 1); // 15
RealNum::new(5)->sub(4, 3, 2, 1); // -5
RealNum::new(5)->div(4, 3, 2, 1); // 0.2083333...
RealNum::new(5)->mul(4, 3, 2, 1); // 120
```

You may pass: *integer*, *float*, *numeric string* and *null* values, as well as other *RealNum* objects:

```php
$num1 = RealNum::new(5);
$num1 = $num1->add(2); // pass an integer
$num1 = $num1->add(2.0); // pass a float
$num1 = $num1->add('2'); // pass a numeric string
$num1 = $num1->add(null); // pass null (adds nothing)
$num2 = RealNum::new(2);
$num1 = $num1->add($num2); // pass another RealNum
```

### Comparisons

You can compare numbers to other values with bound checking:
``` php
RealNum::new(5)->lessThan(10); // alias of lt(..)
RealNum::new(5)->lessThanOrEqualTo(10); // alias of lte(..)
RealNum::new(5)->equalTo(10); // alias of eq(..)
RealNum::new(5)->greaterThanOrEqualTo(10); // alias of gte(..)
RealNum::new(5)->greaterThan(10); // alias of gt(..)

$num1 = RealNum::new(5);
$num2 = RealNum::new(6);
$num1->lt($num2); // you can compare a RealNum with others
```

You may pass multiple values to these comparison methods. eg.

``` php
RealNum::new(5)->lt(10, 15, 20); // will return true if 5 is less-than 10, 15 and 20
```

You can check if a RealNum's value is between given bounds:

``` php
RealNum::new(5)->between(2, 8); // check if 5 is between x and y (inclusively)
RealNum::new(5)->between(2, 8, false); // check if 5 is between x and y (NOT inclusively)
```

### Formatting output

Use the `format()` method to generate a readable-string version of the current value:

``` php
$num = RealNum::new(1234567.89);
print $num->format(); // "1,234,567.89"
```

You may alter the way `format()` renders the output by passing options:

``` php
print RealNum::new(1234567.89)->format(RealNum::NO_THOUSANDS); // "1234567.89" (removes the thousands separator)
print RealNum::new(1234567.89)->format(RealNum::SHOW_PLUS); // "+1,234,567.89" (adds a '+', only for positive values)
print RealNum::new(-1234567.89)->format(RealNum::ACCT_NEG); // "(1,234,567.89)" (uses brackets for negative numbers)

print RealNum::new(null)->format(); // null (will return actual null by default)
print RealNum::new(null)->format(RealNum::NULL_AS_ZERO); // "0"
print RealNum::new(null)->format(RealNum::NULL_AS_STRING); // "null"

// show to the number of max-decimal-places currently being used
print RealNum::new(1234567.89)->maxDecPl(5)->format(RealNum::ALL_DEC_PL); // "1,234,567.89000"

// non-breaking spaces can be returned instead of spaces - see the 'non-breaking whitespace' section below for more details
print htmlentities(
    RealNum::new(1234567.89)->locale('sv-SE')->format(RealNum::NO_BREAK_WHITESPACE)
); // "1&nbsp;234&nbsp;567,89"
```

You may use several settings at the same time:

```php
print RealNum::new(1234567.89)->format(RealNum::NO_THOUSANDS | RealNum::SHOW_PLUS); // "+1234567.89"
```

You may also choose the number of decimal places to show at the time of rendering:

``` php
print RealNum::new(1234567.89)->format(null, 5); // "1,234,567.89000" (5 decimal places)
```

The `format()` method output will generate the correct output for the current locale:

``` php
print RealNum::new(1234567.89)->locale('en')->format(); // "1,234,567.89" (English)
print RealNum::new(1234567.89)->locale('en-AU')->format(); // "1,234,567.89" (Australian English)
print RealNum::new(1234567.89)->locale('en-IN')->format(); // "12,34,567.89" (Indian English)
print RealNum::new(1234567.89)->locale('de')->format(); // "1.234.567,89" (German)
print RealNum::new(1234567.89)->locale('sv')->format(); // "1 234 567,89" (Swedish)
print RealNum::new(1234567.89)->locale('ar')->format(); // "١٬٢٣٤٬٥٦٧٫٨٩" (Arabic)
```

Casting a RealNum to a string is equivalent to calling `format()` with no arguments:

```php
print (string) RealNum::new(1234567.89); // "1,234,567.89"
```

***NOTE***: RealNum uses PHP's NumberFormatter to render the readable output, which currently has a limitation of being able to only show about 17 digits (including before the decimal place). So `format()`'s output will act a bit strangely if there are too many digits. The number stored inside will maintain it's full accuracy however. You may access the full number by reading the `val` property (see the [retrieving values](#retrieving-values) section above).

### Locale

***Note:*** When using Laravel this will be set automatically. See the [Laravel](#laravel) section below.

RealNum's default locale is "en" (English) but you can choose which one to use.

You may change the locale per-object:

``` php
$num1 = RealNum::new(1234567.89);
print $num1->locale; // "en" (the default)
print $num1->format(); // "1,234,567.89"
$num2 = $num1->locale('fr-FR'); // (it's immutable so a new object is created)
print $num2->locale; // "fr-FR"
print $num2->format(); // "1 234 567,89"
```

The locale may be changed by default. All ***new*** RealNum objects will start with this setting:

``` php
RealNum::setDefaultLocale('fr-FR');
print RealNum::getDefaultLocale(); // "fr-FR"
```

### Precision (maximum decimal places)

***Note:*** When using Laravel you may set this in the package config file. See the [Laravel](#laravel) section below.

The `maxDecPl` precision setting is the maximum number of decimal places you would like RealNum to handle. A maxDecPl of 20 is used by default but you may change this per-object:

``` php
$num = RealNum::new('0.123456789012345678901234567890'); // this has more decimals than a float can handle so pass it as a string
print $num->val; // "0.12345678901234567890" ie. rounded to the default 20 decimal places
$num = RealNum::new()->maxDecPl(30)->val('0.123456789012345678901234567890');
print $num->val; // "0.123456789012345678901234567890" the full 30 decimal places
```

The precision may be changed by default. All ***new*** RealNum objects will start with this setting:

``` php
RealNum::setDefaultMaxDecPl(30);
print RealNum::getDefaultMaxDecPl(); // 30
```

### Immutability

***Note:*** When using Laravel you may set this in the package config file. See the [Laravel](#laravel) section below.

RealNum is immutable by default which means that once an object is created it won't change. Anything that changes the value will return a new RealNum instead. You can then pass a RealNum object to other parts of your code and be sure that it won't be changed unexpectedly:

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

Immutability may be turned off by default. All ***new*** RealNum objects will start with this setting:

``` php
RealNum::setDefaultImmutability(false);
var_dump(RealNum::getDefaultImmutability()); // "bool(false)"
```

You can explicitly make a clone of a RealNum object:

```php
$num1 = RealNum::new();
$num2 = $num1->copy(); // this will return a clone regardless of the immutability setting
```

### Non-breaking whitespace

***Note:*** When using Laravel you may set this in the package config file. See the [Laravel](#laravel) section below.

Some locales use spaces when rendering numbers (eg. Swedish use spaces for the thousands separator). `format()` can either return strings containing regular space characters, or with non-breaking space characters instead.

An example of non-breaking whitespace is UTF-8's `\xc2\xa0` character which is used instead of a regular `\x20` space character. There are others like `\xe2\x80\xaf` which is a 'narrow no-break space'.

The `\xc2\xa0` UTF-8 character will become the familiar `&nbsp;` when turned into an html-entity.

By default RealNum uses regular spaces, but you instruct it to return non-breaking whitespace when calling `format()`:

``` php
$num = RealNum::new(1234567.89)->locale('sv-SE'); // Swedish
print htmlentities($num->format()); // "1 234 567,89" (regular spaces)
print htmlentities($num->format(RealNum::NO_BREAK_WHITESPACE)); // "1&nbsp;234&nbsp;567,89" (contains non-breaking whitespace)
```

Non-breaking whitespace may be turned on per-object:

``` php
$num1 = RealNum::new(1234567.89)->locale('sv-SE'); // Swedish
print htmlentities($num1->format()); // "1 234 567,89" (regular spaces)
$num2 = $num1->noBreakWhitespace(true); // (it's immutable so a new object is created)
print htmlentities($num2->format()); // "1&nbsp;234&nbsp;567,89" (contains non-breaking whitespace)
```

Non-breaking whitespace may be turned on by default. All ***new*** RealNum objects will start with this setting:

``` php
RealNum::setDefaultNoBreakWhitespace(true);
var_dump(RealNum::getDefaultNoBreakWhitespace()); // "bool(true)"
```

### Chaining
The *setting* and *calculation* methods above may be chained together:

``` php
print RealNum::new(1)
->locale('en-US')->val(5)->maxDecPl(3) // some "setting" methods
->add(4)->mul(3)->div(2)->sub(1) // some "calculation" methods
->format(); // "12.5"
```

### Percentage values

Whilst the `RealNum` class is used for normal floating-point numbers, you may use the `Percent` class to perform all of the same actions as RealNum, but for percentage values. The difference is in the values you pass to Percent, and its `format()` output will show the percent symbol:

``` php
use CodeDistortion\RealNum\Percent;

$percent = new Percent(1); // normal instantiation
$percent = Percent::new(1); // static instantiation which is more readable when chaining

print Percent::new(0)->format(); // "0%"
print Percent::new(1)->format(); // "100%" (note that 1 was passed, not 100)
print Percent::new(0.5)->format(); // "50%"
print Percent::new(0.01)->format(); // "1%"
print Percent::new(100)->format(); // "10,000%" (this happens if you pass 100)
print Percent::new(-1)->format(); // "-100%"
print Percent::new(null)->format(); // null
```

You can also use Percent objects with RealNums:

```php
$num = RealNum::new(20);
$percent = Percent::new(0.5); // 50%
print $num->mul($percent); // 10
```

### Laravel

The RealNum package is framework agnostic and works well on it's own, but it also integrates with Laravel 5 & 6.

#### Service-provider

RealNum integrates with Laravel 5.5+ automatically thanks to Laravel's package auto-detection. For Laravel 5.0 - 5.4, add the following line to **config/app.php**:

``` php
'providers' => [
  ...
CodeDistortion\RealNum\Laravel\ServiceProvider::class,
  ...
],
```

The service-provider will register the starting locale with RealNum and Percent and update them if it changes, so you don't have to.

#### Config

You may specify default max-dec-pl, immutability and non-breaking-whitespace values by publishing the **config/realnum.php** config file and updating it:

``` bash
php artisan vendor:publish --provider="CodeDistortion\RealNum\Laravel\ServiceProvider" --tag="config"
```

### Testing

``` bash
composer test
```

### Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

### Code of conduct

Please see [CODE_OF_CONDUCT](CODE_OF_CONDUCT.md) for details.

### Security

If you discover any security related issues, please email tim@code-distortion.net instead of using the issue tracker.

## Credits

- [Tim Chandler](https://github.com/code-distortion)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.

## PHP Package Boilerplate

This package was generated using the [PHP Package Boilerplate](https://laravelpackageboilerplate.com).
