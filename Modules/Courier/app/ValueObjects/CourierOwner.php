<?php

namespace Modules\Courier\app\ValueObjects;

final class CourierOwner
{
    public const TYPE_PLATFORM = 'platform';

    public const TYPE_VENDOR = 'vendor';

    public const BUSINESS_MODULE_RETAIL = 'retail';

    public const BUSINESS_MODULE_WHOLESALE = 'wholesale';

    private const ROUTE_KEY_SEPARATOR = '-';

    private function __construct(
        public readonly string $type,
        public readonly int $id,
        public readonly string $businessModule = self::BUSINESS_MODULE_RETAIL,
    ) {}

    public static function platform(string $businessModule = self::BUSINESS_MODULE_RETAIL): self
    {
        return new self(type: self::TYPE_PLATFORM, id: 0, businessModule: self::normalizeBusinessModule($businessModule));
    }

    public static function vendor(int $id, string $businessModule = self::BUSINESS_MODULE_RETAIL): self
    {
        return new self(type: self::TYPE_VENDOR, id: $id, businessModule: self::normalizeBusinessModule($businessModule));
    }

    public static function of(string $type, int|string|null $id, ?string $businessModule = null): self
    {
        $businessModule = self::normalizeBusinessModule($businessModule);

        return $type === self::TYPE_PLATFORM || $type === ''
            ? self::platform(businessModule: $businessModule)
            : new self(type: $type, id: (int) $id, businessModule: $businessModule);
    }

    public static function fromRouteKey(?string $key): self
    {
        if ($key === null || ! str_contains($key, self::ROUTE_KEY_SEPARATOR)) {
            return self::platform();
        }

        $segments = explode(self::ROUTE_KEY_SEPARATOR, $key, 3);

        return self::of(type: $segments[0], id: $segments[1], businessModule: $segments[2] ?? null);
    }

    public function routeKey(): string
    {
        $key = $this->type.self::ROUTE_KEY_SEPARATOR.$this->id;

        return $this->isRetail() ? $key : $key.self::ROUTE_KEY_SEPARATOR.$this->businessModule;
    }

    public function inBusinessModule(string $businessModule): self
    {
        return new self(type: $this->type, id: $this->id, businessModule: self::normalizeBusinessModule($businessModule));
    }

    public function isPlatform(): bool
    {
        return $this->type === self::TYPE_PLATFORM;
    }

    public function isRetail(): bool
    {
        return $this->businessModule === self::BUSINESS_MODULE_RETAIL;
    }

    public function isDefaultPlatform(): bool
    {
        return $this->isPlatform() && $this->isRetail();
    }

    public function equals(self $other): bool
    {
        return $this->type === $other->type
            && $this->id === $other->id
            && $this->businessModule === $other->businessModule;
    }

    private static function normalizeBusinessModule(?string $businessModule): string
    {
        return $businessModule === null || $businessModule === '' ? self::BUSINESS_MODULE_RETAIL : $businessModule;
    }
}
