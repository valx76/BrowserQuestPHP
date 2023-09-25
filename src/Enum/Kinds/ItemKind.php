<?php

namespace App\Enum\Kinds;

use App\Enum\KindInterface;
use App\Enum\KindTrait;

enum ItemKind: int implements KindInterface
{
    use KindTrait;

    case FLASK = 35;
    case BURGER = 36;
    case CHEST = 37;
    case FIREPOTION = 38;
    case CAKE = 39;
}
