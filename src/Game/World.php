<?php

namespace App\Game;

use App\Enum\Kinds\ArmorKind;
use App\Enum\Kinds\ItemKind;
use App\Enum\Kinds\MobKind;
use App\Enum\Kinds\NpcKind;
use App\Enum\Kinds\PlayerKind;
use App\Enum\Kinds\WeaponKind;
use App\Exception\KindNotFoundException;
use App\Game\Messages\Blink;
use App\Game\Messages\Damage;
use App\Game\Messages\Destroy;
use App\Game\Messages\Kill;
use App\Game\Messages\Lists;
use App\Game\Messages\MessageInterface;
use App\Game\Messages\Move;
use App\Game\Messages\Population;
use App\Game\Messages\Spawn;
use App\Service\KindResolver;
use React\EventLoop\Loop;

class World
{
    protected const INITIAL_UPDATES_PER_SECOND = 50;
    protected const MIN_DISTANCE_TO_SPAWNING_POINT = 51;
    protected const REGEN_MAX_HP_DIVIDER = 25;
    protected const FORGET_PLAYER_DURATION = 1;
    protected const HATE_LOWER_RANK = 2;
    protected const ITEM_DESPAWN_BEFORE_BLINK_DELAY = 10;
    protected const ITEM_DESPAWN_BLINKING_DURATION = 4;

    protected int $ups;

    protected ?Map $map;

    /**
     * @var Entity[] $entities
     */
    protected array $entities;

    /**
     * @var Player[] $players
     */
    protected array $players;

    /**
     * @var Mob[] $mobs
     */
    protected array $mobs;

    /**
     * @var Item[] $items
     */
    protected array $items;

    /**
     * @var Npc[] $npcs
     */
    protected array $npcs;

    /**
     * @var MobArea[] $mobAreas
     */
    protected array $mobAreas;

    /**
     * @var ChestArea[] $chestAreas
     */
    protected array $chestAreas;

    /**
     * @var array{entities: array, players: array, incoming: array} $groups
     */
    protected array $groups;

    /**
     * @var array[] $outgoingQueues
     */
    protected array $outgoingQueues;

    protected int $itemsCount;

    protected int $playersCount;

    protected bool $zoneGroupsReady;

    /**
     * @var ?callable $removedCallback
     */
    protected $removedCallback;

    /**
     * @var ?callable $addedCallback
     */
    protected $addedCallback;

    /** @var ?callable $regenCallback */
    protected $regenCallback;

    /**
     * @var ?callable $initCallback
     */
    protected $initCallback;

    /**
     * @var ?callable $connectCallback
     */
    protected $connectCallback;

    /**
     * @var ?callable $enterCallback
     */
    protected $enterCallback;

    /**
     * @var ?callable $attackCallback
     */
    protected $attackCallback;

    protected int $playerIdCounter;

    protected int $chestAreaIdCounter;

