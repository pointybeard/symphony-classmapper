# Symphony CMS: Section Class Mapper

- Version: v2.0.0
- Date: May 14th 2019
- [Release notes](https://github.com/pointybeard/symphony-classmapper/blob/master/CHANGELOG.md)
- [GitHub repository](https://github.com/pointybeard/symphony-classmapper)

[![Latest Stable Version](https://poser.pugx.org/pointybeard/symphony-classmapper/version)](https://packagist.org/packages/pointybeard/symphony-classmapper) [![License](https://poser.pugx.org/pointybeard/symphony-classmapper/license)](https://packagist.org/packages/pointybeard/symphony-classmapper)

Maps sections into custom model classes, simplifying the process of creating, modifying, deleting and fetching entries in Symphony CMS.

## Requirements

This library requires PHP 7.2 or later. For use with earlier version of PHP, please use 1.0.x instead (`composer require pointybeard/symphony-classmapper:\<2.0`).

## Installation

Symphony Class Mapper is installed via [Composer](http://getcomposer.org/). To install, use `composer require pointybeard/symphony-classmapper` or add `"pointybeard/symphony-classmapper": "~2.0"` to your `composer.json` file.

## Usage

To use the Class Mapper, simply extend `AbstractModel`, and use `Trait\hasModelTrait`. E.g. assuming you have a section called 'articles' with a single field 'title':

```php
    <?php

    namespace Your\Project\Namespace;

    use Symphony\SectionClassMapper\SectionClassMapper;

    final class Article extends SectionClassMapper\AbstractModel
    {
        use SectionClassMapper\Traits\hasModelTrait;
    }
```

The trait `hasModelTrait` provides three static member variables: `$sectionFields`, `$fieldMapping` and `$section`. They are used internally and hold a mapping to the Symphony section and the fields from that section. All are auto-populated by the parent object.

The class mapper will attempt to deduce your section handle from your class name. It does this by assuming that your section is a pluralised version of the class name. In the above example, the class name of 'Article' is used to deduce that the corresponding section handle is `articles`. Should the class mapper not be able to locate a section, an exception will be thrown.

The section can set manually by populating the `SECTION` class constant. e.g. `const SECTION = 'article';` This is useful if your section name doesn't stick to the pluralisation assumption or if it might return an ambiguous result (more than 1 matching section).

You can now easily create new entries or load existing ones. For example, using the example class above:

```php
    # Create and save a new article
    $article = new Article;
    $article->title("my article");
    $article->save();

    # Load an existing article
    $article = Article::loadFromId(3);

    # Change a value
    $article->title("the new title");

    # Check if it was modified
    $article->hasBeenModified();

    # Save the changes
    $article->save();

    # Get all articles
    foreach(Article::all() as $article) {
        print $a->title;
    }

    # Get the XML representation of your Article
    $article->toXml();

    # Remove the article
    Article::loadFromId(3)->delete();
```

Class mapped objects also allow for method chaining. E.g.

```php
    $article = (new Article())
        ->title("My Article Title")
        ->body("The article body")
        ->date("now")
        ->save();
```

### Accessing Values

The class mapper takes all fields in your section and creates class member names for them automatically. These names are generated using the field name and converting them to `camelCase`. E.g. "Published Date" becomes `publishedDate` or "My Awesome Field" is `myAwesomeField`.

### Creating A Custom Field Mapping

The class mapper assumes all fields have a `value` field in the database. This is not true for every field though. For example, a Select Box Link field has a `relation_id` instead of a `value`. In this situation you must tell the class mapper how the field should be mapped. This is done by overloading the `getCustomFieldMapping()` method and returning an array of mappings.

Using the Article example from above, lets assume there is now a field called "Author" which is a Select Box Link field pointing to the "Authors" section. We want to get the Author's ID, and also the Author model if it exists.

```php

    # Create a mapping for the Author field, mapping the id to 'authorId'
    protected static function getCustomFieldMapping() {
        return [
            'author' => [
                'databaseFieldName' => 'relation_id',
                'classMemberName' => 'authorId'
            ],
        ];
    }

    # Create a method that allows easy retrieval of an Author object.
    # This assumes the Author class map exists.
    public function author() {
        return Author::fetchFromId($this->authorId);
    }
```

You can see how we quickly wired up the Articles object to know about Authors and how to retrieve them.

### Using Flags

You can specify a `flag` property for custom field mappings. Flags trigger different behaviour when retrieving and saving data. E.g.

```php

    protected static function getCustomFieldMapping() {
        return [
            'related-entries' => [
                'databaseFieldName' => 'relation_id',
                'classMemberName' => 'relatedEntryIDs',
                'flag' => self::FLAG_ARRAY | self::FLAG_INT
            ],

            'published' => [
                'flag' => self::FLAG_BOOL
            ],
        ];
    }

```

Flags can be combined using the bitwise OR (`|`) operator. Some flags aren't compatible, or don't make sense to combine, with other flags. The following flags exist:

*FLAG_ARRAY*
Use this when the field has multiple rows of data, like a multi-select. The data
returned will be an array of values (or `databaseFieldName` if it is set). Can combine with `FLAG_INT`, `FLAG_STR`, `FLAG_FLOAT`, `FLAG_BOOL`, `FLAG_CURRENCY`, and `FLAG_NULL`

*FLAG_BOOL*
Converts the data coming out of the database from Yes|No into true|false. When
saving, it is converted back in to a Yes|No value. Can be combined with `FLAG_ARRAY`

*FLAG_FILE*
When pulling out data, in addition to `file`, this will also grab `size`, `mimetype`, and `meta` values. Saving will pass through only `file`. Can only combine with FLAG_NULL

*FLAG_INT*
*FLAG_STR*
*FLAG_FLOAT*
These flags are used to type cast data being pulled out. Can combine with `FLAG_ARRAY`, in which case all items in the array will be cast to that type.

*FLAG_CURRENCY*
Similar to float, however, will limit the result to 2 decimal places.

*FLAG_NULL*
Converts empty values, i.e. int(0), string(""), (array)[] etc, into a `NULL`. Can combine with `FLAG_ARRAY`

### Sorting Results

*FLAG_SORTBY*
@TODO

*FLAG_SORTDESC*
@TODO

*FLAG_SORTASC*
@TODO

### Providing Custom SQL when fetching

It might be necessary to provide custom SQL to use when the class mapper loads an object. To do this, overload the `fetchSQL` method. It should return an SQL string. You can use the $sectionFields array to easily access the ID values of fields in your section. E.g.

```php
    private static function fetchSQL($where = 1)
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
```

Note that overloading the fetchSQL method will mean you need to handle using the correct field mappings.

### Modifying data before saving

There are times when you might need to change data on the fly before it is saved into the entry. You can do this by overloading the `getData` method. For example, you might have a "modified" date field in your section. By overloading the `getData` method, you can ensure it is updated automatically.

```php
    protected function getData()
    {
        $data = parent::getData();

        // Check if anything has changed and, if so, set the new modified date
        if($this->hasBeenModified()) {
          $data["modified"] = "now";
        }

        return $data;
    }
```

### Validation when Saving

@TODO

*FLAG_REQUIRED*
@TODO

*FLAG_ON_SAVE_VALIDATE*
@TODO

*FLAG_ON_SAVE_ENFORCE_MODIFIED*
@TODO

### Creating a custom fetch method

By default, the class mapper gives you `fetchById` and `all` methods. However, you may wish to be able to fetch entries based on other criteria. For example, you may wish to get only published articles. E.g.

```php
    public static function fetchPublished()
    {
        self::findSectionFields();
        $db = SymphonyPDO\Loader::instance();
        $query = $db->prepare(self::fetchSQL(self::findJoinTableFieldName('published') . '.value = :published'));
        $query->bindValue(':published', 'yes', \PDO::PARAM_STR);
        $query->execute();

        return (new SymphonyPDO\Lib\ResultIterator(__CLASS__, $query));
    }
```

The results are loaded into a SymphonyPDO `ResultIterator` object using the current class for each record. The results can be accessed using a `foreach` loop or the `each()` method with a custom function. e.g.

```php
    Article::fetchPublished()->each(function ($article) {
      $article
        ->published(false)
        ->save();
    });
```

## Support

If you believe you have found a bug, please report it using the [GitHub issue tracker](https://github.com/pointybeard/symphony-classmapper/issues),
or better yet, fork the library and submit a pull request.

## Contributing

We encourage you to contribute to this project. Please check out the [Contributing documentation](https://github.com/pointybeard/symphony-classmapper/blob/master/CONTRIBUTING.md) for guidelines about how to get involved.

## License

"Symphony CMS: Section Class Mapper" is released under the [MIT License](http://www.opensource.org/licenses/MIT).
