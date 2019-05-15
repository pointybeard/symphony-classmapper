<?php declare(strict_types=1);

namespace Symphony\ClassMapper\ClassMapper\Interfaces;
use Symphony\ClassMapper\ClassMapper;

interface NestedFilterInterface
{
    public function add(ClassMapper\Filter $filter) : ClassMapper\NestedFilter;
    public function filters() : array;
}
