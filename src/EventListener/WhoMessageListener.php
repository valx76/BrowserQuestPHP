<?php

namespace App\EventListener;

use App\Event\WhoMessageEvent;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;

#[AsEventListener]
class WhoMessageListener
{
    public function __invoke(WhoMessageEvent $event): void
    {
        $player = $event->getPlayer();
        $world = $event->getWorld();

        $message = $event->getMessage();

        array_shift($message);
        $world->pushSpawnsToPlayer($player, $message);
    }
}