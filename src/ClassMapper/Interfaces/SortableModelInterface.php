<?php

declare(strict_types=1);

namespace Symphony\ClassMapper\ClassMapper\Interfaces;

interface SortableModelInterface
{
    public const SORT_DIRECTION_ASC = 'asc';
    public const SORT_DIRECTION_DESC = 'desc';

    public const FLAG_SORTBY = 0x0100;
    public const FLAG_SORTDESC = 0x200;
    public const FLAG_SORTASC = 0x0400;
}
