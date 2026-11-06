<?php

namespace Modules\TaxModule\app\Traits;

use App\Enums\BusinessModule;

trait BusinessModuleTabsTrait
{
    protected function businessModuleTabs(string $activeBusinessModule, array $routeNames): array
    {
        $availableModules = $this->availableBusinessModules();

        if (count($availableModules) < 2) {
            return [];
        }

        return array_map(fn (string $businessModule): array => [
            'label' => $this->businessModuleLabel($businessModule),
            'url' => route($routeNames[$businessModule]),
            'active' => $activeBusinessModule === $businessModule,
        ], $availableModules);
    }

    protected function availableBusinessModules(): array
    {
        return array_values(array_filter(
            BusinessModule::values(),
            fn (string $businessModule): bool => $this->isBusinessModuleAvailable($businessModule),
        ));
    }

    protected function isBusinessModuleAvailable(string $businessModule): bool
    {
        return function_exists('businessModuleManager') && businessModuleManager()->isAvailableInPanel($businessModule);
    }

    protected function businessModuleLabel(string $businessModule): string
    {
        return match ($businessModule) {
            BusinessModule::WHOLESALE => translate('Wholesale'),
            BusinessModule::AUCTION => translate('Auction'),
            default => translate('Retail'),
        };
    }
}
