<?php declare(strict_types=1);

namespace Symphony\ClassMapper\ClassMapper;

class NestedFilter extends AbstractFilter implements Interfaces\NestedFilterInterface
{
    private $filters = [];

    public function __construct($operator=self::OPERATOR_AND)
    {
        parent::__construct($operator);
    }

    public function add(Filter $filter) : self
    {
        $this->filters[] = $filter;
        return $this;
    }

    public function filters() : array
    {
        return $this->filters;
    }

    public function toArray() : array
    {
        $result = [];

        foreach ($this->filters as $f) {
            $result[] = $f->toArray();
        }

        return $result;
    }

    public function pattern($includeOperator=true) : string
    {
        $result = " (%s )";
        $patterns = "";

        $first = true;
        foreach ($this->filters as $f) {
            $patterns .= " " . $f->pattern(!$first);
            $first = false;
        }

        return trim(
            (
                $includeOperator == true
                ? $this->operator()
                : null
            ) . sprintf($result, $patterns)
        );
    }
}
