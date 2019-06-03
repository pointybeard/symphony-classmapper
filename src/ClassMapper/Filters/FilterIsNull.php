<?php

declare(strict_types=1);

namespace Symphony\ClassMapper\ClassMapper\Filters;

use Symphony\ClassMapper\ClassMapper;

class FilterIsNull extends ClassMapper\Filter
{
    public function pattern($includeOperator = true): string
    {
        return trim((true == $includeOperator ? $this->operator() : null).' %s.%s IS NULL');
    }

    public function __construct(string $field, string $operator = self::OPERATOR_AND)
    {
        parent::__construct($field, null, \PDO::PARAM_STR, $operator);
    }
}
