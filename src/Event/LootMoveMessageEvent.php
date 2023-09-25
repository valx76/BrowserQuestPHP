<?php

namespace App\Event;

class LootMoveMessageEvent extends AbstractMessageEvent
{
    public const NAME = 'message.loot_move';

    public function getPositionX(): int
    {
        return $this->message[1];
    }

    public function getPositionY(): int
    {
        return $this->message[2];
    }

    public function getItemId(): int
    {
        return $this->message[3];
    }
}