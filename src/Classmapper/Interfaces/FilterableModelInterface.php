<?php

declare(strict_types=1);

namespace pointybeard\Symphony\Classmapper\Interfaces;

use pointybeard\Symphony\Classmapper;

interface FilterableModelInterface
{
    public function appendFilter(Classmapper\AbstractFilter $filter): Classmapper\AbstractModel;

    public function clearFilters(): void;

    public function getFilters(): array;

    public function filter(): ResultIterator;

    public static function fetch(?Classmapper\AbstractFilter ...$filters): \SymphonyPDO\Lib\ResultIterator;
}
