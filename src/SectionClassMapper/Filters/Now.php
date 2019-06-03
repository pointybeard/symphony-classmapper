<?php

declare(strict_types=1);

namespace Symphony\SectionClassMapper\SectionClassMapper\Filters;

class Now extends Basic
{
    public function __construct(string $field, string $comparisonOperator = self::COMPARISON_OPERATOR_EQ, string $operator = self::OPERATOR_AND)
    {
        parent::__construct($field, null, $comparisonOperator, $operator);
    }

    protected function pattern(): string
    {
        return '%s.%s '.$this->comparisonOperator().' NOW()';
    }
}
