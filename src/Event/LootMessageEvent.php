<?php

namespace App\Event;

class LootMessageEvent extends AbstractMessageEvent
{
    public const NAME = 'message.loot';

    public function getItemId(): int
    {
        return $this->message[1];
    }
}