<?php

declare(strict_types=1);

namespace pointybeard\Symphony\Classmapper\Interfaces;

use pointybeard\Symphony\Classmapper;
use SymphonyPDO\Lib\ResultIterator;

interface FilterableModelInterface
{
    public function appendFilter(ClassmapperAbstractFilter $filter): Classmapper\AbstractModel;

    public function clearFilters(): void;

    public function getFilters(): array;

    public function filter(): ResultIterator;

    public static function fetch(?array $filters = null): ResultIterator;
}