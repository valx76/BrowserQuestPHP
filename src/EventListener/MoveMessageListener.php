<?php

namespace App\EventListener;

use App\Event\MoveMessageEvent;
use App\Game\Messages\Move;
use App\Game\Vector2;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;

#[AsEventListener]
class MoveMessageListener
{
    public function __invoke(MoveMessageEvent $event): void
    {
        $player = $event->getPlayer();
        $world = $event->getWorld();

        $moveCallback = $player->getMoveCallback();

        if ($moveCallback !== null) {
            $position = new Vector2($event->getPositionX(), $event->getPositionY());
            $playerPosition = $player->getPosition();

            if ($world->isValidPosition($position)) {
                $player->setPosition($position);
                $player->clearTarget();

                $player->broadcast(
                    new Move($player->getId(), $playerPosition->x, $playerPosition->y)
                );

                call_user_func($moveCallback, $playerPosition);
            }
        }
    }
}