    public function __construct(
        protected KindResolver $kindResolver,
        protected int $id,
        protected int $maxPlayers
    )
    {
        $this->ups = self::INITIAL_UPDATES_PER_SECOND;
        $this->map = null;
        $this->entities = [];
        $this->players = [];
        $this->mobs = [];
        $this->items = [];
        $this->npcs = [];
        $this->mobAreas = [];
        $this->chestAreas = [];
        $this->groups = [];
        $this->outgoingQueues = [];
        $this->itemsCount = 0;
        $this->playersCount = 0;
        $this->zoneGroupsReady = false;
        $this->removedCallback = null;
        $this->addedCallback = null;
        $this->initCallback = null;
        $this->connectCallback = null;
        $this->enterCallback = null;
        $this->attackCallback = null;
        $this->playerIdCounter = 0;
        $this->chestAreaIdCounter = 0;

        $self = $this;

        $this->onPlayerConnect(
            function (Player $player) use ($self) {
                $player->onRequestPosition(
                    function () use ($self, $player) {
                        $lastCheckpoint = $player->getLastCheckpoint();

                        if ($lastCheckpoint !== null) {
                            return $lastCheckpoint->getRandomPosition();
                        } else {
                            return $self->map->getRandomStartingPosition();
                        }
                    }
                );
            }
        );

        $this->onPlayerEnter(
            function (Player $player) use ($self) {
                if (!$player->hasEnteredGame()) {
                    $self->incrementPlayerCount();
                }

                // Number of players in this world
                $self->pushToPlayer(
                    $player,
                    new Population($self->playersCount, 1)
                );
                $self->pushRelevantEntityListTo($player);

                $moveCallback = function (Vector2 $position) use ($player, $self) {
                    $player->forEachAttacker(
                        function (Mob $mob) use ($player, $self) {
                            $target = $self->getEntityById($mob->getTarget());

                            if ($target !== null) {
                                $position = $self->findPositionNextTo($mob, $target);

                                if ($mob->distanceToSpawningPoint($position) >= self::MIN_DISTANCE_TO_SPAWNING_POINT) {
                                    $mob->clearTarget();
                                    $mob->forgetEveryone();
                                    $player->removeAttacker($mob);
                                } else {
                                    $self->moveEntity($mob, $position);
                                }
                            }
                        }
                    );
                };
                $player->onMove($moveCallback);
                $player->onLootMove($moveCallback);

                $player->onZone(
                    function () use ($self, $player) {
                        $hasChangedGroups = $self->handleEntityGroupMembership($player);

                        if ($hasChangedGroups) {
                            $self->pushToPreviousGroups(
                                $player,
                                new Destroy($player->getId())
                            );
                            $self->pushRelevantEntityListTo($player);
                        }
                    }
                );

                $player->onBroadcast(
                    function (MessageInterface $message, bool $ignoreSelf) use ($self, $player) {
                        $self->pushToAdjacentGroups(
                            $player->getGroup(),
                            $message,
                            $ignoreSelf ? $player->getId() : null
                        );
                    }
                );

                $player->onBroadcastToZone(
                    function (MessageInterface $message, bool $ignoreSelf) use ($self, $player) {
                        $self->pushToGroup(
                            $player->getGroup(),
                            $message,
                            $ignoreSelf ? $player->getId() : null
                        );
                    }
                );

                $player->onExit(
                    function () use ($self, $player) {
                        $self->removePlayer($player);
                        $self->decrementPlayerCount();

                        if ($self->removedCallback !== null) {
                            call_user_func($self->removedCallback);
                        }
                    }
                );

                if ($self->addedCallback !== null) {
                    call_user_func($self->addedCallback);
                }
            }
        );

        $this->onEntityAttack(
            function (Character $attacker) use ($self) {
                $target = $self->getEntityById($attacker->getTarget());

                if ($target === null || !$attacker->getKind() instanceof MobKind) {
                    return;
                }

                $position = $self->findPositionNextTo($attacker, $target);
                $self->moveEntity($attacker, $position);
            }
        );

        $this->onRegenTick(
            function () use ($self) {
                $self->forEachCharacter(
                    function (Character $character) use ($self) {
                        if (!$character->hasFullHealth()) {
                            $character->regenHealthBy(
                                floor($character->getMaxHitPoints() / self::REGEN_MAX_HP_DIVIDER)
                            );

                            if ($character->getKind() instanceof PlayerKind) {
                                /** @var Player $p */
                                $p = $character;

                                $self->pushToPlayer($p, $character->regen());
                            }
                        }
                    }
                );
            }
        );
    }

