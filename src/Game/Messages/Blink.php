<?php

namespace App\Game\Messages;

use App\Enum\Message;

final readonly class Blink implements MessageInterface
{
    public function __construct(
        private int $entityId
    )
    {
    }

    public function serialize(): array
    {
        return [
            Message::BLINK,
            $this->entityId
        ];
    }
}