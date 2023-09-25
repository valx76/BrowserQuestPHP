<?php

namespace App\EventListener;

use App\Event\HitMessageEvent;
use App\Game\Formulas;
use App\Game\Mob;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;

#[AsEventListener]
class HitMessageListener
{
    public function __invoke(HitMessageEvent $event): void
    {
        $player = $event->getPlayer();
        $world = $event->getWorld();

        /** @var ?Mob $mob */
        $mob = $world->getEntityById($event->getMobId());

        if ($mob instanceof Mob) {
            $dmg = Formulas::dmg($player->getWeaponLevel(), $mob->getArmorLevel());

            if ($dmg > 0 && is_callable($mob->receiveDamage(...))) {
                $mob->receiveDamage($dmg, $player->getId());
                $world->handleMobHate($mob->getId(), $player->getId(), $dmg);
                $world->handleHurtEntity($mob, $player, $dmg);
            }
        }
    }
}