    public function run(string $mapFilePath): void
    {
        $self = $this;

        $this->map = new Map($mapFilePath);

        $this->map->ready(
            function () use ($self) {
                $self->initZoneGroups();

                $self->map->generateCollisionGrid();

                // Populate all mob 'roaming' areas
                foreach ($self->map->getMobAreas() as $a) {
                    $area = new MobArea(
                        $a['id'],
                        new Vector2($a['x'], $a['y']),
                        new Vector2($a['width'], $a['height']),
                        $this,
                        $a['nb'],
                        MobKind::fromName($a['type'])
                    );

                    $area->spawnMobs();

                    $area->onEmpty(
                        function () use ($self, $area) {
                            call_user_func($self->handleEmptyMobArea(...), $area);
                        }
                    );
                }

                // Create all chest areas
                foreach ($self->map->getChestAreas() as $a) {
                    $area = new ChestArea(
                        ($this->chestAreaIdCounter++),
                        new Vector2($a['x'], $a['y']),
                        new Vector2($a['w'], $a['h']),
                        $this,
                        new Vector2($a['tx'], $a['ty']),
                        $a['i']
                    );
                    $self->chestAreas[] = $area;

                    $area->onEmpty(
                        function () use ($self, $area) {
                            call_user_func($self->handleEmptyChestArea(...), $area);
                        }
                    );
                }

                // Spawn static chests
                foreach ($self->map->getStaticChests() as $c) {
                    $chest = $self->createChest(
                        new Vector2($c['x'], $c['y']),
                        $c['i']
                    );
                    $self->addStaticItem($chest);
                }

                // Spawn static entities
                $self->spawnStaticEntities();

                // Set maximum number of entities contained in each chest area
                foreach ($self->chestAreas as $area) {
                    $area->setNumberOfMobs(
                        count($area->getMobs())
                    );
                }
            }
        );

        $this->map->initMap();

        $regenCount = $this->ups * 2;

        $updateCount = 0;
        Loop::addPeriodicTimer(
            1/$this->ups,
            function () use ($self, $regenCount, &$updateCount) {
                $self->processGroups();
                $self->processQueues();

                if ($updateCount < $regenCount) {
                    $updateCount++;
                } else {
                    if ($self->regenCallback !== null) {
                        call_user_func($self->regenCallback);
                    }

                    $updateCount = 0;
                }
            }
        );
    }

    /*public function setUpdatesPerSecond(int $ups): void
    {
        $this->ups = $ups;
    }*/

    /*public function onInit(callable $callback): void
    {
        $this->initCallback = $callback;
    }*/

    public function onPlayerConnect(callable $callback): void
    {
        $this->connectCallback = $callback;
    }

    public function onPlayerEnter(callable $callback): void
    {
        $this->enterCallback = $callback;
    }

    /*public function onPlayerAdded(callable $callback): void
    {
        $this->addedCallback = $callback;
    }*/

    /*public function onPlayerRemoved(callable $callback): void
    {
        $this->removedCallback = $callback;
    }*/

    public function onRegenTick(callable $callback): void
    {
        $this->regenCallback = $callback;
    }

    public function pushRelevantEntityListTo(Player $player): void
    {
        $group = $player->getGroup();

        if ($group === null) {
            // TODO - Exception ?
            return;
        }

        if (!array_key_exists($group, $this->groups)) {
            // TODO - Exception ?
            return;
        }

        $entities = array_keys(
            $this->groups[$group]['entities']
        );
        $entities = Utils::reject(
            $entities,
            function (int $id) use ($player) {
                return $id === $player->getId();
            }
        );

        if (count($entities) > 0) {
            $this->pushToPlayer(
                $player,
                new Lists($entities)
            );
        }
    }

    /**
     * @param int[] $ids
     */
    public function pushSpawnsToPlayer(Player $player, array $ids): void
    {
        foreach ($ids as $id) {
            $entity = $this->getEntityById($id);

            if ($entity === null) {
                // TODO - Check if we want to throw the exception
                // INFO - The code in PHP was just displaying it
                throw new \RuntimeException(
                    sprintf(
                        'Bad id:%d ids:%s',
                        $id,
                        json_encode($ids)
                    )
                );
            }

            $this->pushToPlayer(
                $player,
                new Spawn($entity)
            );
        }
    }

    public function pushToPlayer(Player $player, MessageInterface $message): void
    {
        if (array_key_exists($player->getId(), $this->outgoingQueues)) {
            $this->outgoingQueues[$player->getId()][] = $message->serialize();
        } else {
            echo 'pushToPlayer :: player was undefined';
            // TODO - Throw exception ?
        }
    }

