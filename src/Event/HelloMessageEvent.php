<?php

namespace App\Event;

class HelloMessageEvent extends AbstractMessageEvent
{
    public const NAME = 'message.hello';

    public function getName(): string
    {
        return $this->message[1];
    }

    public function getArmorId(): int
    {
        return $this->message[2];
    }

    public function getWeaponId(): int
    {
        return $this->message[3];
    }
}