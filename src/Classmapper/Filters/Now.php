<?php

declare(strict_types=1);

namespace pointybeard\Symphony\Classmapper\Filters;

class Now extends Basic
{
    public function __construct(string $field, string $comparisonOperator = self::COMPARISON_OPERATOR_EQ, string $operator = self::OPERATOR_AND)
    {
        parent::__construct($field, null, \PDO::PARAM_STR, $comparisonOperator, $operator);
    }

    protected function pattern(): string
    {
        return '%s.%s '.$this->comparisonOperator().' NOW()';
    }
}
