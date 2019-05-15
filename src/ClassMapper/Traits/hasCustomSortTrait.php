<?php declare(strict_types=1);

namespace Symphony\ClassMapper\ClassMapper\Traits;

use SymphonyPDO;
use Symphony\ClassMapper\ClassMapper;
use pointybeard\Helpers\Functions\Flags;

trait hasCustomSortTrait
{

    private static function findSortField() : string
    {
        foreach (self::getCustomFieldMapping() as $field => $mapping) {
            if (isset($mapping['flags']) && Flags\is_flag_set($mapping['flags'], self::FLAG_SORTBY)) {
                return $field;
            }
        }

        return null;
    }

    private static function findSortDirection(string $sortByField=null) : string
    {
        if (is_null($sortByField)) {
            $sortByField = self::findSortField();
        }

        $fieldMappings = self::getCustomFieldMapping();
        $direction = self::SORT_DIRECTION_ASC;

        if (isset($fieldMappings[$sortByField]['flags']) && Flags\is_flag_set($fieldMappings[$sortByField]['flags'], self::FLAG_SORTDESC)) {
            $direction = self::SORT_DIRECTION_DESC;
        }

        return $direction;
    }

    private static function changeSortingMethod(string $sql, string $field, int $direction=self::FLAG_SORTASC) : string
    {
        return preg_replace(
            '@ORDER BY e.id ASC$@i',
            sprintf(
                "ORDER BY %s.value %s",
                self::findJoinTableFieldName($field),
                $direction
            ),
            $sql
        );
    }

    protected static function fetchSQL(string $where = null) : string
    {
        $sql = parent::fetchSQL($where);
        $sortBy = self::findSortField();
        return ($sortBy !== false)
            ? static::changeSortingMethod($sql, $sortBy, self::findSortDirection($sortBy))
            : $sql
        ;
    }
}
