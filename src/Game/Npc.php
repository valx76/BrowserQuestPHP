<?php

namespace App\Game;

use App\Enum\Kinds\NpcKind;

class Npc extends Entity
{
    public function __construct(int $id, NpcKind $kind, Vector2 $position)
    {
        parent::__construct($id, $kind, $position);
    }
}