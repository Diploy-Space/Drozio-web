<?php

namespace App\Traits;

use Carbon\Carbon;
use Closure;
use Illuminate\Support\Facades\Cache;

trait SaleCacheTrait
{
    private const SALE_CACHE_MISS = '__sale_cache_miss__';

    private const SALE_CACHE_TTL_FLOOR = 60;

    protected function saleCacheRegistry(): string
    {
        return CACHE_FLASH_DEAL_KEYS;
    }

    protected function saleCacheBoundary(): ?Carbon
    {
        return null;
    }

    protected function rememberSaleCache(string $key, Closure $valueResolver): mixed
    {
        $value = Cache::get($key, self::SALE_CACHE_MISS);

        if ($value !== self::SALE_CACHE_MISS) {
            return $value;
        }

        $value = $valueResolver();

        Cache::put($key, $value, $this->saleCacheTtl());
        $this->registerSaleCacheKey($key);

        return $value;
    }

    protected function forgetSaleCache(): void
    {
        cacheGroupRemoveByType($this->saleCacheRegistry());
    }

    private function saleCacheTtl(): int
    {
        $boundary = $this->saleCacheBoundary();

        if ($boundary === null) {
            return CACHE_FOR_3_HOURS;
        }

        return max(self::SALE_CACHE_TTL_FLOOR, min(CACHE_FOR_3_HOURS, (int) Carbon::now()->diffInSeconds($boundary, false)));
    }

    private function registerSaleCacheKey(string $key): void
    {
        $registry = $this->saleCacheRegistry();
        $keys = Cache::get($registry, []);

        if (! in_array($key, $keys, true)) {
            $keys[] = $key;
        }

        Cache::put($registry, $keys, CACHE_FOR_3_HOURS);
    }
}
