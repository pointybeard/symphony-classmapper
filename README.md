# Symphony Section Class Mapper

- Version: v0.1.1
- Date: June 21 2016
- [Release notes](https://github.com/pointybeard/symphony-classmapper/blob/master/CHANGELOG.md)
- [GitHub repository](https://github.com/pointybeard/symphony-classmapper)

[![Latest Stable Version](https://poser.pugx.org/pointybeard/symphony-classmapper/version)](https://packagist.org/packages/pointybeard/symphony-classmapper) [![License](https://poser.pugx.org/pointybeard/symphony-classmapper/license)](https://packagist.org/packages/pointybeard/symphony-classmapper)

Maps sections into custom objects, simplifying the process of creating, modifying, deleting and fetching entries in Symphony.

## Installation

Symphony Class Mapper is installed via [Composer](http://getcomposer.org/). To install, simply add it
to your `composer.json` file:

```json
{
    "require": {
        "pointybeard/symphony-classmapper": "~0.1"
    }
}
```

And run composer to update your dependencies:

    $ curl -s http://getcomposer.org/installer | php
    $ php composer.phar update

## Usage

To use the Class Mapper, simply extend `AbstractClassMapper`, and use `Trait\hasClassMapperTrait`. E.g. assuming you have a section called 'articles' with a single field 'title':

```php
    <?php

    namespace Your\Project\Namespace;

    use Symphony\ClassMapper\Lib;

    final class Article extends Lib\AbstractClassMapper
    {
        use Lib\Traits\hasClassMapperTrait;
    }
```

The trait `hasClassMapperTrait` provides three static member variables: `$sectionFields`, `$fieldMapping` and `$section`. They are used internally and hold a mapping to the Symphony section and the fields from that section. All are auto-populated by the parent object.

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

The class mapper assumes all fields have a `value` field in the database. This is not true for every field though. For example, a Select Box Link field has a `relation_id` instead of a `value`. In this situation you must tell the class mapper how the field should be mapped. Using the Article example from above, lets assume there is now a field called "Author" which is a Select Box Link field pointing to the "Authors" section.

We want to get the Author's ID, and also the Author model if it exists.

```php
    # Create a mapping for the Author field, mapping the id to 'authorId'
    protected static $fieldMapping = [
        'author' => [
            'databaseFieldName' => 'relation_id',
            'classMemberName' => 'authorId'
        ],
    ];

    # Create a method that allows easy retrieval of an Author object.
    # This assumes the Author class map exists.
    public function author() {
        return Author::fetchFromId($this->authorId);
    }
```

You can see how we quickly wired up the Articles object to know about Authors and how to retrieve them.

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

Note that overloading the fetchSQL method will ignore any field mappings you might have.

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

Another example might be a checkbox field. The value is normally 'yes' or 'no', however with custom SQL you could make it appear as `true` or `false` in the object. When saving, this needs to be converted to a yes|no value instead. E.g.

```php
    protected function getData()
    {
        $data = parent::getData();
        $data["published"] = (
            $this->published == true || strtolower($this->published) == 'yes'
                ? "Yes"
                : "No"
        );
        return $data;
    }
```

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

"Symphony Section Class Mapper" is released under the [MIT License](http://www.opensource.org/licenses/MIT).
