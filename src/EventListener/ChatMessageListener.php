<?php

namespace App\EventListener;

use App\Event\ChatMessageEvent;
use App\Game\Messages\Chat;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;

#[AsEventListener]
class ChatMessageListener
{
    public function __invoke(ChatMessageEvent $event): void
    {
        $player = $event->getPlayer();

        $message = trim($event->getWrittenMessage());

        if (strlen($message) > 0) {
            $player->broadcastToZone(
                new Chat($player->getId(), $message),
                false
            );
        }
    }
}