<?php

namespace App\Game;

class Area
{
    protected int $nbMobs;

    /**
     * @param Mob[] $mobs
     */
    protected array $mobs;

    protected bool $hasCompletelyRespawned;

    /**
     * @var ?callable $emptyCallback
     */
    protected $emptyCallback;


    public function __construct(
        protected int $id,
        protected Vector2 $position,
        protected Vector2 $size,
        protected World $world
    )
    {
        $this->nbMobs = 0;
        $this->mobs = [];
        $this->hasCompletelyRespawned = true;
        $this->emptyCallback = null;
    }

    public function getRandomPositionInsideArea(): Vector2
    {
        $position = new Vector2(-1, -1);

        do {
            $position->x = $this->position->x + rand(0, $this->size->x);
            $position->y = $this->position->y + rand(0, $this->size->y);
        } while (!$this->world->isValidPosition($position));

        return $position;
    }

    public function removeFromArea(Mob $mob): void
    {
        unset($this->mobs[$mob->getId()]);

        if ($this->isEmpty() && $this->hasCompletelyRespawned && $this->emptyCallback !== null) {
            $this->hasCompletelyRespawned = false;
            ($this->emptyCallback)();
        }
    }

    public function addToArea(Mob $mob): void
    {
        $this->mobs[$mob->getId()] = $mob;
        $mob->setArea($this);
        $this->world->addMob($mob);

        if ($this->isFull()) {
            $this->hasCompletelyRespawned = true;
        }
    }

    public function setNumberOfMobs(int $nbMobs): void
    {
        $this->nbMobs = $nbMobs;
    }

    public function isEmpty(): bool
    {
        foreach ($this->mobs as $mob) {
            if (!$mob->isDead()) {
                return false;
            }
        }

        return true;
    }

    public function isFull(): bool
    {
        return !$this->isEmpty()
            && $this->nbMobs === count($this->mobs);
    }

    public function onEmpty(callable $callback): void
    {
        $this->emptyCallback = $callback;
    }

    /**
     * @return Mob[]
     */
    public function getMobs(): array
    {
        return $this->mobs;
    }
}