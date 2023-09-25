<?php

namespace App\Event;

class HitMessageEvent extends AbstractMessageEvent
{
    public const NAME = 'message.hit';

    public function getMobId(): string
    {
        return $this->message[1];
    }
}