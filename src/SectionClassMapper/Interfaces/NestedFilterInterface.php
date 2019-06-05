<?php

declare(strict_types=1);

namespace Symphony\SectionClassMapper\SectionClassMapper\Interfaces;

use Symphony\SectionClassMapper\SectionClassMapper;

interface NestedFilterInterface
{
    public function add(SectionClassMapper\AbstractFilter $filter): SectionClassMapper\NestedFilter;

    public function filters(): array;
}
