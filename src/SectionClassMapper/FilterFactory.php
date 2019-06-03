<?php

declare(strict_types=1);

namespace Symphony\SectionClassMapper\SectionClassMapper;

use pointybeard\Helpers\Foundation\Factory;

final class FilterFactory extends Factory\AbstractFactory
{
    public static function getTemplateNamespace(): string
    {
        return __NAMESPACE__.'\\Filters\\%s';
    }

    public static function getExpectedClassType(): ?string
    {
        return __NAMESPACE__.'\\AbstractFilter';
    }
}
