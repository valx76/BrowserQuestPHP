<?php

namespace App\Enum;

enum Orientation: int
{
    case UP = 1;
    CASE DOWN = 2;
    CASE LEFT = 3;
    CASE RIGHT = 4;

    public static function random(): self
    {
        return self::from(
            rand(1, 4)
        );
    }
}
