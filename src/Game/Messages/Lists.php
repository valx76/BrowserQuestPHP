<?php

namespace App\Game\Messages;

use App\Enum\Message;

final readonly class Lists implements MessageInterface
{
    /**
     * @param int[] $ids
     */
    public function __construct(
        private array $ids
    )
    {
    }

    public function serialize(): array
    {
        $list = $this->ids;

        array_unshift(
            $list,
            Message::LISTS
        );

        return $list;
    }
}