<?php

namespace App\Game\Messages;

use App\Enum\Message;

final readonly class EquipItem implements MessageInterface
{
    public function __construct(
        private int $playerId,
        private int $kindValue
    )
    {
    }

    public function serialize(): array
    {
        return [
            Message::EQUIP,
            $this->playerId,
            $this->kindValue
        ];
    }
}