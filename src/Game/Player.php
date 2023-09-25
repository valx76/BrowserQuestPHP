<?php

namespace App\Game;

use App\Enum\Kinds\ArmorKind;
use App\Enum\Kinds\ItemKind;
use App\Enum\Kinds\PlayerKind;
use App\Enum\Kinds\WeaponKind;
use App\Enum\Message;
use App\Exception\InvalidMessageEventException;
use App\Game\Messages\EquipItem;
use App\Game\Messages\HitPoints;
use App\Game\Messages\MessageInterface;
use Psr\EventDispatcher\EventDispatcherInterface;
use Ratchet\ConnectionInterface;
use React\EventLoop\Loop;
use React\EventLoop\TimerInterface;

class Player extends Character
{
    protected bool $hasEnteredGame;

    protected bool $isDead;

    /**
     * @var Mob[] $haters
     */
    protected array $haters;

    protected ?Checkpoint $lastCheckpoint;

    protected ?TimerInterface $disconnectTimeout;

    protected ?ArmorKind $armor;

    protected int $armorLevel;

    protected ?WeaponKind $weapon;

    protected int $weaponLevel;

    protected ?string $name;

    /**
     * @var ?callable $zoneCallback
     */
    protected $zoneCallback;

    /**
     * @var ?callable $moveCallback
     */
    protected $moveCallback;

    /**
     * @var ?callable $lootMoveCallback
     */
    protected $lootMoveCallback;

    /**
     * @var ?callable $messageCallback
     */
    protected $messageCallback;

    protected ?TimerInterface $firePotionTimeout;

    /**
     * @var ?callable $exitCallback
     */
    protected $exitCallback;

    /**
     * @var ?callable $broadcastCallback
     */
    protected $broadcastCallback;

    /**
     * @var ?callable $broadcastZoneCallback
     */
    protected $broadcastZoneCallback;

    /**
     * @var ?callable $requestPositionCallback
     */
    protected $requestPositionCallback;

    protected int $hate;

    public function __construct(
        protected readonly ConnectionInterface $connection,
        protected readonly World $world,
        protected readonly EventDispatcherInterface $eventDispatcher
    )
    {
        $playerId = intval(sprintf(
            '5%d%d',
            rand(0, 99),
            $world->incrementAndGetPlayerIdCounter()
        ));

        parent::__construct($playerId, PlayerKind::WARRIOR, new Vector2(0, 0));

        $this->hasEnteredGame = false;
        $this->isDead = false;
        $this->haters = [];
        $this->lastCheckpoint = null;
        $this->disconnectTimeout = null;
        $this->armor = null;
        $this->armorLevel = 0;
        $this->weapon = null;
        $this->weaponLevel = 0;
        $this->name = null;
        $this->zoneCallback = null;
        $this->moveCallback = null;
        $this->lootMoveCallback = null;
        $this->messageCallback = null;
        $this->firePotionTimeout = null;
        $this->exitCallback = null;
        $this->requestPositionCallback = null;
        $this->hate = 0;
    }

    public function onClientConnect(): void
    {
        $this->connection->send('go');
    }

    public function onClientMessage(string $data): void
    {
        $message = json_decode($data, true);
        $action = Message::tryFrom($message[0]);

        if (!$this->hasEnteredGame && $action !== Message::HELLO) {
            throw new \RuntimeException('Invalid handshake message');
        }

        $this->resetTimeout();

        try {
            $messageEventClass = $action->getMessageEventClass();

            $this->eventDispatcher->dispatch(
                new $messageEventClass($this, $this->world, $message)
            );
        } catch (InvalidMessageEventException) {
            if ($this->messageCallback !== null) {
                call_user_func($this->messageCallback, $message);
            }
        }
    }

    public function onClientClose(): void
    {
        if ($this->firePotionTimeout !== null) {
            Loop::cancelTimer($this->firePotionTimeout);
            $this->firePotionTimeout = null;
        }

        if ($this->disconnectTimeout !== null) {
            Loop::cancelTimer($this->disconnectTimeout);
            $this->disconnectTimeout = null;
        }

        if ($this->exitCallback !== null) {
            call_user_func($this->exitCallback);
        }
    }

    public function firePotionTimeoutCallback(): void
    {
        $this->broadcast(
            $this->equip($this->armor)
        );

        $this->firePotionTimeout = null;
    }

    public function destroy(): void
    {
        $this->forEachAttacker(fn (Mob $mob) => $mob->clearTarget());
        $this->attackers = [];

        $this->forEachHater(
            $this->forEachHaterCallback(...)
        );
        $this->haters = [];
    }

    public function forEachHaterCallback(Mob $mob): void
    {
        $mob->forgetPlayer($this->id);
    }

    public function getState(): array
    {
        $baseState = $this->getBaseState();
        $state = [$this->name, $this->orientation->value, $this->armor, $this->weapon];

        if ($this->targetId !== null) {
            $state[] = $this->targetId;
        }

        return array_merge($baseState, $state);
    }

