<?php

namespace App\Enum\Kinds;

use App\Enum\KindInterface;
use App\Enum\KindTrait;

enum ArmorKind: int implements KindInterface
{
    use KindTrait;

    case FIREFOX = 20;
    case CLOTHARMOR = 21;
    case LEATHERARMOR = 22;
    case MAILARMOR = 23;
    case PLATEARMOR = 24;
    case REDARMOR = 25;
    case GOLDENARMOR = 26;

    public function getLevel(): int
    {
        return match($this) {
            self::FIREFOX => 0,
            self::CLOTHARMOR => 1,
            self::LEATHERARMOR => 2,
            self::MAILARMOR => 3,
            self::PLATEARMOR => 4,
            self::REDARMOR => 5,
            self::GOLDENARMOR => 6,

            default => throw new \RuntimeException('Cannot happen!')
        };
    }
}
