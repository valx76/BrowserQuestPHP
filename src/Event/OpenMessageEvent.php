<?php

namespace App\Event;

class OpenMessageEvent extends AbstractMessageEvent
{
    public const NAME = 'message.open';

    public function getChestId(): string
    {
        return $this->message[1];
    }
}