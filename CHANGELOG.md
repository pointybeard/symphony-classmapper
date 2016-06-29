# Change Log
All notable changes to this project will be documented in this file.
This project adheres to [Semantic Versioning](http://semver.org/).

## [0.2.0] - 2016-06-29
#### Changed
- Renamed ModelHasNotBeenModified exception class to ModelHasNotBeenModifiedException
- Updated README with some fixes and changes based on the section name auto-discovery. Documentation around Field Mappings has been updated.
- Removed unused code methods

#### Added
- Added new exception 'SectionNotFoundException' which is thrown when the AbstractClassMapper cannot find a section for mapping.
- Added hasClassMapper trait used by custom class map objects
- The class mapper will now attempt to deduce the section name by deducing it from the class name. It can still be set manually using the SECTION constant
- Added `getCustomFieldMapping()` method. This replaces manually populating the `$fieldMapping` variable.
- Added "docblock" comments.

## 0.1.0 - 2016-06-17
#### Added
- Initial release

[0.2.0]: https://github.com/pointybeard/symphony-classmapper/compare/0.1.0...0.2.0
