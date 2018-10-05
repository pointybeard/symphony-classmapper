# Change Log
All notable changes to this project will be documented in this file.
This project adheres to [Semantic Versioning](http://semver.org/).

## [1.0.0] - 2018-10-05
#### Fixed
- Added getCaller() and getCallingClass() methods. Used by `__set()` when deciding if necessary to flag object as modified. Will not flag if caller was `PDOStatement::fetch` (Fixes #1)

#### Added
- Added flags option to field mappings. Following flags are available: FLAG_ARRAY, FLAG_BOOL, FLAG_FILE, FLAG_INT, FLAG_STR, FLAG_FLOAT, FLAG_CURRENCY, FLAG_NULL

## [0.2.1] - 2016-07-5
#### Fixed
- Fixed reference to `ModelHasNotBeenModifiedException` in `AbstractClassMapper`

## [0.2.0] - 2016-06-29
#### Changed
- Renamed `ModelHasNotBeenModified` exception class to `ModelHasNotBeenModifiedException`
- Updated README with some fixes and changes based on the section name auto-discovery. Documentation around Field Mappings has been updated.
- Removed unused code methods

#### Added
- Added new exception 'SectionNotFoundException' which is thrown when the `AbstractClassMapper` cannot find a section for mapping.
- Added `hasClassMapperTrait` trait used by custom class map objects
- The class mapper will now attempt to deduce the section name by deducing it from the class name. It can still be set manually using the SECTION constant
- Added `getCustomFieldMapping()` method. This replaces manually populating the `$fieldMapping` variable.

## 0.1.0 - 2016-06-17
#### Added
- Initial release

[1.0.0]: https://github.com/pointybeard/symphony-classmapper/compare/0.2.1...1.0.0
[0.2.1]: https://github.com/pointybeard/symphony-classmapper/compare/0.2.0...0.2.1
[0.2.0]: https://github.com/pointybeard/symphony-classmapper/compare/0.1.0...0.2.0
