<?php

declare(strict_types=1);

namespace Symphony\SectionClassMapper\SectionClassMapper\Traits;

use SymphonyPDO;
use Symphony\SectionClassMapper\SectionClassMapper;

trait HasFilterableModelTrait
{
    protected $filters = [];

    protected $filteredResultIterator = null;

    /**
     * Add a filter to this model. When filter() is called, these filters
     * are used.
     *
     * @param SectionClassMapper\AbstractFilter $filter
     *
     * @return self return self to support method chaining
     */
    public function appendFilter(SectionClassMapper\AbstractFilter $filter): self
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
     * @return SymphonyPDO\Lib\ResultIterator The result from calling fetch()
     */
    public function filter(): SymphonyPDO\Lib\ResultIterator
    {
        if (!($this->filteredResultIterator instanceof SymphonyPDO\Lib\ResultIterator)) {
            $this->filteredResultIterator = self::fetch($this->filters);
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
     * @param array $filters an array of SectionClassMapper\AbstractFilter objects
     *
     * @return SymphonyPDOLibResultIterator
     */
    final public static function fetch(?array $filters = null): SymphonyPDO\Lib\ResultIterator
    {
        static::findSectionFields();

        for ($ii = 0; $ii < count($filters); ++$ii) {
            if (!($filters[$ii] instanceof SectionClassMapper\AbstractFilter)) {
                list($fieldName, $value) = $filters[$ii];
                $filters[$ii] = new Filter(
                    $fieldName,
                    $value,
                    isset($filters[$ii][2]) ? $filters[$ii][2] : \PDO::PARAM_STR,
                    isset($filters[$ii][3]) ? $filters[$ii][3] : SectionClassMapper\Filter::OPERATOR_AND,
                    isset($filters[$ii][4]) ? $filters[$ii][4] : SectionClassMapper\Filter::COMPARISON_OPERATOR_EQ
                );
            }
        }

        $where = null;
        $params = [];

        if (!empty($filters)) {
            $where = [];

            foreach ($filters as $index => $f) {
                if ($f instanceof SectionClassMapper\NestedFilter) {
                    $where[] = (count($where) > 0 ? $f->operator() : '').'  (';

                    $first = true;
                    foreach ($f->filters() as $ii => $ff) {
                        $mapping = (object) static::findCustomFieldMapping($ff->field());
                        $where[] = sprintf(
                            $ff->pattern(!$first),
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
                        $f->pattern(
                            empty($where)
                                ? SectionClassMapper\AbstractFilter::FLAG_PATTERN_EXCLUDE_OPERATOR
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

        return new SymphonyPDO\Lib\ResultIterator(static::class, $query);
    }
}
