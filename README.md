Symphony Class Mapper
=====================

Maps sections into custom objects, simplifying the process of creating, modifying, deleting and fetching entries in Symphony.

## Installation

Omnipay is installed via [Composer](http://getcomposer.org/). To install, simply add it
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

To use the Class Mapper, simply extend `AbstractClassMapper`. E.g. assuming you have a section called 'articles' with a single field 'title':

```php
    <?php

    namespace Your\Project\Namespace;

    use Symphony\ClassMapper\Lib;

    final class Articles extends Lib\AbstractClassMapper
    {
        const SECTION = 'articles';
        protected static $sectionFields;
    }
```

The only things you must provide are the constant `SECTION` which is the handle of your section, and static member variable `$sectionFields` which holds a mapping of the fields from your section. This gets auto-populated by the parent object. Everything else you get for free.

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
        ->authorId(45)
        ->body("The article body")
        ->date("now")
        ->save();
```

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

The results are loaded into a SymphonyPDO `ResultIterator` object using the current class for each record. The results can be access using a `foreach` loop or the `each()` method with a custom function. e.g.

```php
    $articles->fetchPublished()->each(function ($article) {
      $article
        ->published(false)
        ->save();
    });
```

## Support

If you believe you have found a bug, please report it using the [GitHub issue tracker](https://github.com/pointybeard/symphony-classmapper/issues),
or better yet, fork the library and submit a pull request.
