<?php

declare(strict_types=1);

namespace pointybeard\Symphony\Classmapper\Interfaces;

use pointybeard\Symphony\Classmapper;

interface NestedFilterInterface
{
    public function add(Classmapper\AbstractFilter $filter): Classmapper\NestedFilter;

    public function filters(): array;
}
