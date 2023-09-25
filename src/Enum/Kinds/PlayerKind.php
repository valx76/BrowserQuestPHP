<?php

namespace App\Enum\Kinds;

use App\Enum\KindInterface;
use App\Enum\KindTrait;

enum PlayerKind: int implements KindInterface
{
    use KindTrait;

    case WARRIOR = 1;
}
