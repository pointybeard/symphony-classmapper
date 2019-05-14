<?php declare(strict_types=1);

namespace Symphony\ClassMapper\ClassMapper\Filters;

class FilterIsNotNull extends FilterIsNull
{
    public function pattern($includeOperator=true) : string
    {
        return trim(($includeOperator == true ? $this->operator() : null) . ' %s.%s IS NOT NULL');
    }
}