    public function pushToGroup(?string $groupId, MessageInterface $message, ?int $ignoredPlayerId = null): void
    {
        if ($groupId === null) {
            // TODO - Exception ?
            return;
        }

        if (!array_key_exists($groupId, $this->groups)) {
            // TODO - Exception ?
            return;
        }

        $group = $this->groups[$groupId];

        if ($group === null || count($group) === 0) {
            // TODO - Exception ?
            return;
        }

        foreach ($group['players'] as $playerId) {
            if ($playerId !== $ignoredPlayerId) {
                /** @var ?Player $p */
                $p = $this->getEntityById($playerId);

                if ($p !== null) {
                    $this->pushToPlayer(
                        $p,
                        $message
                    );
                }
            }
        }
    }

    public function pushToAdjacentGroups(?string $groupId, MessageInterface $message, ?int $ignoredPlayerId = null): void
    {
        if ($groupId === null) {
            return;
        }

        $self = $this;

        $this->map->forEachAdjacentGroup(
            $groupId,
            function (string $id) use ($self, $message, $ignoredPlayerId) {
                $self->pushToGroup($id, $message, $ignoredPlayerId);
            }
        );
    }

    public function pushToPreviousGroups(Player $player, MessageInterface $message): void
    {
        // TODO - Code below causes crash of the client (cannot move player anymore) !

        /*
        // Push this message to all groups which are not going to be updated anymore,
        // since the player left them.
        foreach ($player->getRecentlyLeftGroups() as $id) {
            $this->pushToGroup($id, $message);
        }

        $player->setRecentlyLeftGroups([]);
        */
    }

    public function pushBroadcast(MessageInterface $message, ?int $ignoredPlayerId = null): void
    {
        foreach ($this->outgoingQueues as $playerId => $item) {
            if ($playerId !== $ignoredPlayerId) {
                $this->outgoingQueues[$playerId][] = $message->serialize();
            }
        }
    }

    public function processQueues(): void
    {
        foreach ($this->outgoingQueues as $playerId => $item) {
            if (empty($this->outgoingQueues[$playerId])) {
                continue;
            }

            $this->players[$playerId]->getConnection()->send(json_encode($item));

            $this->outgoingQueues[$playerId] = [];
        }
    }

    public function addEntity(Entity $entity): void
    {
        $this->entities[$entity->getId()] = $entity;
        $this->handleEntityGroupMembership($entity);
    }

    public function removeEntity(Entity $entity): void
    {
        $entityId = $entity->getId();

        unset(
            $this->entities[$entityId],
            $this->mobs[$entityId],
            $this->items[$entityId]
        );

        if ($entity->getKind() instanceof MobKind) {
            /** @var Mob $m */
            $m = $entity;

            $this->clearMobAggroLink($m);
            $this->clearMobHateLinks($m);
        }

        $entity->destroy();
        $this->removeFromGroups($entity);
    }

    public function addPlayer(Player $player): void
    {
        $this->addEntity($player);
        $this->players[$player->getId()] = $player;
        $this->outgoingQueues[$player->getId()] = [];
    }

    public function removePlayer(Player $player): void
    {
        $player->broadcast($player->despawn());
        $this->removeEntity($player);

        unset(
            $this->players[$player->getId()],
            $this->outgoingQueues[$player->getId()]
        );
    }

    public function addMob(Mob $mob): void
    {
        $this->addEntity($mob);
        $this->mobs[$mob->getId()] = $mob;
    }

    public function addNpc(NpcKind $kind, Vector2 $position): Npc
    {
        $npc = new Npc(
            sprintf('8%d%d', $position->x, $position->y),
            $kind,
            $position
        );

        $this->addEntity($npc);
        $this->npcs[$npc->getId()] = $npc;

        return $npc;
    }

    public function addItem(Item $item): Item
    {
        $this->addEntity($item);
        $this->items[$item->getId()] = $item;

        return $item;
    }

    public function createItem(ItemKind|ArmorKind|WeaponKind $kind, Vector2 $position): Item
    {
        $id = sprintf('9%d', ($this->itemsCount++));

        if ($kind === ItemKind::CHEST) {
            return new Chest($id, $position);
        }

        return new Item($id, $kind, $position);
    }

    /**
     * @param int[] $items
     */
    public function createChest(Vector2 $position, array $items): Chest
    {
        /** @var Chest $chest */
        $chest = $this->createItem(ItemKind::CHEST, $position);

        $chest->setItems($items);

        return $chest;
    }

