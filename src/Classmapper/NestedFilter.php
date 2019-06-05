<?php

declare(strict_types=1);

namespace pointybeard\Symphony\Classmapper;

class NestedFilter extends AbstractFilter implements Interfaces\NestedFilterInterface
{
    private $filters = [];

    public function __construct(string $operator = self::OPERATOR_AND)
    {
        parent::__construct('NESTED', null, \PDO::PARAM_STR, $operator);
    }

    public function add(AbstractFilter $filter): self
    {
        $this->filters[] = $filter;

        return $this;
    }

    public function filters(): array
    {
        return $this->filters;
    }

    public function toArray(): array
    {
        $result = [];

        foreach ($this->filters as $f) {
            $result[] = $f->toArray();
        }

        return $result;
    }

    protected function pattern(): string
    {
        return '(%s)';
    }
}
