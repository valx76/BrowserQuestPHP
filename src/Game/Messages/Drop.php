<?php

namespace App\Game\Messages;

use App\Enum\Message;

final readonly class Drop implements MessageInterface
{
    /**
     * @param int[] $hatedPlayerIds
     */
    public function __construct(
        private int $mobId,
        private array $hatedPlayerIds,
        private int $itemId,
        private int $itemKindValue
    )
    {
    }

    public function serialize(): array
    {
        return [
            Message::DROP,
            $this->mobId,
            $this->itemId,
            $this->itemKindValue,
            $this->hatedPlayerIds
        ];
    }
}