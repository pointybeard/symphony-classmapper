<?php

declare(strict_types=1);

namespace Symphony\SectionClassMapper\SectionClassMapper\Interfaces;

use Symphony\SectionClassMapper\SectionClassMapper;
use SymphonyPDO\Lib\ResultIterator;

interface FilterableModelInterface
{
    public function appendFilter(SectionClassMapper\AbstractFilter $filter): SectionClassMapper\AbstractModel;

    public function clearFilters(): void;

    public function getFilters(): array;

    public function filter(): ResultIterator;

    public static function fetch(?array $filters = null): ResultIterator;
}
