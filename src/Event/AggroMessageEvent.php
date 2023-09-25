<?php

namespace App\Event;

class AggroMessageEvent extends AbstractMessageEvent
{
    public const NAME = 'message.aggro';

    public function getMobId(): string
    {
        return $this->message[1];
    }
}