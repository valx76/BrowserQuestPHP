<?php

namespace App\EventListener;

use App\Event\OpenMessageEvent;
use App\Exception\KindNotFoundException;
use App\Game\Chest;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;

#[AsEventListener]
class OpenMessageListener
{
    /**
     * @throws KindNotFoundException
     */
    public function __invoke(OpenMessageEvent $event): void
    {
        $world = $event->getWorld();

        /** @var ?Chest $chest */
        $chest = $world->getEntityById($event->getChestId());

        if ($chest instanceof Chest) {
            $world->handleOpenedChest($chest);
        }
    }
}