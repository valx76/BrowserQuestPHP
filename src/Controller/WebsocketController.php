<?php

namespace App\Controller;

use App\Game\Player;
use App\Game\Utils;
use App\Game\World;
use Psr\EventDispatcher\EventDispatcherInterface;
use Ratchet\ConnectionInterface;
use Ratchet\MessageComponentInterface;

class WebsocketController implements MessageComponentInterface
{
    /**
     * @var World[] $worlds
     */
    private array $worlds;

    /**
     * @var \SplObjectStorage<ConnectionInterface, Player> $players
     */
    private \SplObjectStorage $players;

    public function __construct(
        private readonly EventDispatcherInterface $eventDispatcher
    )
    {
        $this->worlds = [];
        $this->players = new \SplObjectStorage();
    }

    public function onOpen(ConnectionInterface $conn): void
    {
        /** @var ?World $world */
        $world = Utils::detect(
            $this->worlds,
            function (World $world) {
                return $world->getPlayersCount() < $world->getMaxPlayersCount();
            }
        );

        if ($world === null) {
            // TODO - Log message that all servers are full
            return;
        }

        $player = new Player($conn, $world, $this->eventDispatcher);
        $this->players->attach($conn, $player);

        $world->updatePopulation();

        $connectCallback = $world->getConnectCallback();
        if ($connectCallback !== null) {
            call_user_func($connectCallback, $player);
        }

        $player->onClientConnect();
    }

    public function onClose(ConnectionInterface $conn): void
    {
        $conn->close();

        if ($this->players->contains($conn)) {
            /** @var Player $player */
            $player = $this->players->offsetGet($conn);

            $player->onClientClose();

            $this->players->detach($conn);
        }
    }

    public function onError(ConnectionInterface $conn, \Exception $e): void
    {
        $conn->close();

        if ($this->players->contains($conn)) {
            $this->players->detach($conn);
        }

        dump('Error: ' . $e->getMessage());
    }

    public function onMessage(ConnectionInterface $from, $msg): void
    {
        if ($this->players->contains($from)) {
            /** @var Player $player */
            $player = $this->players->offsetGet($from);

            $player->onClientMessage($msg);
        }
    }

    /**
     * @param World[] $worlds
     */
    public function setWorlds(array $worlds): void
    {
        $this->worlds = $worlds;
    }
}