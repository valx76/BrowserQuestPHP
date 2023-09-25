<?php

namespace App\Event;

use App\Game\Player;
use App\Game\World;
use Symfony\Contracts\EventDispatcher\Event;

abstract class AbstractMessageEvent extends Event
{
    public function __construct(
        protected readonly Player $player,
        protected readonly World $world,
        protected readonly array $message
    )
    {
    }

    public function getPlayer(): Player
    {
        return $this->player;
    }

    public function getWorld(): World
    {
        return $this->world;
    }

    public function getMessage(): array
    {
        return $this->message;
    }
}