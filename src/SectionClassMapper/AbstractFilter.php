<?php

declare(strict_types=1);

namespace Symphony\SectionClassMapper\SectionClassMapper;

abstract class AbstractFilter implements Interfaces\FilterInterface
{
    const OPERATOR_OR = 'OR';
    const OPERATOR_AND = 'AND';

    public $operator;

    public function __construct(string $operator = self::OPERATOR_AND)
    {
        $this->operator = $operator;

        if (self::OPERATOR_AND != $this->operator && self::OPERATOR_OR != $this->operator) {
            throw new \Exception("Invalid filter operator '{$this->operator}' specified.");
        }
    }

    public function toArray(): array
    {
        return [
            'operator' => $this->operator,
        ];
    }

    public function operator(): string
    {
        return $this->operator;
    }
}