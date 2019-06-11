# Change Log

All notable changes to this project will be documented in this file.
This project adheres to [Semantic Versioning](http://semver.org/).

## [2.0.0] - 2019-06-11
- Major rewrite of code base
- Added filtering an sorting
- Added ability to create models on the fly using `Classmapper\create()`

## [1.0.4][] - 2019-01-03
#### Added
-   Added `getDatabaseConnection()`, `bindToDatabase()`, and `unbindFromDatabase()` static methods in `AbstractClassMapper`.
-   Using `self::getDatabaseConnection()` instead of `SymphonyPDO\Loader::instance()` internally in `AbstractClassMapper`.

#### Changed
-   Removed `composer.lock` from repo
-   Updated `.gitignore` to include `.php_cs.cache` and `composer.lock`
-   Added `$force` argument to `AbstractClassMapper::findSectionFields()`. This allows section mappings to be updated when called instead of returning the cached values.

## [1.0.3][] - 2019-01-03
#### Changed
-   Added `class` argument to `AbstractClassMapper::toXML()` method. This is used instead of `XMLElement` when building the XML. Must either implement the same interface as `XMLElement` or extend it.

## [1.0.2][] - 2018-12-12
#### Fixed
-   Fixed check of Entry object. Namespace was incorrect.

## [1.0.1][] - 2018-12-02
#### Changed
-   Included `GROUP BY` clause in `AbstractClassMapper::fetchSQL()`. This prevents duplicate entries when fetching fields with multiple rows (e.g. `FLAG_ARRAY`)
-   Improved error handling in `AbstractClassMapper::update` when entry ID is invalid.

## [1.0.0][] - 2018-10-05
#### Fixed
-   Added getCaller() and getCallingClass() methods. Used by `__set()` when deciding if necessary to flag object as modified. Will not flag if caller was `PDOStatement::fetch` (Fixes #1)

#### Added
-   Added flags option to field mappings. Following flags are available: `FLAG_ARRAY`, `FLAG_BOOL`, `FLAG_FILE`, `FLAG_INT`, `FLAG_STR`, `FLAG_FLOAT`, `FLAG_CURRENCY`, `FLAG_NULL`

## [0.2.1][] - 2016-07-5
#### Fixed
-   Fixed reference to `ModelHasNotBeenModifiedException` in `AbstractClassMapper`

## [0.2.0][] - 2016-06-29
#### Changed
-   Renamed `ModelHasNotBeenModified` exception class to `ModelHasNotBeenModifiedException`
-   Updated README with some fixes and changes based on the section name auto-discovery. Documentation around Field Mappings has been updated.
-   Removed unused code methods

#### Added
-   Added new exception 'SectionNotFoundException' which is thrown when the `AbstractClassMapper` cannot find a section for mapping.
-   Added `hasClassMapperTrait` trait used by custom class map objects
-   The class mapper will now attempt to deduce the section name by deducing it from the class name. It can still be set manually using the SECTION constant
-   Added `getCustomFieldMapping()` method. This replaces manually populating the `$fieldMapping` variable.

## 0.1.0 - 2016-06-17
#### Added
- Initial release

[2.0.0]: https://github.com/pointybeard/symphony-classmapper/compare/1.0.4...2.0.0
[1.0.4]: https://github.com/pointybeard/symphony-classmapper/compare/1.0.3...1.0.4
[1.0.3]: https://github.com/pointybeard/symphony-classmapper/compare/1.0.2...1.0.3
[1.0.2]: https://github.com/pointybeard/symphony-classmapper/compare/1.0.1...1.0.2
[1.0.1]: https://github.com/pointybeard/symphony-classmapper/compare/1.0.0...1.0.1
[1.0.0]: https://github.com/pointybeard/symphony-classmapper/compare/0.2.1...1.0.0
[0.2.1]: https://github.com/pointybeard/symphony-classmapper/compare/0.2.0...0.2.1
[0.2.0]: https://github.com/pointybeard/symphony-classmapper/compare/0.1.0...0.2.0
