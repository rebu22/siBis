<?php

namespace App\Support;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

/** Kuota harian tamu (tanpa akun), dihitung per token cookie di cache. */
final class GuestQuota
{
    public const DAILY_LIMIT = 5;

    public static function remaining(Request $request): int
    {
        return max(0, self::DAILY_LIMIT - (int) Cache::get(self::key($request), 0));
    }

    public static function consume(Request $request): void
    {
        $key = self::key($request);

        if (! Cache::add($key, 1, now()->endOfDay())) {
            Cache::increment($key);
        }
    }

    private static function key(Request $request): string
    {
        return 'guest-quota:'.sha1((string) $request->attributes->get('guest_token')).':'.now()->toDateString();
    }
}
