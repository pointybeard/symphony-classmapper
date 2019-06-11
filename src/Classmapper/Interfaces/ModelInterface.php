<?php

declare(strict_types=1);

namespace pointybeard\Symphony\Classmapper\Interfaces;

use pointybeard\Symphony\Classmapper;
use XMLElement;

interface ModelInterface
{
    public const FLAG_ARRAY = 0x0001;
    public const FLAG_BOOL = 0x0002;
    public const FLAG_FILE = 0x0004;
    public const FLAG_INT = 0x0008;
    public const FLAG_STR = 0x0010;
    public const FLAG_FLOAT = 0x0020;
    public const FLAG_CURRENCY = 0x0040;
    public const FLAG_NULL = 0x0080;

    public const FLAG_REQUIRED = 0x0800;

    public const FLAG_ON_SAVE_VALIDATE = 0x1000;
    public const FLAG_ON_SAVE_ENFORCE_MODIFIED = 0x2000;

    public function validate(): bool;

    public function toXml(XMLElement $container = null): XMLElement;

    public function delete();

    public function save(?int $flags = self::FLAG_ON_SAVE_VALIDATE, string $sectionHandle = null): Classmapper\AbstractModel;

    public function flagAsModified(): void;

    public function flagAsNotModified(): void;

    public function hasBeenModified(): bool;

    public function toArray(): array;

    public function getSectionHandle(): ?string;
}