    public function addStaticItem(Item $item): void
    {
        $self = $this;

        $item->setStatic(true);

        $item->onRespawn(
            function () use ($self, $item) {
                call_user_func($self->addStaticItem(...), $item);
            }
        );

        $this->addItem($item);
    }

    public function addItemFromChest(ItemKind|ArmorKind|WeaponKind $kind, Vector2 $position): Item
    {
        $item = $this->createItem($kind, $position);
        $item->setIsFromChest(true);

        return $this->addItem($item);
    }

    /**
     * The mob will no longer be registered as an attacker of its current target
     */
    public function clearMobAggroLink(Mob $mob): void
    {
        if ($mob->getTarget() !== null) {
            /** @var ?Player $player */
            $player = $this->getEntityById($mob->getTarget());

            $player?->removeAttacker($mob);
        }
    }

    public function clearMobHateLinks(Mob $mob): void
    {
        foreach ($mob->getHateList() as $player) {
            $player->removeHater($mob);
        }
    }

    /*public function forEachEntity(callable $callback): void
    {
        foreach ($this->entities as $entity) {
            call_user_func($callback, $entity);
        }
    }*/

    public function forEachPlayer(callable $callback): void
    {
        foreach ($this->players as $player) {
            call_user_func($callback, $player);
        }
    }

    public function forEachMob(callable $callback): void
    {
        foreach ($this->mobs as $mob) {
            call_user_func($callback, $mob);
        }
    }

    public function forEachCharacter(callable $callback): void
    {
        $this->forEachPlayer($callback);
        $this->forEachMob($callback);
    }

    public function handleMobHate(int $mobId, int $playerId, int $hatePoints): void
    {
        /** @var ?Player $player */
        $player = $this->getEntityById($playerId);

        /** @var ?Mob $mob */
        $mob = $this->getEntityById($mobId);

        if ($player !== null && $mob !== null) {
            $mob->increaseHateFor($playerId, $hatePoints, $this);
            $player->addHater($mob);

            if ($mob->getHitPoints() > 0) {
                // Only choose a target if still alive
                $this->chooseMobTarget($mob);
            }
        }
    }

    public function chooseMobTarget(Mob $mob, int $hateRank = 0): void
    {
        /** @var ?Player $player */
        $player = $this->getEntityById(
            $mob->getHatedPlayerId($hateRank) ?? 0
        );

        // If the mob is not already attacking the player, create an attack link between them.
        if ($player !== null && !array_key_exists($mob->getId(), $player->getAttackers())) {
            $this->clearMobAggroLink($mob);

            $player->addAttacker($mob);
            $mob->setTarget($player);

            $this->broadcastAttacker($mob);
        }
    }

    public function onEntityAttack(callable $callback): void
    {
        $this->attackCallback = $callback;
    }

    public function getEntityById(int $id): ?Entity
    {
        if (array_key_exists($id, $this->entities)) {
            return $this->entities[$id];
        }

        return null;
    }

    public function broadcastAttacker(Character $character): void
    {
        $this->pushToAdjacentGroups(
            $character->getGroup(), $character->attack(), $character->getId()
        );

        if ($this->attackCallback !== null) {
            call_user_func($this->attackCallback, $character);
        }
    }

    /**
     * @throws KindNotFoundException
     */
    public function handleHurtEntity(Character $character, ?Player $attacker = null, int $damage = 0): void
    {
        if ($character->getKind() instanceof PlayerKind) {
            // A player is only aware of his own hitpoints

            /** @var Player $p */
            $p = $character;

            $this->pushToPlayer($p, $p->health());
        } else if ($character->getKind() instanceof MobKind) {
            // Let the mob's attacker (player) know how much damage was inflicted

            $this->pushToPlayer(
                $attacker,
                new Damage($character->getId(), $damage)
            );
        }

        // If the entity is about to die
        if ($character->getHitPoints() <= 0) {
            if ($character->getKind() instanceof MobKind) {
                /** @var Mob $mob */
                $mob = $character;

                $this->pushToPlayer($attacker, new Kill($mob->getKind()->value));
                $this->pushToAdjacentGroups($mob->getGroup(), $mob->despawn()); // Despawn must be enqueued before the item drop

                $item = $this->getDroppedItem($mob);
                if ($item !== null) {
                    $this->pushToAdjacentGroups($mob->getGroup(), $mob->drop($item));
                    $this->handleItemDespawn($item);
                }
            } else if ($character->getKind() instanceof PlayerKind) {
                /** @var Player $p */
                $p = $character;

                $this->handlePlayerVanish($p);
                $this->pushToAdjacentGroups($character->getGroup(), $character->despawn());
            }

            $this->removeEntity($character);
        }
    }

