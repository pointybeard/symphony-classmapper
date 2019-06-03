<?php

declare(strict_types=1);

namespace Symphony\SectionClassMapper\SectionClassMapper\Filters;

use Symphony\SectionClassMapper\SectionClassMapper;

class IsNull extends SectionClassMapper\AbstractFilter
{
    protected function pattern(): string
    {
        return '%s.%s IS NULL';
    }

    public function __construct(string $field, string $operator = self::OPERATOR_AND)
    {
        parent::__construct($field, null, \PDO::PARAM_STR, $operator);
    }
}
