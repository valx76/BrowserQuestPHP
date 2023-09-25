<?php

namespace App\Game\Messages;

use App\Enum\Message;

final readonly class Attack implements MessageInterface
{
    public function __construct(
        private int $attackerId,
        private int $targetId
    )
    {
    }

    public function serialize(): array
    {
        return [
            Message::ATTACK,
            $this->attackerId,
            $this->targetId
        ];
    }
}