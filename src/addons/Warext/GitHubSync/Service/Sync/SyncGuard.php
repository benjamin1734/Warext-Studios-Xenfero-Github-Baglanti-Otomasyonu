<?php

namespace Warext\GitHubSync\Service\Sync;

final class SyncGuard
{
    private static int $depth = 0;

    public static function active(): bool
    {
        return self::$depth > 0;
    }

    public static function run(callable $callback)
    {
        self::$depth++;
        try
        {
            return $callback();
        }
        finally
        {
            self::$depth = max(0, self::$depth - 1);
        }
    }
}
