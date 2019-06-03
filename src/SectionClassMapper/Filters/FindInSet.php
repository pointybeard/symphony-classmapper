<?php

declare(strict_types=1);

namespace Symphony\SectionClassMapper\SectionClassMapper\Filters;

use Symphony\SectionClassMapper\SectionClassMapper;

class FindInSet extends SectionClassMapper\AbstractFilter
{
    public function __construct($field, array $values, string $operator = self::OPERATOR_AND)
    {
        parent::__construct(
            $field,
            implode(',', array_map('trim', $values)),
            \PDO::PARAM_STR,
            $operator
        );
    }

    protected function pattern(): string
    {
        return 'find_in_set(cast(%s.%s as char), :%s)';
    }
}
