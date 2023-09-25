<?php

namespace App\Event;

class TeleportMessageEvent extends AbstractMessageEvent
{
    public const NAME = 'message.teleport';

    public function getPositionX(): int
    {
        return $this->message[1];
    }

    public function getPositionY(): int
    {
        return $this->message[2];
    }
}