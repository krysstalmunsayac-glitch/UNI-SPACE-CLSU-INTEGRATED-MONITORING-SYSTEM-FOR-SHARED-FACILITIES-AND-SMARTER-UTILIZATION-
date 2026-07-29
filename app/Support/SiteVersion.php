<?php

namespace App\Support;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class SiteVersion
{
    private const CACHE_KEY = 'site-content-version';

    public static function current(): string
    {
        return Cache::rememberForever(self::CACHE_KEY, fn () => (string) Str::uuid());
    }

    public static function bump(): string
    {
        $version = (string) Str::uuid();
        Cache::forever(self::CACHE_KEY, $version);

        return $version;
    }
}
