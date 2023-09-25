<?php

namespace App\Game;

class Map
{
    protected const ZONE_SIZE_WIDTH = 28;
    protected const ZONE_SIZE_HEIGHT = 12;

    protected Vector2 $size;

    /**
     * @var int[] $collisions
     */
    protected array $collisions;

    /**
     * @var array{id: int, x: int, y: int, width: int, height: int, type: string, nb: int} $mobAreas
     */
    protected array $mobAreas;

    /**
     * @var array{x: int, y: int, w: int, h: int, i: int[], tx: int, ty: int} $chestAreas
     */
    protected array $chestAreas;

    /**
     * @var array{x: int, y: int, i: array} $staticChests
     */
    protected array $staticChests;

    /**
     * @var array{string} $staticEntities
     */
    protected array $staticEntities;

    protected Vector2 $zoneSize;

    protected Vector2 $groupSize;

    /**
     * @var Vector2[] $connectedGroups
     */
    protected array $connectedGroups;

    /**
     * @var Checkpoint[] $checkpoints
     */
    protected array $checkpoints;

    /**
     * @var Checkpoint[] $startingAreas
     */
    protected array $startingAreas;

    /**
     * @var ?callable $readyCallback
     */
    protected $readyCallback;

    /**
     * @var int[][] $grid
     */
    protected array $grid;

    protected bool $isLoaded;

    public function __construct(
        protected string $filePath
    )
    {
        $this->isLoaded = false;
    }

    public function initMap(): void
    {
        if (!file_exists($this->filePath)) {
            throw new \RuntimeException('Map file not found!');
        }

        $map = json_decode(
            file_get_contents($this->filePath),
            true
        );

        $this->size = new Vector2($map['width'], $map['height']);
        $this->collisions = $map['collisions'];
        $this->mobAreas = $map['roamingAreas'];
        $this->chestAreas = $map['chestAreas'];
        $this->staticChests = $map['staticChests'];
        $this->staticEntities = $map['staticEntities'];
        $this->isLoaded = true;
        $this->zoneSize = new Vector2(self::ZONE_SIZE_WIDTH, self::ZONE_SIZE_HEIGHT);
        $this->groupSize = new Vector2(
            floor($this->size->x / $this->zoneSize->x),
            floor($this->size->y / $this->zoneSize->y)
        );

        $this->initConnectedGroups($map['doors']);
        $this->initCheckpoints($map['checkpoints']);

        if ($this->readyCallback !== null)
        {
            call_user_func($this->readyCallback);
        }
    }

    public function ready(callable $callback): void
    {
        $this->readyCallback = $callback;
    }

    public function tileIndexToGridPosition(int $tileNum): Vector2
    {
        return new Vector2(
            $this->getX($tileNum, $this->size->x),
            floor(($tileNum-1) / $this->size->x)
        );
    }

    protected function getX($tileNum, $width): int
    {
        if ($tileNum === 0) {
            return 0;
        }

        return ($tileNum % $width === 0)
            ? $width - 1
            : ($tileNum % $width) - 1;
    }

    public function generateCollisionGrid(): void
    {
        $this->grid = [];

        if (!$this->isLoaded) {
            return;
        }

        $tileIndex = 0;
        $collisionsMap = array_flip($this->collisions);

        for ($i = 0; $i < $this->size->y; $i++) {
            $this->grid[$i] = [];

            for ($j = 0; $j < $this->size->x; $j++) {
                $this->grid[$i][$j] = isset($collisionsMap[$tileIndex]) ? 1 : 0;
                $tileIndex++;
            }
        }
    }

    public function isOutOfBounds(Vector2 $position): bool
    {
        return $position->x <= 0 || $position->x >= $this->size->x
            || $position->y <= 0 || $position->y >= $this->size->y;
    }

    public function isColliding(Vector2 $position): bool
    {
        if ($this->isOutOfBounds($position)) {
            return false;
        }

        return $this->grid[$position->y][$position->x] === 1;
    }

    public function groupIdToGroupPosition(string $id): Vector2
    {
        $posArray = explode('-', $id);

        return new Vector2(
            $posArray[0],
            $posArray[1]
        );
    }

    public function forEachGroup(callable $callback): void
    {
        for ($x = 0; $x < $this->groupSize->x; $x++) {
            for ($y = 0; $y < $this->groupSize->y; $y++) {
                call_user_func($callback, sprintf('%d-%d', $x, $y));
            }
        }
    }

    public function getGroupIdFromPosition(Vector2 $position): string
    {
        $groupX = floor(($position->x - 1) / $this->zoneSize->x);
        $groupY = floor(($position->y - 1) / $this->zoneSize->y);

        return sprintf(
            '%d-%d',
            $groupX,
            $groupY
        );
    }

