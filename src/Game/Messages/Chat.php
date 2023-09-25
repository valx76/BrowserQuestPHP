<?php

namespace App\Game\Messages;

use App\Enum\Message;

final readonly class Chat implements MessageInterface
{
    public function __construct(
        private int $playerId,
        private string $message
    )
    {
    }

    public function serialize(): array
    {
        return [
            Message::CHAT,
            $this->playerId,
            $this->message
        ];
    }
}