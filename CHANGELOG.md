# Changelog

All notable changes to `code-distortion/realnum` will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/), and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).



## [0.7.1] - 2021-12-23

### Added
- Added support for PHP 8.1
- Added phpstan ^1.0 to dev dependencies
- New `->abs()` method



## [0.7.0] - 2021-04-23

### Changed (breaking)
- Tweaked the way nulls are handled in calculations. Now:
  - Adding or subtracting *null* with *null* will give *null*,
  - Adding or subtracting a value (eg. 5) with *null* will give the value (5),
  - Multiplying or dividing *null* with *null* will give *null*,
  - Multiplying or dividing a value (eg. 5) with *null* will give *null*,

### Added
- New `->isNull()` method



## [0.6.5] - 2021-02-21

### Added
- Added support for PHP 8
- Added Laravel 8
- Updated to PSR12



## [0.6.4] - 2020-03-08

### Added
- Added Laravel 7



## [0.6.3] - 2020-01-29

### Changed
- Reviewed PHPDocs
- Updated readme



## [0.6.2] - 2020-01-27

### Changed
- Updated non-Testbench tests so they could use the non-namespaced phpunit TestCase from old versions of phpunit (because old versions of Testbench require old versions of phpunit). This allowed testing back to Laravel 5.2.
- Removed Testbench from tests that are really only needed to test the version of PHP.



## [0.6.1] - 2020-01-26

### Added
- GitHub actions workflows file

### Changed
- Updated the code-of-conduct to https://www.contributor-covenant.org/version/2/0/code_of_conduct.html
- Added Treeware details
- Bumped dependencies



## [0.6.0] - 2019-12-27

### Changed
- Changed the Laravel config file name



## [0.5.0] - 2019-12-04

### Changed
- Added a parent exception
- Tweaked the individual exceptions.



## [0.4.1] - 2019-11-13

### Changed
- Added custom exceptions



## [0.4.0] - 2019-11-12

### Changed (breaking)
- Updated the use of code-distortion/options which has changed ->resolve(x) to be chainable



## [0.3.0] - 2019-11-11

### Changed (breaking)
- Updated the Base class to accept 'null' (string) as a valid value
- Removed the "breaking" helper methods and attribute - it is now simply a format-setting
- Changed "nullZero" and "nullString" options to "null=x"
- Changed decPl to be a "decPl=x" format-setting

### Changed
- Updated documentation

### Fixed
- Changed the Laravel unit-test to an integration-test, and updated it to use the service-provider



## [0.2.0] - 2019-11-05

### Changed (breaking)
- Altered format() to use code-distortion/options based option values
- Changed locale and noBreakWhitespace to be format-settings

### Changed
- Updated documentation



## [0.1.2] - 2019-10-29

### Added
- Beta release
