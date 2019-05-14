<?php declare(strict_types=1);

namespace Symphony\ClassMapper\ClassMapper\Interfaces;

interface NestedFilterInterface
{
    public function add(Filter $filter) : self;
    public function filters() : array;
}
