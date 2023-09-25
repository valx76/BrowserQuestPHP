<?php

namespace App\Event;

class MoveMessageEvent extends AbstractMessageEvent
{
    public const NAME = 'message.move';

    public function getPositionX(): int
    {
        return $this->message[1];
    }

    public function getPositionY(): int
    {
        return $this->message[2];
    }
}