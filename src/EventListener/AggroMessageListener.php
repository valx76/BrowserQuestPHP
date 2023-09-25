<?php

namespace App\EventListener;

use App\Event\AggroMessageEvent;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;

#[AsEventListener]
class AggroMessageListener
{
    public const MOB_HATE_POINT = 5;

    public function __invoke(AggroMessageEvent $event): void
    {
        $player = $event->getPlayer();
        $world = $event->getWorld();

        if ($player->getMoveCallback() !== null) {
            $world->handleMobHate(
                $event->getMobId(),
                $player->getId(),
                self::MOB_HATE_POINT
            );
        }
    }
}