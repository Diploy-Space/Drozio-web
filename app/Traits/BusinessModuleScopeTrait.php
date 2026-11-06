<?php

namespace App\Traits;

use App\Enums\BusinessModule;
use App\Services\BusinessModule\BusinessModule as BusinessModuleData;

trait BusinessModuleScopeTrait
{
    /** @return array<int, string> */
    protected function businessModuleAllowList(): array
    {
        return [BusinessModule::RETAIL, BusinessModule::AUCTION, BusinessModule::WHOLESALE];
    }

    /** @return array<string, BusinessModuleData> */
    public function getAvailableModules(): array
    {
        $manager = businessModuleManager();
        $modules = [];
        foreach ($this->businessModuleAllowList() as $key) {
            if ($manager->isAvailableInPanel($key)) {
                $modules[$key] = $manager->get($key);
            }
        }

        return $modules;
    }

    public function resolveModule(?string $requested): string
    {
        return in_array($requested, array_keys($this->getAvailableModules()), true)
            ? $requested
            : BusinessModule::RETAIL;
    }

    /** @return array<string, BusinessModuleData> */
    public function getAvailableModulesWithCommon(): array
    {
        return [BusinessModule::COMMON => new BusinessModuleData(
            key: BusinessModule::COMMON,
            label: 'Common',
            icon: '',
            sections: [],
            requiresAddon: null,
            landing: '',
            isBusiness: false,
        )] + $this->getAvailableModules();
    }

    public function resolveModuleWithCommon(?string $requested): string
    {
        return in_array($requested, array_keys($this->getAvailableModulesWithCommon()), true)
            ? $requested
            : BusinessModule::COMMON;
    }
}
