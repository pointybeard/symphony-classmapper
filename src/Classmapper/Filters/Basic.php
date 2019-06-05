<?php

declare(strict_types=1);

namespace pointybeard\Symphony\Classmapper\Filters;

use pointybeard\Symphony\Classmapper;

class Basic extends Classmapper\AbstractFilter
{
    public const COMPARISON_OPERATOR_EQ = '=';
    public const COMPARISON_OPERATOR_NEQ = '<>';
    public const COMPARISON_OPERATOR_GT = '>';
    public const COMPARISON_OPERATOR_GTEQ = '>=';
    public const COMPARISON_OPERATOR_LT = '<';
    public const COMPARISON_OPERATOR_LTEQ = '<=';
    public const COMPARISON_OPERATOR_LIKE = 'LIKE';
    public const COMPARISON_OPERATOR_NOT_LIKE = 'NOT LIKE';

    protected $comparisonOperator = null;

    protected function pattern(): string
    {
        // FIELD.COLUMN COMPARISON :VALUE
        return '%s.%s '.$this->comparisonOperator().' :%s';
    }

    public function __construct(string $field, $value, int $type = \PDO::PARAM_STR, string $comparisonOperator = self::COMPARISON_OPERATOR_EQ, string $operator = self::OPERATOR_AND)
    {
        $this->comparisonOperator($comparisonOperator);
        parent::__construct($field, $value, $type, $operator);
    }
}
