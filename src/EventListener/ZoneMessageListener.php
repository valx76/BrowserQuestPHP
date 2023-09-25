<?php

namespace App\EventListener;

use App\Event\ZoneMessageEvent;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;

#[AsEventListener]
class ZoneMessageListener
{
    public function __invoke(ZoneMessageEvent $event): void
    {
        $player = $event->getPlayer();

        $zoneCallback = $player->getZoneCallback();

        if ($zoneCallback === null) {
            throw new \RuntimeException('zoneCallback has not been set!');
        }

        call_user_func($player->getZoneCallback());
    }
}