<?php

declare(strict_types=1);

namespace Symphony\SectionClassMapper\SectionClassMapper\Traits;

use pointybeard\Helpers\Functions\Flags;

trait HasSortableModelTrait
{
    protected static $fetchSqlTemplate = 'SELECT SQL_CALC_FOUND_ROWS e.id as `id`, %s
        FROM `tbl_entries` AS `e` %s
        WHERE e.section_id = %d AND %s
        GROUP BY e.id ORDER BY %%s.%%s %%s
    ';

    private static function findSortField(): ?string
    {
        foreach (static::getCustomFieldMapping() as $field => $mapping) {
            if (isset($mapping['flags']) && Flags\is_flag_set($mapping['flags'], self::FLAG_SORTBY)) {
                return $field;
            }
        }

        return null;
    }

    private static function findSortDirection(?string $sortByField): string
    {
        if (null === $sortByField) {
            $sortByField = self::findSortField();
        }

        $fieldMappings = static::getCustomFieldMapping();
        $direction = self::SORT_DIRECTION_ASC;

        if (isset($fieldMappings[$sortByField]['flags']) && Flags\is_flag_set($fieldMappings[$sortByField]['flags'], self::FLAG_SORTDESC)) {
            $direction = self::SORT_DIRECTION_DESC;
        }

        return $direction;
    }

    protected static function fetchSQL(string $where = null): string
    {
        // Default is to sort by entry id
        $join = 'e';
        $column = 'id';

        if (null != ($sortBy = self::findSortField())) {
            $join = self::findJoinTableFieldName($sortBy);
            $column = self::findDatabaseFieldName($sortBy);
        }

        return sprintf(
            parent::fetchSQL($where),
            $join,
            $column,
            self::findSortDirection($sortBy)
        );
    }
}
