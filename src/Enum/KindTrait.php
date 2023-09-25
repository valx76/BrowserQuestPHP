<?php

namespace App\Enum;

use App\Exception\KindNotFoundException;

trait KindTrait
{
    public static function tryFromName(string $name): ?self
    {
        foreach (self::cases() as $case) {
            if (strtoupper($case->name) === strtoupper($name)) {
                return $case;
            }
        }

        return null;
    }

    /**
     * @throws KindNotFoundException
     */
    public static function fromName(string $name): self
    {
        foreach (self::cases() as $case) {
            if (strtoupper($case->name) === strtoupper($name)) {
                return $case;
            }
        }

        throw new KindNotFoundException(
            sprintf('Kind "%s" not found', $name)
        );
    }
}