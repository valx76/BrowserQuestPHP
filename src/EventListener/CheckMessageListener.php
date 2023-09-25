<?php

namespace App\EventListener;

use App\Event\CheckMessageEvent;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;

#[AsEventListener]
class CheckMessageListener
{
    public function __invoke(CheckMessageEvent $event): void
    {
        $player = $event->getPlayer();
        $world = $event->getWorld();

        $checkpoint = $world->getMap()?->getCheckpoint($event->getCheckpointId());

        if ($checkpoint !== null) {
            $player->setLastCheckpoint($checkpoint);
        }
    }
}