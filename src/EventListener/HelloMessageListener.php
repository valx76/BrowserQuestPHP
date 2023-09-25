<?php

namespace App\EventListener;

use App\Event\HelloMessageEvent;
use App\Enum\Kinds\ArmorKind;
use App\Enum\Kinds\PlayerKind;
use App\Enum\Kinds\WeaponKind;
use App\Enum\Message;
use App\Enum\Orientation;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;

#[AsEventListener]
class HelloMessageListener
{
    public function __invoke(HelloMessageEvent $event): void
    {
        $player = $event->getPlayer();
        $world = $event->getWorld();

        $name = $event->getName();
        $player->setName(strlen($name) === 0 ? 'lorem ipsum' : $name);
        $player->setKind(PlayerKind::WARRIOR);

        $player->equipArmor(ArmorKind::from($event->getArmorId()));
        $player->equipWeapon(WeaponKind::from($event->getWeaponId()));

        $player->setOrientation(Orientation::random());
        $player->updateHitPoints();
        $player->updatePosition();

        $world->addPlayer($player);
        call_user_func($world->getEnterCallback(), $player);

        $position = $player->getPosition();

        $player->getConnection()->send(
            json_encode([
                Message::WELCOME,
                $player->getId(),
                $player->getName(),
                $position->x,
                $position->y,
                $player->getHitPoints()
            ])
        );

        $player->setHasEnteredGame(true);
        $player->setIsDead(false);
    }
}