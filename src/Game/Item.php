<?php

namespace App\Game;

use App\Enum\Kinds\ArmorKind;
use App\Enum\Kinds\ItemKind;
use App\Enum\Kinds\WeaponKind;
use React\EventLoop\Loop;
use React\EventLoop\TimerInterface;

class Item extends Entity
{
    protected bool $isStatic;

    protected bool $isFromChest;

    protected ?TimerInterface $blinkTimeout;

    protected ?TimerInterface $despawnTimeout;

    /** @var ?callable $respawnCallback */
    protected $respawnCallback;

    public function __construct(int $id, ItemKind|ArmorKind|WeaponKind $kind, Vector2 $position)
    {
        parent::__construct($id, $kind, $position);

        $this->isStatic = false;
        $this->isFromChest = false;
        $this->blinkTimeout = null;
        $this->despawnTimeout = null;
        $this->respawnCallback = null;
    }

    public function handleDespawn(
        int $beforeBlinkDelay,
        callable $blinkCallback,
        int $blinkingDuration,
        callable $despawnCallback
    ): void
    {
        $self = $this;

        $this->blinkTimeout = Loop::addTimer(
            $beforeBlinkDelay,
            function () use ($self, $blinkCallback, $blinkingDuration, $despawnCallback) {
                call_user_func($blinkCallback);

                $self->despawnTimeout = Loop::addTimer(
                    $blinkingDuration,
                    $despawnCallback
                );
            }
        );
    }

    public function destroy(): void
    {
        if ($this->blinkTimeout !== null) {
            Loop::cancelTimer($this->blinkTimeout);
            $this->blinkTimeout = null;
        }

        if ($this->despawnTimeout !== null) {
            Loop::cancelTimer($this->despawnTimeout);
            $this->despawnTimeout = null;
        }

        if ($this->isStatic) {
            $this->scheduleRespawn(30_000);
        }
    }

    public function scheduleRespawn(int $delay): void
    {
        $self = $this;

        Loop::addTimer(
            $delay,
            function () use ($self) {
                if ($self->respawnCallback !== null) {
                    call_user_func($self->respawnCallback);
                }
            }
        );
    }

    public function onRespawn(callable $callback): void
    {
        $this->respawnCallback = $callback;
    }

    public function setStatic(bool $isStatic): void
    {
        $this->isStatic = $isStatic;
    }

    public function setIsFromChest(bool $isFromChest): void
    {
        $this->isFromChest = $isFromChest;
    }

    public function isStatic(): bool
    {
        return $this->isStatic;
    }

    public function isFromChest(): bool
    {
        return $this->isFromChest;
    }
}