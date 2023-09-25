<?php

namespace App\EventListener;

use App\Event\HurtMessageEvent;
use App\Game\Formulas;
use App\Game\Mob;
use React\EventLoop\Loop;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;

#[AsEventListener]
class HurtMessageListener
{
    public function __invoke(HurtMessageEvent $event): void
    {
        $player = $event->getPlayer();
        $world = $event->getWorld();

        /** @var ?Mob $mob */
        $mob = $world->getEntityById($event->getMobId());

        $playerHitPoints = $player->getHitPoints();

        if ($mob instanceof Mob && $playerHitPoints > 0) {
            $player->setHitPoints(
                $player->getHitPoints() - Formulas::dmg($mob->getWeaponLevel(), $player->getArmorLevel())
            );
            $world->handleHurtEntity($player);

            if ($player->getHitPoints() <= 0) {
                $player->setIsDead(true);

                $firePotionTimeout = $player->getFirePotionTimeout();

                if ($firePotionTimeout !== null) {
                    Loop::cancelTimer($firePotionTimeout);
                    $player->setFirePotionTimeout(null);
                }
            }
        }
    }
}