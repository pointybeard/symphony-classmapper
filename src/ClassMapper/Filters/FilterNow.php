<?php declare(strict_types=1);

namespace Symphony\ClassMapper\ClassMapper\Filters;

use Symphony\ClassMapper\ClassMapper;

class FilterNow extends ClassMapper\Filter
{
    public function pattern($includeOperator=true) : string
    {
        return trim(($includeOperator == true ? $this->operator() : null) . ' %s.%s ' . $this->comparisonOperator . ' NOW()');
    }

    public function __construct(string $field, int $operator=self::OPERATOR_AND, int $comparisonOperator=self::COMPARISON_OPERATOR_EQ)
    {
        parent::__construct($field, null, \PDO::PARAM_STR, $operator, $comparisonOperator);
    }
}
