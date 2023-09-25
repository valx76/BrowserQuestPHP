<?php

namespace App\Enum\Kinds;

use App\Enum\KindInterface;
use App\Enum\KindTrait;

enum NpcKind: int implements KindInterface
{
    use KindTrait;

    case GUARD = 40;
    case VILLAGEGIRL = 43;
    case VILLAGER = 44;
    case CODER = 55;
    case SCIENTIST = 46;
    case PRIEST = 45;
    case KING = 41;
    case RICK = 48;
    case NYAN = 49;
    case SORCERER = 50;
    case AGENT = 47;
    case OCTOCAT = 42;
    case BEACHNPC = 51;
    case FORESTNPC = 52;
    case DESERTNPC = 53;
    case LAVANPC = 54;
}
