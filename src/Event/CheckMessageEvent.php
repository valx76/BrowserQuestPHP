<?php

namespace App\Event;

class CheckMessageEvent extends AbstractMessageEvent
{
    public const NAME = 'message.check';

    public function getCheckpointId(): int
    {
        return $this->message[1];
    }
}