<?php

declare(strict_types=1);

namespace pointybeard\Symphony\Classmapper\Interfaces;

interface FilterInterface
{
    // Standard SQL operators
    public const OPERATOR_OR = 'OR';
    public const OPERATOR_AND = 'AND';

    public function toArray(): array;

    public function __toString(): string;
}
