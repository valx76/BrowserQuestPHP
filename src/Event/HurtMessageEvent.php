<?php

namespace App\Event;

class HurtMessageEvent extends AbstractMessageEvent
{
    public const NAME = 'message.hurt';

    public function getMobId(): string
    {
        return $this->message[1];
    }
}