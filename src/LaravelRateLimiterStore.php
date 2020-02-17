<?php

namespace ViaWork\LeverPhp;

use Illuminate\Support\Facades\Cache;
use Spatie\GuzzleRateLimiterMiddleware\Store;

class LaravelRateLimiterStore implements Store
{
    public function get(): array
    {
        return Cache::get('lever-rate-limiter', []);
    }

    public function push(int $timestamp)
    {
        Cache::put('lever-rate-limiter', array_merge($this->get(), [$timestamp]));
    }
}
