<?php

namespace App\Game;

class Utils
{
    public static function any(array $list, callable $callback): bool
    {
        foreach ($list as $item) {
            if (call_user_func($callback, $item)) {
                return true;
            }
        }

        return false;
    }

    public static function reject(array $list, callable $callback): array
    {
        $array = [];

        foreach ($list as $item) {
            if (!call_user_func($callback, $item)) {
                $array[] = $item;
            }
        }

        return $array;
    }

    public static function detect(array $list, callable $callback): mixed
    {
        foreach ($list as $item) {
            if (call_user_func($callback, $item)) {
                return $item;
            }
        }

        return null;
    }
}