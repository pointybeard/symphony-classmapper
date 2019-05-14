<?php declare(strict_types=1);

namespace Symphony\ClassMapper\ClassMapper\Interfaces;

interface FilterInterface
{
    public function operator() : string;
    public function toArray() : array;
    public function pattern($includeOperator=true) : string;
}