    /*
    public function despawn(Entity $entity): void
    {
        $this->pushToAdjacentGroups(
            $entity->getGroup(),
            $entity->despawn()
        );

        if (array_key_exists($entity->getId(), $this->entities)) {
            $this->removeEntity($entity);
        }
    }
    */

    /**
     * @throws KindNotFoundException
     */
    public function spawnStaticEntities(): void
    {
        $count = 0;

        foreach ($this->map->getStaticEntities() as $tileId => $kindName) {
            $kind = $this->kindResolver->getKindFromName($kindName);
            $position = $this->map->tileIndexToGridPosition($tileId);

            if ($kind instanceof NpcKind) {
                $this->addNpc(
                    $kind,
                    new Vector2($position->x + 1, $position->y)
                );

                continue;
            }

            if ($kind instanceof MobKind) {
                $self = $this;
                $mob = new Mob(
                    sprintf('7%d%d', $kind->value, ($count++)),
                    $kind,
                    new Vector2($position->x + 1, $position->y)
                );

                $mob->onRespawn(
                    function () use ($mob, $self) {
                        $mob->setIsDead(false);
                        $self->addMob($mob);

                        $area = $mob->getArea();

                        if ($area instanceof ChestArea) {
                            $area->addToArea($mob);
                        }
                    }
                );

                $mob->onMove(
                    $this->onMobMoveCallback(...)
                );
                $this->addMob($mob);
                $this->tryAddingMobToChestArea($mob);

                continue;
            }

            if ($kind instanceof ItemKind || $kind instanceof ArmorKind || $kind instanceof WeaponKind) {
                $this->addStaticItem(
                    $this->createItem(
                        $kind,
                        new Vector2($position->x + 1, $position->y)
                    )
                );
            }
        }
    }

    public function isValidPosition(Vector2 $position): bool
    {
        return !$this->map->isOutOfBounds($position)
            && !$this->map->isColliding($position);
    }

    public function handlePlayerVanish(Player $player): void
    {
        $self = $this;

        /** @var Mob[] $previousAttackers */
        $previousAttackers = [];

        // When a player dies or teleports, all of his attackers go and attack their second most hated $player
        $player->forEachAttacker(
            function (Mob $mob) use (&$previousAttackers, $self) {
                $previousAttackers[] = $mob;
                $self->chooseMobTarget($mob, self::HATE_LOWER_RANK);
            }
        );

        foreach ($previousAttackers as $mob) {
            $player->removeAttacker($mob);
            $mob->clearTarget();
            $mob->forgetPlayer($player->getId(), self::FORGET_PLAYER_DURATION);
        }

        $this->handleEntityGroupMembership($player);
    }

    public function setPlayersCount(int $count): void
    {
        $this->playersCount = $count;
    }

    public function getPlayersCount(): int
    {
        return $this->playersCount;
    }

    public function getMaxPlayersCount(): int
    {
        return $this->maxPlayers;
    }

    /*public function incrementPlayersCount(): void
    {
        $this->setPlayersCount($this->playersCount + 1);
    }*/

    /*public function decrementPlayersCount(): void
    {
        if ($this->playersCount <= 0) {
            $this->playersCount = 0;
            return;
        }

        $this->setPlayersCount($this->playersCount - 1);
    }*/

