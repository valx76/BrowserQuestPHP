<?php

namespace App\Game\Messages;

use App\Enum\Message;

final readonly class Damage implements MessageInterface
{
    public function __construct(
        private int $entityId,
        private int $points
    )
    {
    }

    public function serialize(): array
    {
        return [
            Message::DAMAGE,
            $this->entityId,
            $this->points
        ];
    }
}