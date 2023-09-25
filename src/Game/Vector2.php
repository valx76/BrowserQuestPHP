<?php

namespace App\Game;

final class Vector2
{
    public function __construct(
        public int $x,
        public int $y
    )
    {
    }

    public function isEqualTo(Vector2 $other): bool
    {
        return $this->x === $other->x
            && $this->y === $other->y;
    }

    public function distanceTo(Vector2 $other): int
    {
        $distX = abs($this->x - $other->x);
        $distY = abs($this->y - $other->y);

        return ($distX > $distY) ? $distX : $distY;
    }
}