<?php

declare(strict_types=1);

namespace Symphony\SectionClassMapper\SectionClassMapper\Traits;

/**
 * This trait contains the mandatory member variables used by AbstractModel.
 */
trait hasModelTrait
{
    protected static $sectionFields;
    protected static $fieldMapping = [];
    protected static $section = null;
}
