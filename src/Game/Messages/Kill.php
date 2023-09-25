<?php

namespace App\Game\Messages;

use App\Enum\Message;

final readonly class Kill implements MessageInterface
{
    public function __construct(
        private int $kindValue
    )
    {
    }

    public function serialize(): array
    {
        return [
            Message::KILL,
            $this->kindValue
        ];
    }
}