<?php

namespace App\Enum\Kinds;

use App\Enum\KindInterface;
use App\Enum\Kinds\ArmorKind;
use App\Enum\Kinds\ItemKind;
use App\Enum\KindTrait;
use App\Exception\KindNotFoundException;
use App\Enum\Kinds\WeaponKind;

enum MobKind: int implements KindInterface
{
    use KindTrait;

    case RAT = 2;
    case SKELETON = 3;
    case GOBLIN = 4;
    case OGRE = 5;
    case SPECTRE = 6;
    case CRAB = 7;
    case BAT = 8;
    case WIZARD = 9;
    case EYE = 10;
    case SNAKE = 11;
    case SKELETON2 = 12;
    case BOSS = 13;
    case DEATHKNIGHT = 14;

    /**
     * @return array{drops: array, hp: int, armor: int, weapon: int}
     */
    public function getProperties(): array
    {
        return match($this) {
            self::RAT => [
                'drops' => [
                    ItemKind::FLASK->name => 40,
                    ItemKind::BURGER->name => 10,
                    ItemKind::FIREPOTION->name => 5,
                ],
                'hp' => 25,
                'armor' => 1,
                'weapon' => 1
            ],

            self::SKELETON => [
                'drops' => [
                    ItemKind::FLASK->name => 40,
                    ArmorKind::MAILARMOR->name => 10,
                    WeaponKind::AXE->name => 20,
                    ItemKind::FIREPOTION->name => 5
                ],
                'hp' => 110,
                'armor' => 2,
                'weapon' => 2
            ],

            self::GOBLIN => [
                'drops' => [
                    ItemKind::FLASK->name => 50,
                    ArmorKind::LEATHERARMOR->name => 20,
                    WeaponKind::AXE->name => 10,
                    ItemKind::FIREPOTION->name => 5
                ],
                'hp' => 90,
                'armor' => 2,
                'weapon' => 1
            ],

            self::OGRE => [
                'drops' => [
                    ItemKind::BURGER->name => 10,
                    ItemKind::FLASK->name => 50,
                    ArmorKind::PLATEARMOR->name => 20,
                    WeaponKind::MORNINGSTAR->name => 20,
                    ItemKind::FIREPOTION->name => 5
                ],
                'hp' => 200,
                'armor' => 3,
                'weapon' => 2
            ],

            self::SPECTRE => [
                'drops' => [
                    ItemKind::FLASK->name => 30,
                    ArmorKind::REDARMOR->name => 40,
                    WeaponKind::REDSWORD->name => 30,
                    ItemKind::FIREPOTION->name => 5
                ],
                'hp' => 250,
                'armor' => 2,
                'weapon' => 4
            ],

            self::CRAB => [
                'drops' => [
                    ItemKind::FLASK->name => 50,
                    ArmorKind::LEATHERARMOR->name => 10,
                    WeaponKind::AXE->name => 20,
                    ItemKind::FIREPOTION->name => 5
                ],
                'hp' => 60,
                'armor' => 2,
                'weapon' => 1
            ],

            self::BAT => [
                'drops' => [
                    ItemKind::FLASK->name => 50,
                    WeaponKind::AXE->name => 10,
                    ItemKind::FIREPOTION->name => 5
                ],
                'hp' => 80,
                'armor' => 2,
                'weapon' => 1
            ],

            self::WIZARD => [
                'drops' => [
                    ItemKind::FLASK->name => 50,
                    ArmorKind::PLATEARMOR->name => 20,
                    ItemKind::FIREPOTION->name => 5
                ],
                'hp' => 100,
                'armor' => 2,
                'weapon' => 6
            ],

            self::EYE => [
                'drops' => [
                    ItemKind::FLASK->name => 50,
                    ArmorKind::REDARMOR->name => 20,
                    WeaponKind::REDSWORD->name => 10,
                    ItemKind::FIREPOTION->name => 5
                ],
                'hp' => 200,
                'armor' => 3,
                'weapon' => 3
            ],

            self::SNAKE => [
                'drops' => [
                    ItemKind::FLASK->name => 50,
                    ArmorKind::MAILARMOR->name => 10,
                    WeaponKind::MORNINGSTAR->name => 10,
                    ItemKind::FIREPOTION->name => 5
                ],
                'hp' => 150,
                'armor' => 3,
                'weapon' => 2
            ],

            self::SKELETON2 => [
                'drops' => [
                    ItemKind::FLASK->name => 60,
                    ArmorKind::PLATEARMOR->name => 15,
                    WeaponKind::BLUESWORD->name => 15,
                    ItemKind::FIREPOTION->name => 5
                ],
                'hp' => 200,
                'armor' => 3,
                'weapon' => 3
            ],

            self::BOSS => [
                'drops' => [
                    WeaponKind::GOLDENSWORD->name => 100,
                ],
                'hp' => 700,
                'armor' => 6,
                'weapon' => 7
            ],

            self::DEATHKNIGHT => [
                'drops' => [
                    ItemKind::BURGER->name => 95,
                    ItemKind::FIREPOTION->name => 5
                ],
                'hp' => 250,
                'armor' => 3,
                'weapon' => 3
            ],

            default => throw new \RuntimeException('Cannot happen!')
        };
    }
}
