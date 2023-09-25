<?php

namespace App\EventListener;

use App\Event\AttackMessageEvent;
use App\Game\Mob;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;

#[AsEventListener]
class AttackMessageListener
{
    public function __invoke(AttackMessageEvent $event): void
    {
        $player = $event->getPlayer();
        $world = $event->getWorld();

        /** @var ?Mob $mob */
        $mob = $world->getEntityById($event->getMobId());

        if ($mob instanceof Mob) {
            $player->setTarget($mob);
            $world->broadcastAttacker($player);
        }
    }
}