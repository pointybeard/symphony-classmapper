<?php

declare(strict_types=1);

namespace pointybeard\Symphony\Classmapper\Filters;

class IsNotNull extends IsNull
{
    protected function pattern(): string
    {
        return '%s.%s IS NOT NULL';
    }
}
