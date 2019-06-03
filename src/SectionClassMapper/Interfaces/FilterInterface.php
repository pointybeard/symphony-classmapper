<?php

declare(strict_types=1);

namespace Symphony\SectionClassMapper\SectionClassMapper\Interfaces;

interface FilterInterface
{
    // Standard SQL operators
    public const OPERATOR_OR = 'OR';
    public const OPERATOR_AND = 'AND';

    public function toArray(): array;

    public function __toString(): string;
}
