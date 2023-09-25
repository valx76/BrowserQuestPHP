<?php

namespace App\Game\Messages;

use App\Enum\Message;

final readonly class Destroy implements MessageInterface
{
    public function __construct(
        private int $entityId
    )
    {
    }

    public function serialize(): array
    {
        return [
            Message::DESTROY,
            $this->entityId
        ];
    }
}