    public function send(string $message): void
    {
        $this->connection->send($message);
    }

    public function broadcast(MessageInterface $message, bool $ignoreSelf = true): void
    {
        if ($this->broadcastCallback !== null) {
            call_user_func($this->broadcastCallback, $message, $ignoreSelf);
        }
    }

    public function broadcastToZone(MessageInterface $message, $ignoreSelf = true): void
    {
        if ($this->broadcastZoneCallback !== null) {
            call_user_func($this->broadcastZoneCallback, $message, $ignoreSelf);
        }
    }

    public function onExit(callable $callback): void
    {
        $this->exitCallback = $callback;
    }

    public function onMove(callable $callback): void
    {
        $this->moveCallback = $callback;
    }

    public function onLootMove(callable $callback): void
    {
        $this->lootMoveCallback = $callback;
    }

    public function onZone(callable $callback): void
    {
        $this->zoneCallback = $callback;
    }

    public function onMessage(callable $callback): void
    {
        $this->messageCallback = $callback;
    }

    public function onBroadcast(callable $callback): void
    {
        $this->broadcastCallback = $callback;
    }

    public function onBroadcastToZone(callable $callback): void
    {
        $this->broadcastZoneCallback = $callback;
    }

    public function equip(ItemKind|WeaponKind|ArmorKind $kind): EquipItem
    {
        return new EquipItem($this->id, $kind->value);
    }

    public function addHater(Mob $mob): void
    {
        if (!array_key_exists($mob->getId(), $this->haters)) {
            $this->haters[$mob->getId()] = $mob;
        }
    }

    public function removeHater(Mob $mob): void
    {
        unset($this->haters[$mob->getId()]);
    }

    public function forEachHater(callable $callback): void
    {
        array_walk(
            $this->haters,
            function (Mob $mob) use ($callback) {
                call_user_func($callback, $mob);
            }
        );
    }

    public function equipArmor(ArmorKind $kind): void
    {
        $this->armor = $kind;
        $this->armorLevel = $kind->getLevel();
    }

    public function equipWeapon(WeaponKind $kind): void
    {
        $this->weapon = $kind;
        $this->weaponLevel = $kind->getLevel();
    }

    public function equipItem(Item $item): void
    {
        $kind = $item->getKind();

        if ($kind instanceof ArmorKind) {
            $this->equipArmor($kind);
            $this->updateHitPoints();

            $msg = new HitPoints($this->maxHitPoints);
            $this->send(
                json_encode($msg->serialize())
            );

            return;
        }

        if ($kind instanceof WeaponKind) {
            $this->equipWeapon($kind);
        }
    }

    public function updateHitPoints(): void
    {
        $this->resetHitPoints(
            Formulas::hp($this->armorLevel)
        );
    }

    public function updatePosition(): void
    {
        if ($this->requestPositionCallback !== null) {
            $position = call_user_func($this->requestPositionCallback);
            $this->setPosition($position);
        }
    }

    public function onRequestPosition(callable $callback): void
    {
        $this->requestPositionCallback = $callback;
    }

    public function resetTimeout(): void
    {
        if ($this->disconnectTimeout !== null) {
            Loop::cancelTimer($this->disconnectTimeout);
        }

        $this->disconnectTimeout = Loop::addPeriodicTimer(
            15*60,
            $this->timeout(...)
        );
    }

    public function timeout(): void
    {
        $this->connection->send('timeout');
        echo 'Player was idle for too long' . PHP_EOL;
    }

    public function getLastCheckpoint(): ?Checkpoint
    {
        return $this->lastCheckpoint;
    }

    public function hasEnteredGame(): bool
    {
        return $this->hasEnteredGame;
    }

    public function setHasEnteredGame(bool $hasEnteredGame): void
    {
        $this->hasEnteredGame = $hasEnteredGame;
    }

    public function isDead(): bool
    {
        return $this->isDead;
    }

    public function setIsDead(bool $isDead): void
    {
        $this->isDead = $isDead;
    }

    public function increaseHate(int $hate): void
    {
        $this->hate += $hate;
    }

    public function getHate(): int
    {
        return $this->hate;
    }

    public function getConnection(): ConnectionInterface
    {
        return $this->connection;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function getMoveCallback(): ?callable
    {
        return $this->moveCallback;
    }

    public function getLootMoveCallback(): ?callable
    {
        return $this->lootMoveCallback;
    }

    public function getWeaponLevel(): int
    {
        return $this->weaponLevel;
    }

    public function getArmorLevel(): int
    {
        return $this->armorLevel;
    }

    public function getFirePotionTimeout(): ?TimerInterface
    {
        return $this->firePotionTimeout;
    }

    public function setFirePotionTimeout(?TimerInterface $firePotionTimeout): void
    {
        $this->firePotionTimeout = $firePotionTimeout;
    }

    public function getZoneCallback(): ?callable
    {
        return $this->zoneCallback;
    }

    public function setLastCheckpoint(?Checkpoint $checkpoint): void
    {
        $this->lastCheckpoint = $checkpoint;
    }
}