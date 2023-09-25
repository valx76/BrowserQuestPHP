<?php

namespace App\Enum;

use App\Event\AggroMessageEvent;
use App\Event\AttackMessageEvent;
use App\Event\ChatMessageEvent;
use App\Event\CheckMessageEvent;
use App\Event\HelloMessageEvent;
use App\Event\HitMessageEvent;
use App\Event\HurtMessageEvent;
use App\Event\LootMessageEvent;
use App\Event\LootMoveMessageEvent;
use App\Event\MoveMessageEvent;
use App\Event\OpenMessageEvent;
use App\Event\TeleportMessageEvent;
use App\Event\WhoMessageEvent;
use App\Event\ZoneMessageEvent;
use App\Exception\InvalidMessageEventException;

enum Message: int
{
    case HELLO = 0;
    CASE WELCOME = 1;
    CASE SPAWN = 2;
    CASE DESPAWN = 3;
    CASE MOVE = 4;
    CASE LOOTMOVE = 5;
    CASE AGGRO = 6;
    CASE ATTACK = 7;
    CASE HIT = 8;
    CASE HURT = 9;
    CASE HEALTH = 10;
    CASE CHAT = 11;
    CASE LOOT = 12;
    CASE EQUIP = 13;
    CASE DROP = 14;
    CASE TELEPORT = 15;
    CASE DAMAGE = 16;
    CASE POPULATION = 17;
    CASE KILL = 18;
    CASE LISTS = 19;
    CASE WHO = 20;
    CASE ZONE = 21;
    CASE DESTROY = 22;
    CASE HP = 23;
    CASE BLINK = 24;
    CASE OPEN = 25;
    CASE CHECK = 26;

    /**
     * @throws InvalidMessageEventException
     */
    public function getMessageEventClass(): string
    {
        return match($this) {
            self::HELLO => HelloMessageEvent::class,
            self::MOVE => MoveMessageEvent::class,
            self::LOOTMOVE => LootMoveMessageEvent::class,
            self::AGGRO => AggroMessageEvent::class,
            self::ATTACK => AttackMessageEvent::class,
            self::HIT => HitMessageEvent::class,
            self::HURT => HurtMessageEvent::class,
            self::CHAT => ChatMessageEvent::class,
            self::LOOT => LootMessageEvent::class,
            self::TELEPORT => TeleportMessageEvent::class,
            self::WHO => WhoMessageEvent::class,
            self::ZONE => ZoneMessageEvent::class,
            self::OPEN => OpenMessageEvent::class,
            self::CHECK => CheckMessageEvent::class,
            default => throw new InvalidMessageEventException()
        };
    }
}
