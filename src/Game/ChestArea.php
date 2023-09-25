<?php

namespace App\Game;

class ChestArea extends Area
{
    /**
     * @param int[] $items
     */
    public function __construct(
        int $id,
        Vector2 $position,
        Vector2 $size,
        World $world,
        protected Vector2 $chestPosition,
        protected array $items // Item kind values
    )
    {
        parent::__construct($id, $position, $size, $world);
    }

    public function contains(Entity $entity): bool
    {
        return $entity->getPosition()->x >= $this->position->x
            && $entity->getPosition()->y >= $this->position->y
            && $entity->getPosition()->x < $this->position->x + $this->size->x
            && $entity->getPosition()->y < $this->position->y + $this->size->y;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getPosition(): Vector2
    {
        return $this->position;
    }

    public function getSize(): Vector2
    {
        return $this->size;
    }

    public function getChestPosition(): Vector2
    {
        return $this->chestPosition;
    }

    /**
     * @return int[]
     */
    public function getItems(): array
    {
        return $this->items;
    }
}