    /**
     * @throws KindNotFoundException
     */
    public function getDroppedItem(Mob $mob): ?Item
    {
        /** @var MobKind $kind */
        $kind = $mob->getKind();

        $drops = $kind->getProperties()['drops'];
        $value = rand(0, 100);
        $percent = 0;

        foreach ($drops as $itemName => $percentage) {
            $percent += $percentage;

            if ($value <= $percent) {
                /** @var ItemKind|ArmorKind|WeaponKind $droppedItemKind */
                $droppedItemKind = $this->kindResolver->getKindFromName($itemName);

                return $this->addItem(
                    $this->createItem(
                        $droppedItemKind,
                        $mob->getPosition()
                    )
                );
            }
        }

        return null;
    }

    public function onMobMoveCallback(Mob $mob): void
    {
        $mobPosition = $mob->getPosition();

        $this->pushToAdjacentGroups(
            $mob->getGroup(),
            new Move($mob->getId(), $mobPosition->x, $mobPosition->y)
        );

        $this->handleEntityGroupMembership($mob);
    }

    public function findPositionNextTo(Entity $entity, Entity $target): Vector2
    {
        // TODO - Enhance the code below (can create an infinite loop ?)
        while (true) {
            $position = $entity->getPositionNextTo($target);

            if ($this->isValidPosition($position)) {
                return $position;
            }
        }
    }

    public function initZoneGroups(): void
    {
        $self = $this;

        $this->map->forEachGroup(
            function (string $id) use ($self) {
                $self->groups[$id] = [
                    'entities' => [],
                    'players' => [],
                    'incoming' => []
                ];
            }
        );

        $this->zoneGroupsReady = true;
    }

    public function removeFromGroups(Entity $entity): array
    {
        $self = $this;
        $oldGroups = [];
        $entityGroup = $entity->getGroup();

        if ($entityGroup === null) {
            return [];
        }

        if ($entity instanceof Player) {
            $this->groups[$entityGroup]['players'] = Utils::reject(
                $this->groups[$entityGroup]['players'],
                function (string $id) use ($entity) {
                    return $id === $entity->getId();
                }
            );
        }

        $this->map->forEachAdjacentGroup(
            $entityGroup,
            function (string $id) use ($entity, &$oldGroups, $self) {
                if (isset($self->groups[$id]['entities'][$entity->getId()])) {
                    unset($self->groups[$id]['entities'][$entity->getId()]);
                    $oldGroups[] = $id;
                }
            }
        );

        $entity->setGroup(null);

        return $oldGroups;
    }

    /**
     * Registers an entity as "incoming" into several groups, meaning that it just entered them.
     * All players inside these groups will receive a Spawn message when WorldServer.processGroups is called.
     */
    public function addAsIncomingToGroup(Entity $entity, ?string $groupId): void
    {
        if ($groupId === null) {
            // TODO - Exception ?
            return;
        }

        $self = $this;
        $isChest = $entity instanceof Chest;
        $isItem = $entity instanceof Item;

        $isDroppedItem = false;
        if ($isItem) {
            /** @var Item $item */
            $item = $entity;

            $isDroppedItem = !$item->isStatic() && !$item->isFromChest();
        }

        $this->map->forEachAdjacentGroup(
            $groupId,
            function (string $id) use ($self, $isChest, $isItem, $isDroppedItem, $entity) {
                if (!array_key_exists($id, $self->groups)) {
                    return;
                }

                // Items dropped off of mobs are handled differently via DROP messages. See handleHurtEntity.
                if (!array_key_exists($entity->getId(), $self->groups[$id]['entities']) && (!$isItem || $isChest || !$isDroppedItem)) {
                    $self->groups[$id]['incoming'][] = $entity;
                }
            }
        );
    }

    public function addToGroup(Entity $entity, ?string $groupId): array
    {
        if ($groupId === null) {
            // TODO - Exception
            return [];
        }

        $self = $this;
        $newGroups = [];

        if (array_key_exists($groupId, $this->groups)) {
            $this->map->forEachAdjacentGroup(
                $groupId,
                function (string $id) use ($self, &$newGroups, $entity, $groupId) {
                    $self->groups[$id]['entities'][$entity->getId()] = $entity;
                    $newGroups[] = $id;
                }
            );
            $entity->setGroup($groupId);

            if ($entity instanceof Player) {
                $this->groups[$groupId]['players'][] = $entity->getId();
            }
        }

        return $newGroups;
    }

