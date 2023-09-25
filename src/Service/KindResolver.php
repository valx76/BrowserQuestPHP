<?php

namespace App\Service;

use App\Enum\KindInterface;
use App\Enum\Kinds\ArmorKind;
use App\Enum\Kinds\ItemKind;
use App\Enum\Kinds\MobKind;
use App\Enum\Kinds\NpcKind;
use App\Enum\Kinds\PlayerKind;
use App\Enum\Kinds\WeaponKind;
use App\Exception\KindNotFoundException;

readonly class KindResolver
{
    /**
     * @throws KindNotFoundException
     */
    public function getKindFromName(string $name): KindInterface
    {
        $possibleArmorKind = ArmorKind::tryFromName($name);
        if ($possibleArmorKind !== null) {
            return $possibleArmorKind;
        }

        $possibleItemKind = ItemKind::tryFromName($name);
        if ($possibleItemKind !== null) {
            return $possibleItemKind;
        }

        $possibleMobKind = MobKind::tryFromName($name);
        if ($possibleMobKind !== null) {
            return $possibleMobKind;
        }

        $possibleNpcKind = NpcKind::tryFromName($name);
        if ($possibleNpcKind !== null) {
            return $possibleNpcKind;
        }

        $possiblePlayerKind = PlayerKind::tryFromName($name);
        if ($possiblePlayerKind !== null) {
            return $possiblePlayerKind;
        }

        $possibleWeaponKind = WeaponKind::tryFromName($name);
        if ($possibleWeaponKind !== null) {
            return $possibleWeaponKind;
        }

        throw new KindNotFoundException(
            sprintf('Kind "%s" not found', $name)
        );
    }

    /**
     * @throws KindNotFoundException
     */
    public function getKindFromValue(int $value): KindInterface
    {
        $possibleArmorKind = ArmorKind::tryFrom($value);
        if ($possibleArmorKind !== null) {
            return $possibleArmorKind;
        }

        $possibleItemKind = ItemKind::tryFrom($value);
        if ($possibleItemKind !== null) {
            return $possibleItemKind;
        }

        $possibleMobKind = MobKind::tryFrom($value);
        if ($possibleMobKind !== null) {
            return $possibleMobKind;
        }

        $possibleNpcKind = NpcKind::tryFrom($value);
        if ($possibleNpcKind !== null) {
            return $possibleNpcKind;
        }

        $possiblePlayerKind = PlayerKind::tryFrom($value);
        if ($possiblePlayerKind !== null) {
            return $possiblePlayerKind;
        }

        $possibleWeaponKind = WeaponKind::tryFrom($value);
        if ($possibleWeaponKind !== null) {
            return $possibleWeaponKind;
        }

        throw new KindNotFoundException(
            sprintf('Kind value "%s" not found', $value)
        );
    }
}