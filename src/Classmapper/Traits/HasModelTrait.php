<?php

declare(strict_types=1);

namespace pointybeard\Symphony\Classmapper\Traits;

/**
 * This trait contains the mandatory member variables set and used by
 * AbstractModel. They cannot reside in AbstractModel since they are static and
 * unique to that one specific section.
 */
trait HasModelTrait
{
    protected static $sectionFields;
    protected static $fieldMapping = [];
    protected static $section = null;
}
