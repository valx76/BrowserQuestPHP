<?php

namespace App\EventListener;

use App\Event\LootMoveMessageEvent;
use App\Game\Item;
use App\Game\Messages\LootMove;
use App\Game\Vector2;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;

#[AsEventListener]
class LootMoveMessageListener
{
    public function __invoke(LootMoveMessageEvent $event): void
    {
        $player = $event->getPlayer();
        $world = $event->getWorld();

        $lootMoveCallback = $player->getLootMoveCallback();

        if ($lootMoveCallback !== null) {
            $position = new Vector2($event->getPositionX(), $event->getPositionY());
            $player->setPosition($position);

            /** @var ?Item $item */
            $item = $world->getEntityById($event->getItemId());

            if ($item instanceof Item) {
                $player->clearTarget();

                $player->broadcast(
                    new LootMove($player->getId(), $item->getId())
                );

                call_user_func($lootMoveCallback, $player->getPosition());
            }
        }
    }
}