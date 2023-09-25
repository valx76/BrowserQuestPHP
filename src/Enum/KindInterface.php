<?php

namespace App\Enum;

/**
 * @property int $value
 */
interface KindInterface
{
    public static function tryFromName(string $name): ?self;

    public static function fromName(string $name): self;
}