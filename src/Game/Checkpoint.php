<?php

namespace App\Game;

class Checkpoint
{
    public function __construct(
        protected int $id,
        protected Vector2 $position,
        protected Vector2 $size
    )
    {
    }

    public function getRandomPosition(): Vector2
    {
        return new Vector2(
            $this->position->x + rand(0, $this->size->x - 1),
            $this->position->y + rand(0, $this->size->y - 1)
        );
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
}