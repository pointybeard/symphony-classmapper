<?php declare(strict_types=1);

namespace Symphony\ClassMapper\ClassMapper\Interfaces;

interface FilterInterface
{
    const OPERATOR_OR = "OR";
    const OPERATOR_AND = "AND";

    public function operator() : string;
    public function toArray() : array;
    public function pattern($includeOperator=true) : string;
}
