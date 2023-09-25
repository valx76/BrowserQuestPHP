<?php

namespace App\Game\Messages;

use App\Game\Entity;
use App\Enum\Message;

final readonly class Spawn implements MessageInterface
{
    public function __construct(
        private Entity $entity
    )
    {
    }

    public function serialize(): array
    {
        $spawn = [Message::SPAWN];

        return array_merge($spawn, $this->entity->getState());
    }
}