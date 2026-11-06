<?php

namespace App\Services\BusinessModule;

final class BusinessModule
{
    public function __construct(
        public readonly string $key,
        public readonly string $label,
        public readonly string $icon,
        public readonly array $sections,
        public readonly ?string $requiresAddon,
        public readonly string $landing,
        public readonly string|array|null $requiresSetting = null,
        public readonly string|array|null $storefrontRequiresSetting = null,
        public readonly array $urlPatterns = [],
        public readonly string $dashboardRoute = '',
        public readonly bool $isBusiness = true,
        public readonly string $landingRoute = '',
        public readonly array $requiresPermission = [],
        public readonly array $landingRoutes = [],
    ) {}

    public function hasSection(string $section): bool
    {
        return in_array($section, $this->sections, true);
    }
}
