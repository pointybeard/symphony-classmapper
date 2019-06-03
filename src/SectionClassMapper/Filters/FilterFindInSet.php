<?php

declare(strict_types=1);

namespace Symphony\SectionClassMapper\SectionClassMapper\Filters;

use Symphony\SectionClassMapper\SectionClassMapper;

class FilterFindInSet extends SectionClassMapper\Filter
{
    public function pattern($includeOperator = true): string
    {
        return trim(
            (
                true == $includeOperator
                    ? $this->operator()
                    : null
            ).
            ' find_in_set(cast(%s.%s as char), :%s)'
        );
    }

    public function __construct($field, array $values, string $operator = self::OPERATOR_AND)
    {
        parent::__construct($field, implode(',', array_map('trim', $values)), \PDO::PARAM_STR, $operator);
    }
}
