<?php

namespace App\Game;

use App\Enum\KindInterface;
use App\Game\Messages\Despawn;
use App\Game\Messages\Spawn;

class Entity
{
    protected ?string $group;

    /**
     * @var string[] $recentlyLeftGroups
     */
    protected array $recentlyLeftGroups;

    public function __construct(
        protected int $id,
        protected KindInterface $kind,
        protected Vector2 $position
    )
    {
        $this->group = null;
        $this->recentlyLeftGroups = [];
    }

    public function destroy(): void
    {
    }

    public function getBaseState(): array
    {
        return [
            $this->id,
            $this->kind->value,
            $this->position->x,
            $this->position->y
        ];
    }

    public function getState(): array
    {
        return $this->getBaseState();
    }

    public function spawn(): Spawn
    {
        return new Spawn($this);
    }

    public function despawn(): Despawn
    {
        return new Despawn($this->id);
    }

    public function setPosition(Vector2 $position): void
    {
        $this->position = new Vector2($position->x, $position->y);
    }

    public function getPositionNextTo(Entity $entity): Vector2
    {
        $rand = rand(0, 3);

        $position = new Vector2(
            $entity->position->x,
            $entity->position->y
        );

        switch ($rand) {
            case 0:
                $position->y--;
                break;
            case 1:
                $position->y++;
                break;
            case 2:
                $position->x--;
                break;
            case 3:
                $position->x++;
                break;
        }

        return $position;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getKind(): KindInterface
    {
        return $this->kind;
    }

    public function setKind(KindInterface $kind): void
    {
        $this->kind = $kind;
    }

    public function getPosition(): Vector2
    {
        return $this->position;
    }

    public function getGroup(): ?string
    {
        return $this->group;
    }

    public function setGroup(?string $group): void
    {
        $this->group = $group;
    }

    /**
     * @return string[]
     */
    public function getRecentlyLeftGroups(): array
    {
        return $this->recentlyLeftGroups;
    }

    /**
     * @param string[] $recentlyLeftGroups
     */
    public function setRecentlyLeftGroups(array $recentlyLeftGroups): void
    {
        $this->recentlyLeftGroups = $recentlyLeftGroups;
    }
}