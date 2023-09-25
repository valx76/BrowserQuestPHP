<?php

namespace App\Game\Messages;

use App\Enum\Message;

final readonly class HitPoints implements MessageInterface
{
    public function __construct(
        private int $maxHitPoints
    )
    {
    }

    public function serialize(): array
    {
        return [
            Message::HP,
            $this->maxHitPoints
        ];
    }
}