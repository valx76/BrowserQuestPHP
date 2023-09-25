<?php

namespace App\Game\Messages;

use App\Enum\Message;

final readonly class Teleport implements MessageInterface
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
            Message::TELEPORT,
            $this->entityId,
            $this->positionX,
            $this->positionY
        ];
    }
}