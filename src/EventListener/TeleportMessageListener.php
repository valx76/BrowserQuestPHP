<?php

namespace App\EventListener;

use App\Event\TeleportMessageEvent;
use App\Game\Messages\Teleport;
use App\Game\Vector2;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;

#[AsEventListener]
class TeleportMessageListener
{
    public function __invoke(TeleportMessageEvent $event): void
    {
        $player = $event->getPlayer();
        $world = $event->getWorld();

        $position = new Vector2($event->getPositionX(), $event->getPositionY());

        if ($world->isValidPosition($position)) {
            $player->setPosition($position);
            $player->clearTarget();

            $playerPosition = $player->getPosition();

            $player->broadcast(
                new Teleport($player->getId(), $playerPosition->x, $playerPosition->y)
            );

            $world->handlePlayerVanish($player);
            $world->pushRelevantEntityListTo($player);
        }
    }
}