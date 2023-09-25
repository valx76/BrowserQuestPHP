<?php

namespace App\Event;

class AttackMessageEvent extends AbstractMessageEvent
{
    public const NAME = 'message.attack';

    public function getMobId(): string
    {
        return $this->message[1];
    }
}