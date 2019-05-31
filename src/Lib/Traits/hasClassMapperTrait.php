<?php

namespace Symphony\ClassMapper\Lib\Traits;

/**
 * This trait contains the mandatory member variables used by AbstractClassMapper.
 */
trait hasClassMapperTrait
{
    protected static $sectionFields;
    protected static $fieldMapping = [];
    protected static $section = null;
}
