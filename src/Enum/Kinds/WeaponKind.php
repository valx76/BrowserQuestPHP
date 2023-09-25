<?php

namespace App\Enum\Kinds;

use App\Enum\KindInterface;
use App\Enum\KindTrait;

enum WeaponKind: int implements KindInterface
{
    use KindTrait;

    case SWORD1 = 60;
    case SWORD2 = 61;
    case REDSWORD = 62;
    case GOLDENSWORD = 63;
    case MORNINGSTAR = 64;
    case AXE = 65;
    case BLUESWORD = 66;

    public function getLevel(): int
    {
        return match($this) {
            self::SWORD1 => 1,
            self::SWORD2 => 2,
            self::REDSWORD => 6,
            self::GOLDENSWORD => 7,
            self::MORNINGSTAR => 4,
            self::AXE => 3,
            self::BLUESWORD => 5,

            default => throw new \RuntimeException('Cannot happen!')
        };
    }
}