    public function handleEntityGroupMembership(Entity $entity): bool
    {
        $groupId = $this->map->getGroupIdFromPosition($entity->getPosition());
        $entityGroupId = $entity->getGroup();

        if ($entityGroupId === $groupId) {
            return false;
        }

        $this->addAsIncomingToGroup($entity, $groupId);
        $oldGroups = $this->removeFromGroups($entity);
        $newGroups = $this->addToGroup($entity, $groupId);

        if (count($oldGroups) > 0) {
            $entity->setRecentlyLeftGroups(
                array_diff($oldGroups, $newGroups)
            );
        }

        return true;
    }

    public function processGroups(): void
    {
        $self = $this;

        if ($this->zoneGroupsReady) {
            $this->map->forEachGroup(
                function (string $id) use ($self) {
                    if ($self->groups[$id]['incoming'] === null || count($self->groups[$id]['incoming']) === 0) {
                        return;
                    }

                    foreach ($self->groups[$id]['incoming'] as $entity) {
                        if ($entity instanceof Player) {
                            $self->pushToGroup(
                                $id,
                                new Spawn($entity),
                                $entity->getId()
                            );
                        } else {
                            $self->pushToGroup(
                                $id,
                                new Spawn($entity)
                            );
                        }
                    }

                    $self->groups[$id]['incoming'] = [];
                }
            );
        }
    }

    public function moveEntity(Entity $entity, Vector2 $position): void
    {
        $entity->setPosition($position);
        $this->handleEntityGroupMembership($entity);
    }

    public function handleItemDespawn(Item $item): void
    {
        $self = $this;

        $item->handleDespawn(
            self::ITEM_DESPAWN_BEFORE_BLINK_DELAY,
            function () use ($self, $item) {
                $self->pushToAdjacentGroups(
                    $item->getGroup(),
                    new Blink($item->getId())
                );
            },
            self::ITEM_DESPAWN_BLINKING_DURATION,
            function () use ($self, $item) {
                $self->pushToAdjacentGroups(
                    $item->getGroup(),
                    new Destroy($item->getId())
                );

                $self->removeEntity($item);
            }
        );
    }

    public function handleEmptyMobArea(MobArea $area): void
    {

    }

    public function handleEmptyChestArea(ChestArea $area): void
    {
        $chest = $this->addItem(
            $this->createChest($area->getChestPosition(), $area->getItems())
        );
        $this->handleItemDespawn($chest);
    }

    /**
     * @throws KindNotFoundException
     */
    public function handleOpenedChest(Chest $chest): void
    {
        $this->pushToAdjacentGroups($chest->getGroup(), $chest->despawn());
        $this->removeEntity($chest);

        $kindValue = $chest->getRandomItemKindValue();
        if ($kindValue !== null) {
            $kind = $this->kindResolver->getKindFromValue($kindValue);

            if (!$kind instanceof ItemKind && !$kind instanceof ArmorKind && !$kind instanceof WeaponKind) {
                return;
            }

            $item = $this->addItemFromChest(
                $kind,
                $chest->getPosition()
            );

            $this->handleItemDespawn($item);
        }
    }

    public function tryAddingMobToChestArea(Mob $mob): void
    {
        foreach ($this->chestAreas as $area) {
            if ($area->contains($mob)) {
                $area->addToArea($mob);
            }
        }
    }

    public function updatePopulation(?int $totalPlayers = null): void
    {
        $this->pushBroadcast(
            new Population($this->playersCount, $totalPlayers ?? $this->playersCount)
        );
    }

    public function getEnterCallback(): ?callable
    {
        return $this->enterCallback;
    }

    public function incrementPlayerCount(): void
    {
        $this->setPlayersCount($this->playersCount + 1);
    }

    public function decrementPlayerCount(): void
    {
        if ($this->playersCount <= 0) {
            return;
        }

        $this->setPlayersCount($this->playersCount - 1);
    }

    public function getConnectCallback(): ?callable
    {
        return $this->connectCallback;
    }

    public function getMap(): ?Map
    {
        return $this->map;
    }

    public function incrementAndGetPlayerIdCounter(): int
    {
        return $this->playerIdCounter++;
    }
}