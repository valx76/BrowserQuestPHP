<?php

namespace App\Game\Messages;

use App\Enum\Message;

final readonly class Move implements MessageInterface
{
    public function __construct(
        private int $entityId,
        private int $positionX,
        private int $positionY
    )
    {
    }

    public function serialize(): array
    {
        return [
            Message::MOVE,
            $this->entityId,
            $this->positionX,
            $this->positionY
        ];
    }
}