<?php

namespace App\EventListener;

use App\Event\LootMessageEvent;
use App\Enum\Kinds\ArmorKind;
use App\Enum\Kinds\ItemKind;
use App\Enum\Kinds\WeaponKind;
use App\Game\Item;
use App\Game\Messages\HitPoints;
use React\EventLoop\Loop;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;

#[AsEventListener]
class LootMessageListener
{
    public const FIREPOTION_TIMEOUT_DELAY = 15;
    public const HP_AMOUNT_FLASK = 40;
    public const HP_AMOUNT_BURGER = 100;

    public function __invoke(LootMessageEvent $event): void
    {
        $player = $event->getPlayer();
        $world = $event->getWorld();

        /** @var ?Item $item */
        $item = $world->getEntityById($event->getItemId());

        if ($item instanceof Item) {
            $kind = $item->getKind();

            if ($kind instanceof WeaponKind || $kind instanceof ArmorKind || ($kind instanceof ItemKind && $kind !== ItemKind::CHEST)) {
                $player->broadcast($item->despawn());
                $world->removeEntity($item);

                if ($kind === ItemKind::FIREPOTION) {
                    $player->updateHitPoints();
                    $player->broadcast($player->equip($kind));

                    $player->setFirePotionTimeout(
                        Loop::addTimer(self::FIREPOTION_TIMEOUT_DELAY, $player->firePotionTimeoutCallback(...))
                    );

                    $hitPoints = new HitPoints($player->getMaxHitPoints());
                    $data = $hitPoints->serialize();
                    $player->getConnection()->send(json_encode($data));
                } else if ($kind === ItemKind::FLASK || $kind === ItemKind::BURGER) {
                    if (!$player->hasFullHealth()) {
                        $amount = match ($kind) {
                            ItemKind::FLASK => self::HP_AMOUNT_FLASK,
                            ItemKind::BURGER => self::HP_AMOUNT_BURGER,
                            default => throw new \RuntimeException('Cannot happen!')
                        };

                        $player->regenHealthBy($amount);
                        $world->pushToPlayer($player, $player->health());
                    }
                } else if ($kind instanceof ArmorKind || $kind instanceof WeaponKind) {
                    $player->equipItem($item);
                    $player->broadcast($player->equip($kind));
                }
            }
        }
    }
}