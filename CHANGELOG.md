# Changelog

All notable changes to `realnum` will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/), and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).



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
