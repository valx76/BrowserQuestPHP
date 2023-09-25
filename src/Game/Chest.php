<?php

namespace App\Game;

use App\Enum\Kinds\ItemKind;

class Chest extends Item
{
    /**
     * @var int[] $items
     */
    protected array $items; // Item kind values

    public function __construct(int $id, Vector2 $position)
    {
        parent::__construct($id, ItemKind::CHEST, $position);
    }

    /**
     * @param int[] $items
     */
    public function setItems(array $items): void
    {
        $this->items = $items;
    }

    public function getRandomItemKindValue(): ?int
    {
        if (count($this->items) === 0) {
            return null;
        }

        return $this->items[array_rand($this->items)];
    }
}