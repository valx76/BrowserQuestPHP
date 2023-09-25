<?php

namespace App\Game\Messages;

use App\Enum\Message;

final readonly class Despawn implements MessageInterface
{
    public function __construct(
        private int $entityId
    )
    {
    }

    public function serialize(): array
    {
        return [Message::DESPAWN, $this->entityId];
    }
}