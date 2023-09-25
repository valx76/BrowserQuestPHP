<?php

namespace App\Game;

class Formulas
{
    public static function dmg(int $weaponLevel, int $armorLevel): int
    {
        $dealt = $weaponLevel * rand(5, 10);
        $absorbed = $armorLevel * rand(1, 3);
        $dmg = $dealt - $absorbed;

        if ($dmg <= 0) {
            return rand(0, 3);
        }

        return $dmg;
    }

    public static function hp(int $armorLevel): int
    {
        return 80 + (($armorLevel - 1) * 30);
    }
}