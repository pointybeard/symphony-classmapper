# Symphony CMS: Section Class Mapper

- Version: 2.0.0
- Date: June 11 2019
- [Release notes](https://github.com/pointybeard/symphony-classmapper/blob/master/CHANGELOG.md)
- [GitHub repository](https://github.com/pointybeard/symphony-classmapper)

[![Latest Stable Version](https://poser.pugx.org/pointybeard/symphony-classmapper/version)](https://packagist.org/packages/pointybeard/symphony-classmapper) [![License](https://poser.pugx.org/pointybeard/symphony-classmapper/license)](https://packagist.org/packages/pointybeard/symphony-classmapper)

Maps sections into custom model classes, simplifying the process of creating, modifying, deleting and fetching entries in Symphony CMS.

## Requirements

This library requires PHP 7.2 or later. For use with earlier version of PHP, use 1.0.x instead (`composer require pointybeard/symphony-classmapper:\<2.0`).

## Installation

Symphony Class Mapper is installed via [Composer](http://getcomposer.org/). To install, use `composer require pointybeard/symphony-classmapper` or add `"pointybeard/symphony-classmapper": "~2.0"` to your `composer.json` file.

## Usage

The most basic usage is to let Section Class Mapper create an annonomous class for you and map it on to your section by using `Classmapper\create()`. E.g. assuming you have a section called 'articles' and a field called 'title':

```php
declare(strict_types=1);

include 'vendor/autoload.php';

use pointybeard\Symphony\Classmapper;

Classmapper\create(
    'Article', // Name of class to be created
    'articles' // Handle of section to map
);
```

Note that the second argument for `create()`, section handle, is optional. If ommitted, Classmapper will attempt to deduce your section handle from your class name (in this case, 'Article'). It does this by assuming that your section is a pluralised version of the class name.

In the above example, the class name of 'Article' would be used to deduce a corresponding section handle of `articles`. Should the class mapper not be able to locate a section, a `ClassmapperException` will be thrown.

Setting a section handle is useful if your section name doesn't stick to the pluralisation assumption or if it might return an ambiguous result (i.e, more than one matching section).

Once created, Articles can be created by instanciating the newly created `Article` class, setting field values, and calling `save()`.

```php
// Create a new article
$article = new Article;
$article->title('My Article');
$article->save();

// Classmapper also supports method chaining like so
(new Article)
    ->title('My New Article')
    ->save()
;
```

Existing articles can be accessed using two built-in methods: `all()` and `loadFromId()`.

```php
// Get article with id of 1
$article = Article::loadFromId(1);

// Iterate over all articles
foreach(Article::all() as $article) {
    printf("%d: %s\r\n", $article->id, $article->title);
}
```

Other useful methods include `hasBeenModified()`, `toXml()`, and `delete()`:

```php
# Check if it was modified
$article->hasBeenModified();

# Get the XML representation of your Article
$article->toXml();

# Remove the article
$article->delete()

# Alternatively, you can delete entries like this
Article::loadFromId(3)->delete();

```

### Creating Custom Model Classes

The auto-generated class produced by calling `Classmapper::create()` are useful but somewhat limited. The biggest limitation being that they cannot have custom field mappings to accomodate non-standard fields. They are useful for basic sections without complex relationships to other sections. To get around this limitation, we need reate your a concrete class. This gives you all the same built-in methods, but, allows you to expand it's API and, most importantly, define fields.

To create a custom Classmapper model, extend `AbstractModel` and use the `HasModelTrait` trait. E.g. Using the same Articles example:

```php
    <?php

    namespace Your\Project\Namespace;

    use pointybeard\Symphony\Classmapper;

    final class Article extends Classmapper\AbstractModel
    {
        use Classmapper\Traits\HasModelTrait;
    }
```

The trait `HasModelTrait` provides three static member variables: `$sectionFields`, `$fieldMapping` and `$section`. They are used internally and hold a mapping to the Symphony section and the fields from that section; all are auto-populated by the parent object at run-time.

If the section has a non-standard handle, it can set manually by overloading `AbstractModel::getSectionHandle()` to return the section handle. e.g.

```php
...
public function getSectionHandle(): string
{
    return 'articles';
}
...
```

At this stage, the custom Article class is identical to that produced by `Classmapper::create('Article')`, however, we have a framework for adding additional features, logic, and defining fields.

### Accessing Values

The class mapper takes all the fields in a section and creates class member names for them automatically. These names are generated using the field handle and converting them to `camelCase`. E.g. "published-date" becomes `publishedDate` and "my-awesome-field" is `myAwesomeField`.

### Creating A Custom Field Mapping

The class mapper assumes all fields have a `value` field in the database and that value is always a string, however, this is not true for every field. For example, a Select Box Link field has a field called `relation_id` which is an integer. In this situation you must tell the class mapper how the field should be mapped and its type. This is done by overloading the `AbstractModel::getCustomFieldMapping()` method.

Using the Article example from above, lets assume there is now a field called "Author" which is a Select Box Link field pointing to the "Authors" section. We'll tell the Class Mapper that the Author field is an integer and has a database field `relation_id` (instead of the default `value`). Finally, we'll remap the field name to be `authorId` instead of `author`.

```php
...
# Create a mapping for the Author field, mapping the id to 'authorId'
protected static function getCustomFieldMapping() {
    return [
        'author' => [
            'databaseFieldName' => 'relation_id',
            'classMemberName' => 'authorId',
            'flags' => self::FLAG_INT
        ],
    ];
}

# Create a method that allows easy retrieval of an Author object.
# Note, this assumes an Author class model exists.
public function author() {
    return Author::fetchFromId($this->authorId);
}
...
```

You can see how we quickly wired up the Articles model to know about Authors and how to retrieve them.

### Using Flags

You can specify a `flag` property for custom field mappings (briefly covered in 'Creating A Custom Field Mapping' above) to trigger different behaviours when retrieving and saving data.

Flags can be combined using the bitwise OR (`|`) operator. Note, some flags cannot, or don't make sense to, combine with other flags.

Here is an example showing a more fully fleshed out Model's custom field mapping:

```php
...
protected static function getCustomFieldMapping() {
    return [
        'related-entries' => [
            'databaseFieldName' => 'relation_id',
            'classMemberName' => 'relatedEntryIDs',
            'flags' => self::FLAG_ARRAY | self::FLAG_INT | self::FLAG_NULL
        ],
        'published' => [
            'flags' => self::FLAG_BOOL
        ],
        'date' => [
            'classMemberName' => 'dateCreatedAt',
            'flags' => self::FLAG_SORTBY | self::FLAG_SORTDESC | self::FLAG_REQUIRED
        ],
        'title' => [
            'flags' => self::FLAG_STR | self::FLAG_REQUIRED
        ],
        'author' => [
            'databaseFieldName' => 'relation_id',
            'classMemberName' => 'authorId',
            'flags' => self::FLAG_INT | self::FLAG_REQUIRED
        ],
        'subtitle' => [
            'flags' => self::FLAG_STR | self::FLAG_NULL
        ],
    ];
}
...
```

#### Type

Type flags signal to the Class Mapper, when data is retrieved or saved, that it should be cast

*FLAG\_INT*, *FLAG\_STR*, and *FLAG\_FLOAT*

These flags are used to type cast data being pulled out and map directly to the native PHP methods `intval()`, `floatval()`, and `strval()` respectivly. They can be combined with `FLAG_ARRAY`, in which case all items in the array will be cast to that type.

*FLAG\_CURRENCY*

Similar to `FLAG_FLOAT`, however, will limit the result to 2 decimal places.

*FLAG\_BOOL*

Converts the data coming out of the database from `Yes|No` string value into `true|false`. When saving, it is converted back in to a Yes|No string value. Can be combined with `FLAG_ARRAY`

#### Behavioural

*FLAG\_ARRAY*

Use this when the field has multiple rows of data, like a multi-select. The data
returned will be an array of values. Can combine with `FLAG_INT`, `FLAG_STR`, `FLAG_FLOAT`, `FLAG_BOOL`, `FLAG_CURRENCY`, and `FLAG_NULL`

*FLAG\_FILE*

Will set the field value to an array containing `file`, `size`, `mimetype`, and `meta`. Note, when saving only `file` is used since the other fields can be re-built by examining the file if needed. Can only combine with `FLAG_NULL`

*FLAG\_NULL*

Converts empty values, i.e. int(0), string(""), (array)[] etc, into `NULL`. Can be combined with all other flags. When the Class mapper build data for the model, if the field's value is empty, it will instead set it to NULL.

#### Sorting

Sorting flags are used when retrieving data. The Class Mapper will look for these flags when building the SQL used to pull put data from the database.

To enable sorting for your model, be sure to implement `SortableModelInterface` and also use the `Traits\HasSortableModelTrait` trait. E.g.

```php
use pointybeard\Symphony\Classmapper;

class Articles extends Classmapper\AbstractModel implements Classmapper\Interfaces\SortableModelInterface {
    use Classmapper\Traits\HasSortableModelTrait;
    ...
}
```

*FLAG\_SORTBY*

When set, the result set will be sorted by this field. Note, the specific table column used to sort is either `value`, e.g. [field].value or `databaseFieldName` if it is set.

*FLAG\_SORTDESC* and *FLAG\_SORTASC*

Denotes the sorting direction; ASC or DESC. Cannot combine both `FLAG_SORTASC` and `FLAG_SORTDESC`. Default is `FLAG_SORTASC`.

#### Validation

These flags are applied when saving.

*FLAG\_REQUIRED*

Signifies that this field must have a non-empty value, otherwise saving will fail. Note that `FLAG_NULL` is NOT the opposite of `FLAG_REQUIRED`. It is possible that a field might have a null value, however, `FLAG_REQUIRED` would ensure it has a value before allowing you to save.

### Validation when Saving

When saving an entry, you can tell the Class Mapper how strict you would like it to be. e.g. `$articles->save(self::FLAG_ON_SAVE_ENFORCE_MODIFIED)`. Flags can be combined using the bitwise OR (`|`) operator (as is the case with all `FLAG_*` constants).

The following flags are supported:

*FLAG\_ON\_SAVE\_VALIDATE*

When saving, all fields will be validated according to any custom field `flags` mapping. Currently the only related flag is `FLAG_REQUIRED` which will ensure the field has a non-empty value. If validation fails, a `ModelValidationFailedException` exception will be thrown. This flag is enabled by default. Pass `NULL`, `0`, or another flag to prevent valdiation when saving. e.g. `$article->save(null)`

*FLAG\_ON\_SAVE\_ENFORCE\_MODIFIED*

This will trigger a `ModelHasNotBeenModifiedException` exception if you attempt to save an entry that has not been modified. Check `hasBeenModified()`

### Providing Custom SQL when fetching

It might be necessary to provide custom SQL to use when the class mapper loads an object. To do this, overload the `AbstractModel::fetchSQL()` method. It should return an SQL string. You can use `self::$sectionFields` to easily access the ID values of fields in your section. E.g.

```php
...
protected static function fetchSQL($where = 1)
{
    return sprintf('
        SELECT SQL_CALC_FOUND_ROWS
            t.entry_id as `id`,
            t.value as `title`,
            f.file as `file`,
            a.value as `available`
        FROM `tbl_entries_data_%d` AS `t`
        INNER JOIN `tbl_entries_data_%d` AS `f` ON f.entry_id = t.entry_id
        LEFT JOIN `tbl_entries_data_%d` AS `a` ON a.entry_id = f.entry_id
        WHERE %s
        ORDER BY t.entry_id ASC',
        self::$sectionFields['title'],
        self::$sectionFields['file'],
        self::$sectionFields['available'],
        $where
    );
}
...
```

Note that overloading the `fetchSQL()` method will mean you need to handle using the correct field mappings, filtering and sorting rather than letting `AbstractModel` handle it for you.

### Modifying data before saving

There are times when you might need to change data on the fly before it is saved into the entry. You can do this by overloading the `AbstractModel::getData()` method. For example, you might have a "modified date" field in your section. By overloading the `getData` method, you can ensure it is updated automatically.

```php
...
protected function getData()
{
    $data = parent::getData();

    // Check if anything has changed and, if so, set the new modified date
    if($this->hasBeenModified()) {
      $data['modified'] = 'now';
    }

    return $data;
}
...
```

### Filtering Results

The class mapper gives you the `fetchById()`, and `all()` methods out of the box. However, you'll quickly need a more powerful way of filtering down results. This is where using Filter classes come in to play.

To enable Filtering of results on your model, implement the `FilterableModelInterface` interface and use the `HasFilterableModelTrait` trait. e.g.

```php
use pointybeard\Symphony\Classmapper;

class Articles extends Classmapper\AbstractModel implements Classmapper\Interfaces\FilterableModelInterface {
    use Classmapper\Traits\HasFilterableModelTrait;
    ...
}
```

This will give you access to 5 new methods: `fetch()`, `filter()`, `appendFilter()`, `clearFilters()`, and `getFilters()` as well as an outlet to use the 5 included Filter classes: `Basic`, `FindInSet`, `IsNotNull`, `IsNull`, and `Now`

#### Fetch

The simplest way to filter results is to call `fetch()`. It expects to get a objects that extend `AbstractFilter` (note that calling `fetch()` without any filters is the same as calling `all()`).

Filter objects can be instanciated direct, e.g. `new Filter\Basic(...)`, however, Class Mapper includes a factory class to make the process more consistent.

Here is a simple example:

```
## Find all articles that are published and have a creation date less than now
$article->fetch(
    Classmapper\FilterFactory::build('Basic', 'published', 'Yes'),
    Classmapper\FilterFactory::build('Now', 'dateCreatedAt', Classmapper\Filters\Basic::COMPARISON_OPERATOR_LT),
);
```

The result of calling `fetch()` will be a SymphonyPDO `ResultIterator` object. The results can be accessed using a `foreach` loop or the `each()` method with a custom function. e.g.

```php
Article::fetch(...)->each(function ($article) {
  // do something with $article here
});

foreach(Article::fetch(...) as $article) {
  // do something with $article here
}
```

Each Filter has slightly different requirements for instanciation, however, they will always following this ordering (where values in square brackets may or may not be required):

    FILTER, FIELD_NAME, [VALUE], [TYPE], [COMPARISON], OPERATOR

You can check each Filter class's specific requirements by looking at their constructor.

*FILTER*

This is the name of the Filter class to use. Built-in Filters include `Basic`, `FindInSet`, `IsNotNull`, `IsNull`, and `Now`.

*FIELD\_NAME*

This is the name of the field in the section as defined by the Class Mapper. It will either be the value specified by `classMemberName` in your field mappings, or the camelCase version of the field handle.

*TYPE*

This is one of the [PDO's Predefined Constants](https://www.php.net/manual/en/pdo.constants.php). The default is `PDO::PARAM_STR`.

*COMPARISON*

This is the comparison operator used when comparing `value` to the value in `fieldName`. These are provided by `Filter\Basic`:

```php
COMPARISON_OPERATOR_EQ          // '='
COMPARISON_OPERATOR_NEQ         // '!='
COMPARISON_OPERATOR_GT          // '>'
COMPARISON_OPERATOR_GTEQ        // '>='
COMPARISON_OPERATOR_LT          // '<'
COMPARISON_OPERATOR_LTEQ        // '<='
COMPARISON_OPERATOR_LIKE        // 'LIKE'
COMPARISON_OPERATOR_NOT_LIKE    // 'NOT LIKE'
```

*OPERATOR*

This tells the Class Mapper how to join the filters. This operator is applied between the current filter and the previous filter. Available options are `OPERATOR_OR`, and `OPERATOR_AND`. The default is `OPERATOR_AND`.


#### Filter Classes

The 5 built-in Filters are `Basic`, `FindInSet`, `IsNotNull`, `IsNull`, and `Now`

*Filters\Basic*

This is useful for simple `a COMPARED TO b` type comparisons. It provides the operators `=`, `!=`, `>`, `>=`, `<`, `<=`, `LIKE`, and `NOT LIKE` which are available as class constants (see *COMPARISON* above).

Basic expects up to 5 arguments when instanciated

```php
public function __construct(
    string $field,
    $value,
    int $type = \PDO::PARAM_STR,
    string $comparisonOperator = self::COMPARISON_OPERATOR_EQ,
    string $operator = self::OPERATOR_AND
)
```

*Filters\FindInSet*

This filter expects to get an array of values. It will check if the field value is the same as any of the values provided.

Basic expects up to 3 arguments when instanciated

```php
public function __construct(
    $field,
    array $values,
    string $operator = self::OPERATOR_AND
)
```

*Filters\IsNull* and *Filters\IsNotNull*

These filters will check if a value is or is not null.

Basic expects 2 arguments when instanciated.

```php
public function __construct(
    $field,
    string $operator = self::OPERATOR_AND
)
```

*Filters\Now*

This filter extends `Filters\Basic`, giving access to the `NOW()` feature of SQL.

Basic expects up to 3 arguments when instanciated.

```php
public function __construct(
    string $field,
    string $comparisonOperator = self::COMPARISON_OPERATOR_EQ,
    string $operator = self::OPERATOR_AND
)
```

#### Using `filter()`

Instead of calling `fetch()` and providing Filters on the fly, you can create an instance of the model class and then append filters with `appendFilter()`. Once you have built up the set of filters you want, call `filter()` to return a result set. For example:

```php
## Create an instance of the Article model
$article = new Article;

## Append filters with appendFilter(). Note method chaining is supported
$article
    ->appendFilter(Classmapper\FilterFactory::build('Basic', 'published', 'Yes'))
    ->appendFilter(Classmapper\FilterFactory::build('Now', 'dateCreatedAt', Classmapper\Filters\Basic::COMPARISON_OPERATOR_LT))
;

## Returns the results
$result = $article->filter();

## Optionally clear the filters from this instance
$article->clearFilters();
```

The main benefit of using `appendFilter()` and `filter()` is that you can pass the model around, allowing other sections of code to add/remove filters before finally calling `filter()`. Additionally, the result is cached in that instance so you can call `filter()` multiple times without any performance hit.

To get a result with different filters, either call `clearFilters()` or create a new instance of your model.

## Support

If you believe you have found a bug, please report it using the [GitHub issue tracker](https://github.com/pointybeard/symphony-classmapper/issues),
or better yet, fork the library and submit a pull request.

## Contributing

We encourage you to contribute to this project. Please check out the [Contributing documentation](https://github.com/pointybeard/symphony-classmapper/blob/master/CONTRIBUTING.md) for guidelines about how to get involved.

## License

"Symphony CMS: Section Class Mapper" is released under the [MIT License](http://www.opensource.org/licenses/MIT).