    /**
     * @return Vector2[]
     */
    public function getAdjacentGroupPositions(string $groupId): array
    {
        $position = $this->groupIdToGroupPosition($groupId);

        // Surrounding groups
        $list = [
            new Vector2($position->x - 1, $position->y - 1),
            new Vector2($position->x, $position->y - 1),
            new Vector2($position->x + 1, $position->y - 1),
            new Vector2($position->x - 1, $position->y),

            new Vector2($position->x, $position->y),

            new Vector2($position->x + 1, $position->y),
            new Vector2($position->x - 1, $position->y + 1),
            new Vector2($position->x, $position->y + 1),
            new Vector2($position->x + 1, $position->y + 1),
        ];

        // Groups connected via doors
        $self = $this;
        if (!empty($this->connectedGroups[$groupId])) {
            array_walk(
                $this->connectedGroups[$groupId],
                function (Vector2 $position) use (&$list, $self) {
                    // Do not add a connected group if it is already part of the surrounding ones
                    if (Utils::any(
                        $list,
                        function (Vector2 $groupPos) use ($position, $self) {
                            return $groupPos->isEqualTo($position);
                        }
                    )) {
                        $list[] = $position;
                    }
                }
            );
        }

        return Utils::reject(
            $list,
            function (Vector2 $pos) use ($self) {
                return $pos->x < 0 || $pos->x >= $self->groupSize->x
                    || $pos->y < 0 || $pos->y >= $self->groupSize->y;
            }
        );
    }

    public function forEachAdjacentGroup(string $groupId, callable $callback): void
    {
        $positions = $this->getAdjacentGroupPositions($groupId);

        array_walk(
            $positions,
            function (Vector2 $position) use ($callback) {
                call_user_func(
                    $callback,
                    sprintf('%d-%d', $position->x, $position->y)
                );
            }
        );
    }

    /**
     * @param array{array{x: int, y: int, p: int, tcx: int, tcy: int, to: string, tx: int, ty: int}} $doors
     */
    public function initConnectedGroups(array $doors): void
    {
        $self = $this;

        array_walk(
            $doors,
            /**
             * @param array{x: int, y: int, p: int, tcx: int, tcy: int, to: string, tx: int, ty: int} $door
             */
            function (array $door) use ($self) {
                $doorPosition = new Vector2($door['x'], $door['y']);
                $doorPositionT = new Vector2($door['tx'], $door['ty']);

                $groupId = $self->getGroupIdFromPosition(
                    new Vector2($doorPosition->x, $doorPosition->y)
                );
                $connectedGroupId = $self->getGroupIdFromPosition(
                    new Vector2($doorPositionT->x, $doorPositionT->y)
                );
                $connectedPosition = $self->groupIdToGroupPosition($connectedGroupId);

                if (!isset($self->connectedGroups[$groupId])) {
                    $self->connectedGroups[$groupId] = [];
                }
                $self->connectedGroups[$groupId][] = $connectedPosition;
            }
        );
    }

    /**
     * @param array{array{id: int, x: int, y: int, w: int, h: int, s: int}} $checkpointsList
     */
    public function initCheckpoints(array $checkpointsList): void
    {
        $this->checkpoints = [];
        $this->startingAreas = [];

        $self = $this;

        array_walk(
            $checkpointsList,
            /**
             * @param array{id: int, x: int, y: int, w: int, h: int, s: int} $cp
             */
            function (array $cp) use ($self) {
                $checkpoint = new Checkpoint(
                    $cp['id'],
                    new Vector2($cp['x'], $cp['y']),
                    new Vector2($cp['w'], $cp['h'])
                );

                $self->checkpoints[$checkpoint->getId()] = $checkpoint;

                if ($cp['s'] === 1) {
                    $self->startingAreas[] = $checkpoint;
                }
            }
        );
    }

    public function getCheckpoint(int $id): Checkpoint
    {
        if (!array_key_exists($id, $this->checkpoints)) {
            throw new \RuntimeException(
                sprintf('The checkpoint %d does not exist', $id)
            );
        }

        return $this->checkpoints[$id];
    }

    public function getRandomStartingPosition(): Vector2
    {
        $nbAreas = count($this->startingAreas);
        $i = rand(0, $nbAreas - 1);
        $area = $this->startingAreas[$i];

        return $area->getRandomPosition();
    }

    /**
     * @return array{id: int, x: int, y: int, width: int, height: int, type: string, nb: int}
     */
    public function getMobAreas(): array
    {
        return $this->mobAreas;
    }

    /**
     * @return array{x: int, y: int, w: int, h: int, i: int[], tx: int, ty: int}
     */
    public function getChestAreas(): array
    {
        return $this->chestAreas;
    }

    /**
     * @return array{x: int, y: int, i: array}
     */
    public function getStaticChests(): array
    {
        return $this->staticChests;
    }

    /**
     * @return array{string}
     */
    public function getStaticEntities(): array
    {
        return $this->staticEntities;
    }
}