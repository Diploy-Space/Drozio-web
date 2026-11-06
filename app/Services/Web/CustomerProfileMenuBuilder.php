<?php

namespace App\Services\Web;

use App\Enums\BusinessModule;
use Illuminate\Support\Facades\Route;

class CustomerProfileMenuBuilder
{
    /** @return array<int, array<string, mixed>>  */
    public function build(?string $moduleKey = null): array
    {
        $request = request();
        $items = [];

        foreach ((array) config('business_modules.customer_profile_menu', []) as $key => $definition) {
            $definition = (array) $definition;
            $routeName = $this->routeName(definition: $definition, moduleKey: $moduleKey);

            if ($routeName === null || ! Route::has($routeName)) {
                continue;
            }

            if (! $this->settingEnabled(setting: $definition['requires_setting'] ?? null)) {
                continue;
            }

            if (! $this->themeViewExists(viewKey: $definition['requires_theme_view'] ?? null)) {
                continue;
            }

            $patterns = array_values((array) ($definition['patterns'] ?? []));

            $items[] = [
                'key' => (string) $key,
                'label' => translate($definition['label'] ?? ucfirst((string) $key)),
                'drawer_label' => translate($definition['drawer_label'] ?? $definition['label'] ?? ucfirst((string) $key)),
                'icon_class' => (string) ($definition['icon']['class'] ?? ''),
                'icon_image' => (string) ($definition['icon']['image'] ?? ''),
                'url' => route($routeName),
                'is_active' => $patterns !== [] && $request !== null && $request->is(...$patterns),
            ];
        }

        return $items;
    }

    /** @param  array<string, mixed>  $definition  */
    private function routeName(array $definition, ?string $moduleKey): ?string
    {
        if (isset($definition['route'])) {
            return (string) $definition['route'];
        }

        $routes = (array) ($definition['routes'] ?? []);

        foreach ([$moduleKey, BusinessModule::RETAIL] as $candidate) {
            if ($candidate === null || ! isset($routes[$candidate])) {
                continue;
            }

            if (Route::has($routes[$candidate])) {
                return (string) $routes[$candidate];
            }
        }

        return null;
    }

    private function settingEnabled(?string $setting): bool
    {
        return $setting === null || (bool) getWebConfig(name: $setting);
    }

    private function themeViewExists(?string $viewKey): bool
    {
        if ($viewKey === null) {
            return true;
        }

        return defined('VIEW_FILE_NAMES') && (string) (VIEW_FILE_NAMES[$viewKey] ?? '') !== '';
    }
}
