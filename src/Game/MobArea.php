<?php

namespace App\Game;

use App\Enum\Kinds\MobKind;
use React\EventLoop\Loop;

class MobArea extends Area
{
    public function __construct(
        int $id,
        Vector2 $position,
        Vector2 $size,
        World $world,
        protected int $nb,
        protected MobKind $kind
    )
    {
        parent::__construct($id, $position, $size, $world);

        $this->setNumberOfMobs($this->nb);
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

    public function getNb(): int
    {
        return $this->nb;
    }

    public function getKind(): ?MobKind
    {
        return $this->kind;
    }

    public function spawnMobs(): void
    {
        for ($i = 0; $i < $this->nb; $i++) {
            $this->addToArea(
                $this->createMobInsideArea()
            );
        }
    }

    protected function createMobInsideArea(): Mob
    {
        $position = $this->getRandomPositionInsideArea();

        $mob = new Mob(
            sprintf('1%d%d%d', $this->id, $this->kind->value, count($this->mobs)),
            $this->kind,
            $position
        );

        $mob->onMove(
            $this->world->onMobMoveCallback(...)
        );

        return $mob;
    }

    public function respawnMob(Mob $mob, int $delay): void
    {
        $this->removeFromArea($mob);

        $self = $this;
        Loop::addTimer(
            $delay,
            function () use ($self, $mob) {
                $pos = $self->getRandomPositionInsideArea();
                $mob->setPosition($pos);
                $mob->setIsDead(false);

                $self->addToArea($mob);
                $self->world->addMob($mob);
            }
        );
    }

    public function respawnMobCallback(Mob $mob): void
    {
        $position = $this->getRandomPositionInsideArea();
        $mob->setPosition(
            new Vector2($position->x, $position->y)
        );
        $mob->setIsDead(false);

        $this->addToArea($mob);

        $this->world->addMob($mob);
    }
}