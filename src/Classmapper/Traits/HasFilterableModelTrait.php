<?php

declare(strict_types=1);

namespace pointybeard\Symphony\Classmapper\Traits;

use SymphonyPDO;
use pointybeard\Symphony\Classmapper;

trait HasFilterableModelTrait
{
    protected $filters = [];

    protected $filteredResultIterator = null;

    /**
     * Add a filter to this model. When filter() is called, these filters
     * are used.
     *
     * @param Classmapper\AbstractFilter $filter
     *
     * @return self return self to support method chaining
     */
    public function appendFilter(Classmapper\AbstractFilter $filter): Classmapper\AbstractModel
    {
        $this->filters[] = $filter;

        return $this;
    }

    /**
     * Removes all filters from this model instance.
     */
    public function clearFilters(): void
    {
        $this->filters = [];
        $this->filteredResultIterator = null;
    }

    /**
     * Returns array containing filters associated with this instance of
     * AbstractModel.
     */
    public function getFilters(): array
    {
        return $this->filters;
    }

    /**
     * Calls fetch(), passing it any filters that have been added with
     * appendFilter(). The result is cached in $this->filteredResultIterator
     * and can be cleared by calling clearFilters().
     *
     * @return \Iterator The result from calling fetch()
     */
    public function filter(): \Iterator
    {
        if (!($this->filteredResultIterator instanceof \Iterator)) {
            $this->filteredResultIterator = self::fetch(...$this->filters);
        }
        $this->filteredResultIterator->rewind();

        return $this->filteredResultIterator;
    }

    /**
     * Static method for fetching entries. Accepts an array of Filter objects
     * which are used when constructing the SQL. Allows filtering of entries
     * without the need to create an instance of the model first (as is
     * necessary when calling filter()).
     *
     * @param array $filters an array of Classmapper\AbstractFilter objects
     *
     * @return \Iterator
     */
    final public static function fetch(?Classmapper\AbstractFilter ...$filters): \Iterator
    {
        static::findSectionFields();

        $where = null;
        $params = [];

        if (!empty($filters)) {
            $where = [];

            foreach ($filters as $index => $f) {
                if ($f instanceof Classmapper\NestedFilter) {
                    $where[] = (count($where) > 0 ? $f->operator() : '').'  (';

                    $first = true;
                    foreach ($f->filters() as $ii => $ff) {
                        $mapping = (object) static::findCustomFieldMapping($ff->field());
                        $where[] = sprintf(
                            $ff->getPattern(
                                true == $first
                                    ? Classmapper\AbstractFilter::FLAG_PATTERN_EXCLUDE_OPERATOR
                                    : null
                            ),
                            $mapping->joinTableName,
                            isset($mapping->databaseFieldName)
                                ? $mapping->databaseFieldName
                                : 'value',
                            "{$mapping->joinTableName}_{$index}_{$ii}"
                        );

                        if (null !== $ff->value()) {
                            $params["{$mapping->joinTableName}_{$index}_{$ii}"] = [
                                'value' => $ff->value(),
                                'type' => $ff->type(),
                            ];
                        }

                        $first = false;
                    }

                    $where[] = ')';
                } else {
                    $mapping = (object) static::findCustomFieldMapping($f->field());
                    $where[] = sprintf(
                        $f->getPattern(
                            empty($where)
                                ? Classmapper\AbstractFilter::FLAG_PATTERN_EXCLUDE_OPERATOR
                                : null
                        ),
                        $mapping->joinTableName,
                        isset($mapping->databaseFieldName)
                            ? $mapping->databaseFieldName
                            : 'value',
                        "{$mapping->joinTableName}_{$index}"
                    );

                    if (null !== $f->value()) {
                        $params["{$mapping->joinTableName}_{$index}"] = [
                            'value' => $f->value(),
                            'type' => $f->type(),
                        ];
                    }
                }
            }

            $where = implode(' ', $where);
        }

        // If there aren't any filters, $where will still be null
        $query = self::getDatabaseConnection()->prepare(static::fetchSQL($where));

        // If there aren't any filters, $params will be an empty array
        foreach ($params as $name => &$p) {
            $query->bindParam($name, $p['value'], $p['type']);
        }

        $query->execute();

        return new self::$resultContainerClass(static::class, $query);
    }
}
