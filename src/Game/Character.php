<?php

namespace App\Game;

use App\Enum\Kinds\MobKind;
use App\Enum\Kinds\PlayerKind;
use App\Enum\Orientation;
use App\Game\Messages\Attack;
use App\Game\Messages\Health;

class Character extends Entity
{
    protected Orientation $orientation;

    /**
     * @var Mob[] $attackers
     */
    protected array $attackers;

    protected ?int $targetId;

    protected int $maxHitPoints;

    protected int $hitPoints;

    public function __construct(int $id, MobKind|PlayerKind $kind, Vector2 $position)
    {
        parent::__construct($id, $kind, $position);

        $this->orientation = Orientation::random();
        $this->attackers = [];
        $this->targetId = null;

        $this->maxHitPoints = 0;
        $this->hitPoints = 0;
    }

    public function getState(): array
    {
        $baseState = $this->getBaseState();
        $state = [$this->orientation->value];

        if ($this->targetId !== null) {
            $state[] = $this->targetId;
        }

        return array_merge($baseState, $state);
    }

    public function resetHitPoints(int $maxHitPoints): void
    {
        $this->maxHitPoints = $maxHitPoints;
        $this->hitPoints = $maxHitPoints;
    }

    public function regenHealthBy(int $value): void
    {
        $hp = $this->hitPoints;
        $max = $this->maxHitPoints;

        if ($hp < $max) {
            if ($hp + $value <= $max) {
                $this->hitPoints += $value;
            } else {
                $this->hitPoints = $max;
            }
        }
    }

    public function hasFullHealth(): bool
    {
        return $this->hitPoints === $this->maxHitPoints;
    }

    public function setTarget(Entity $entity): void
    {
        $this->targetId = $entity->getId();
    }

    public function clearTarget(): void
    {
        $this->targetId = null;
    }

    public function hasTarget(): bool
    {
        return $this->targetId !== null;
    }

    public function attack(): Attack
    {
        return new Attack($this->id, $this->targetId);
    }

    public function health(): Health
    {
        return new Health($this->hitPoints, false);
    }

    public function regen(): Health
    {
        return new Health($this->hitPoints, true);
    }

    public function addAttacker(Mob $mob): void
    {
        $this->attackers[$mob->getId()] = $mob;
    }

    public function removeAttacker(Mob $mob): void
    {
        if (array_key_exists($mob->getId(), $this->attackers)) {
            unset($this->attackers[$mob->getId()]);
        }
    }

    public function forEachAttacker(callable $callback): void
    {
        array_map($callback, $this->attackers);
    }

    /**
     * @return Mob[]
     */
    public function getAttackers(): array
    {
        return $this->attackers;
    }

    public function getTarget(): ?int
    {
        return $this->targetId;
    }

    public function getMaxHitPoints(): int
    {
        return $this->maxHitPoints;
    }

    public function getHitPoints(): int
    {
        return $this->hitPoints;
    }

    public function setHitPoints(int $hitPoints): void
    {
        $this->hitPoints = $hitPoints;
    }

    public function setOrientation(Orientation $orientation): void
    {
        $this->orientation = $orientation;
    }
}