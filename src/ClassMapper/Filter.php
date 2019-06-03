<?php

declare(strict_types=1);

namespace Symphony\ClassMapper\ClassMapper;

class Filter extends AbstractFilter
{
    const COMPARISON_OPERATOR_EQ = '=';
    const COMPARISON_OPERATOR_NEQ = '<>';
    const COMPARISON_OPERATOR_GT = '>';
    const COMPARISON_OPERATOR_GTEQ = '>=';
    const COMPARISON_OPERATOR_LT = '<';
    const COMPARISON_OPERATOR_LTEQ = '<=';
    const COMPARISON_OPERATOR_LIKE = 'LIKE';
    const COMPARISON_OPERATOR_NOT_LIKE = 'NOT LIKE';

    public function __construct($field, $value, $type = \PDO::PARAM_STR, $operator = self::OPERATOR_AND, $comparisonOperator = self::COMPARISON_OPERATOR_EQ)
    {
        parent::__construct($operator);

        $this->field = $field;
        $this->value = $value;
        $this->type = $type;
        $this->comparisonOperator = $comparisonOperator;

        if (!in_array($this->comparisonOperator, [
            self::COMPARISON_OPERATOR_EQ,
            self::COMPARISON_OPERATOR_NEQ,
            self::COMPARISON_OPERATOR_GT,
            self::COMPARISON_OPERATOR_GTEQ,
            self::COMPARISON_OPERATOR_LT,
            self::COMPARISON_OPERATOR_LTEQ,
            self::COMPARISON_OPERATOR_LIKE,
            self::COMPARISON_OPERATOR_NOT_LIKE,
        ])) {
            throw new \Exception("Invalid filter comparison operator '{$this->comparisonOperator}' specified.");
        }

        if (!in_array($this->type, [
            \PDO::PARAM_BOOL,
            \PDO::PARAM_NULL,
            \PDO::PARAM_INT,
            \PDO::PARAM_STR,
            \PDO::PARAM_LOB,
            \PDO::PARAM_STMT,
            \PDO::PARAM_INPUT_OUTPUT,
        ])) {
            throw new \Exception("Invalid filter value type '{$this->type}' specified.");
        }
    }

    public function field(): string
    {
        return $this->field;
    }

    public function value()
    {
        return $this->value;
    }

    public function type(): int
    {
        return $this->type;
    }

    public function comparisonOperator(): string
    {
        return $this->comparisonOperator;
    }

    public function toArray(): array
    {
        return array_merge(parent::toArray(), [
            'field' => $this->field,
            'value' => $this->value,
            'type' => $this->type,
            'comparisonOperator' => $this->comparisonOperator,
        ]);
    }

    public function pattern($includeOperator = true): string
    {
        return trim((true == $includeOperator ? $this->operator() : null).' %s.%s '.$this->comparisonOperator.' :%s');
    }
}
