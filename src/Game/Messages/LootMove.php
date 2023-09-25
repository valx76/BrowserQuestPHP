<?php

namespace App\Game\Messages;

use App\Enum\Message;

final readonly class LootMove implements MessageInterface
{
    public function __construct(
        private int $entityId,
        private int $itemId
    )
    {
    }

    public function serialize(): array
    {
        return [
            Message::LOOTMOVE,
            $this->entityId,
            $this->itemId
        ];
    }
}