<?php declare(strict_types=1);

namespace Symphony\ClassMapper\ClassMapper\Traits;

/**
 * This trait contains the mandatory member variables used by AbstractModel
 */
trait hasClassMapperTrait
{
    protected static $sectionFields;
    protected static $fieldMapping = [];
    protected static $section = null;
}
