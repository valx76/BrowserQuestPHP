<?php

namespace App\Game\Messages;

interface MessageInterface
{
    public function serialize(): array;
}