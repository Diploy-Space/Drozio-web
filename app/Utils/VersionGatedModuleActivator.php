<?php

namespace App\Utils;

use Nwidart\Modules\Activators\FileActivator;
use Nwidart\Modules\Module;

class VersionGatedModuleActivator extends FileActivator
{
    private const ADDON_INFO_FILE = '/Addon/info.php';

    private array $supportedVersions = [];

    public function hasStatus(Module $module, bool $status): bool
    {
        if (! $this->isVersionSupported($module)) {
            return $status === false;
        }

        return parent::hasStatus($module, $status);
    }

    private function isVersionSupported(Module $module): bool
    {
        if (! array_key_exists($module->getName(), $this->supportedVersions)) {
            $installedVersion = $this->installedVersion($module);

            $isVersionSupported = ! function_exists('isAddonVersionSupported')
                || isAddonVersionSupported(moduleName: $module->getName(), version: $installedVersion);
            $isUpdatePending = function_exists('isAddonUpdatePending')
                && isAddonUpdatePending(moduleName: $module->getName(), version: $installedVersion);

            $this->supportedVersions[$module->getName()] = $isVersionSupported && ! $isUpdatePending;
        }

        return $this->supportedVersions[$module->getName()];
    }

    private function installedVersion(Module $module): mixed
    {
        $infoPath = $module->getPath().self::ADDON_INFO_FILE;

        if (! is_file($infoPath)) {
            return null;
        }

        $info = include $infoPath;

        return is_array($info) ? ($info['version'] ?? null) : null;
    }
}
