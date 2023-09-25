<?php

namespace App\Game;

use App\Enum\Kinds\MobKind;
use App\Game\Messages\Drop;
use React\EventLoop\Loop;
use React\EventLoop\TimerInterface;

class Mob extends Character
{
    protected Vector2 $spawningPosition;

    protected int $armorLevel;

    protected int $weaponLevel;

    /**
     * @var Player[] $hateList
     */
    protected array $hateList;

    protected ?TimerInterface $respawnTimeout;

    protected ?TimerInterface $returnTimeout;

    protected bool $isDead;

    /**
     * @var ?callable $respawnCallback
     */
    protected $respawnCallback;

    /**
     * @var ?callable $moveCallback
     */
    protected $moveCallback;

    protected ?Area $area;

    public function __construct(int $id, MobKind $kind, Vector2 $position)
    {
        parent::__construct($id, $kind, $position);

        $this->updateHitPoints();
        $this->spawningPosition = new Vector2($position->x, $position->y);
        $this->armorLevel = $kind->getProperties()['armor'];
        $this->weaponLevel = $kind->getProperties()['weapon'];
        $this->hateList = [];
        $this->respawnTimeout = null;
        $this->returnTimeout = null;
        $this->isDead = false;
        $this->respawnCallback = null;
        $this->moveCallback = null;
        $this->area = null;
    }

    public function destroy(): void
    {
        $this->isDead = true;
        $this->hateList = [];
        $this->clearTarget();;
        $this->updateHitPoints();
        $this->resetPosition();

        $this->handleRespawn();
    }

    public function receiveDamage(int $points, int $playerId): void
    {
        $this->hitPoints -= $points;
    }

    public function hates(int $playerId): bool
    {
        return Utils::any($this->hateList, function (Player $player) use ($playerId) {
            return $player->getId() === $playerId;
        });
    }

    public function increaseHateFor(int $playerId, int $points, World $world): void
    {
        if ($this->hates($playerId)) {
            /** @var ?Player $player */
            $player = Utils::detect($this->hateList, function (Player $player) use ($playerId) {
                return $player->getId() === $playerId;
            });

            $player?->increaseHate($points);
        } else {
            /** @var ?Player $player */
            $player = $world->getEntityById($playerId);

            if ($player !== null) {
                $this->hateList[] = $player;
            }
        }

        if ($this->returnTimeout !== null) {
            // Prevent the mob from returning to its spawning position since it has aggroed a new player
            Loop::cancelTimer($this->returnTimeout);
            $this->returnTimeout = null;
        }
    }

    public function getHatedPlayerId(int $hateRank): ?int
    {
        $sorted = $this->hateList;
        usort($sorted, fn (Player $player) => $player->getHate());
        $size = count($this->hateList);
        $index = 0;

        if ($hateRank > 0 && $hateRank <= $size) {
            $index = $size - $hateRank;
        } else {
            $index = $size - 1;
        }

        if (array_key_exists($index, $sorted)) {
            return $sorted[$index]->getId();
        }

        return null;
    }

    public function forgetPlayer(int $playerId, int $duration = 0): void
    {
        $this->hateList = Utils::reject($this->hateList, function (Player $player) use ($playerId) {
            return $player->getId() === $playerId;
        });

        if (empty($this->hateList)) {
            $this->returnToSpawningPosition($duration);
        }
    }

    public function forgetEveryone(): void
    {
        $this->hateList = [];
        $this->returnToSpawningPosition(1);
    }

    public function drop(Item $item): Drop
    {
        $hatedPlayerIds = [];

        foreach ($this->getHateList() as $player) {
            $hatedPlayerIds[] = $player->getId();
        }

        return new Drop(
            $this->id,
            $hatedPlayerIds,
            $item->getId(),
            $item->getKind()->value
        );
    }

    public function handleRespawn(): void
    {
        $delay = 30;

        if ($this->area instanceof MobArea) {
            // Respawn inside the area if part of a MobArea
            $this->area->respawnMob($this, $delay);

            return;
        }

        if ($this->area instanceof ChestArea) {
            $this->area->removeFromArea($this);
        }

        $self = $this;
        Loop::addTimer(
            $delay,
            function () use ($self) {
                if ($self->respawnCallback !== null) {
                    call_user_func($self->respawnCallback);
                }
            }
        );
    }

    public function resetPosition(): void
    {
        $this->setPosition(
            new Vector2($this->spawningPosition->x, $this->spawningPosition->y)
        );
    }

    public function returnToSpawningPosition(int $waitDuration): void
    {
        $this->clearTarget();

        $self = $this;
        $this->returnTimeout = Loop::addTimer(
            $waitDuration,
            function () use ($self) {
                $self->resetPosition();
                $self->move(
                    new Vector2($self->position->x, $self->position->y)
                );
            }
        );
    }

    public function move(Vector2 $position): void
    {
        $this->setPosition(
            new Vector2($position->x, $position->y)
        );

        if ($this->moveCallback !== null) {
            call_user_func($this->moveCallback, $this);
        }
    }

    public function updateHitPoints(): void
    {
        /** @var MobKind $kind */
        $kind = $this->kind;

        $this->resetHitPoints(
            $kind->getProperties()['hp']
        );
    }

    public function distanceToSpawningPoint(Vector2 $position): int
    {
        return $position->distanceTo($this->spawningPosition);
    }

    public function isDead(): bool
    {
        return $this->isDead;
    }

    public function setIsDead(bool $isDead): void
    {
        $this->isDead = $isDead;
    }

    /**
     * @return Player[]
     */
    public function getHateList(): array
    {
        return $this->hateList;
    }

    public function onRespawn(callable $callback): void
    {
        $this->respawnCallback = $callback;
    }

    public function onMove(callable $callback): void
    {
        $this->moveCallback = $callback;
    }

    public function setArea(Area $area): void
    {
        $this->area = $area;
    }

    public function getWeaponLevel(): int
    {
        return $this->weaponLevel;
    }

    public function getArmorLevel(): int
    {
        return $this->armorLevel;
    }

    public function getArea(): ?Area
    {
        return $this->area;
    }
}