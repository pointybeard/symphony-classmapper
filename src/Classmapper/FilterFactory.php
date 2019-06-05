<?php

declare(strict_types=1);

namespace pointybeard\Symphony\Classmapper;

use pointybeard\Helpers\Foundation\Factory;

final class FilterFactory extends Factory\AbstractFactory
{
    public function getTemplateNamespace(): string
    {
        return __NAMESPACE__.'\\Filters\\%s';
    }

    public function getExpectedClassType(): ?string
    {
        return __NAMESPACE__.'\\AbstractFilter';
    }
}
