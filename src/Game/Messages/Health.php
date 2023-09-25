<?php

namespace App\Game\Messages;

use App\Enum\Message;

final readonly class Health implements MessageInterface
{
    public function __construct(
        private int $points,
        private bool $isRegen
    )
    {
    }

    public function serialize(): array
    {
        $health = [Message::HEALTH, $this->points];

        if ($this->isRegen) {
            $health[] = 1;
        }

        return $health;
    }
}