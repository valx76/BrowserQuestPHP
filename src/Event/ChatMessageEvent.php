<?php

namespace App\Event;

class ChatMessageEvent extends AbstractMessageEvent
{
    public const NAME = 'message.chat';

    public function getWrittenMessage(): string
    {
        return $this->message[1];
    }
}