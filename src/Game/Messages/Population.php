<?php

namespace App\Game\Messages;

use App\Enum\Message;

final readonly class Population implements MessageInterface
{
    public function __construct(
        private int $world,
        private int $total
    )
    {
    }

    public function serialize(): array
    {
        return [
            Message::POPULATION,
            $this->world,
            $this->total
        ];
    }
}