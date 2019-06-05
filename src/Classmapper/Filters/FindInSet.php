<?php

declare(strict_types=1);

namespace pointybeard\Symphony\Classmapper\Filters;

use pointybeard\Symphony\Classmapper;

class FindInSet extends Classmapper\AbstractFilter
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
