<?php

if (! function_exists('theme_asset')) {
    function theme_asset($path = null): string
    {
        static $memo = [];
        $key = (string) $path;
        if (isset($memo[$key])) {
            return $memo[$key];
        }

        $themeName = theme_root_path();
        if ($themeName == 'default') {
            return $memo[$key] = dynamicAsset(path: $path);
        } else {
            if (DOMAIN_POINTED_DIRECTORY == 'public') {
                return $memo[$key] = dynamicAsset(path: 'public/themes/'.$themeName.'/public/'.$path);
            } else {
                return $memo[$key] = dynamicAsset(path: 'resources/themes/'.$themeName.'/public/'.$path);
            }
        }
    }
}

if (! function_exists('theme_root_path')) {
    function theme_root_path(): string
    {
        static $themeName;

        return $themeName ??= (config('app.theme'));
    }
}

if (! function_exists('themeViewName')) {
    function themeViewName(string $key): string
    {
        $viewName = defined('VIEW_FILE_NAMES') ? (VIEW_FILE_NAMES[$key] ?? '') : '';

        abort_if($viewName === '', 404);

        return $viewName;
    }
}

if (! function_exists('getHexToRGBColorCode')) {
    function getHexToRGBColorCode($hex): ?string
    {
        $result = preg_match('/^#?([a-f\d]{2})([a-f\d]{2})([a-f\d]{2})$/i', $hex, $matches);

        return $result ? hexdec($matches[1]).', '.hexdec($matches[2]).', '.hexdec($matches[3]) : null;
    }
}

if (! function_exists('getSystemDynamicPartials')) {
    function getSystemDynamicPartials($type = null): mixed
    {
        if ($type == 'analytics_script') {
            return view('system-partials._analytics_script');
        }

        return null;
    }
}

if (! function_exists('formatCompactNumber')) {
    function formatCompactNumber(int|float|null $value = 0): string
    {
        if (! is_numeric($value)) {
            return (string) $value;
        }

        if ($value >= 1000000000) {
            return round($value / 1000000000, 2).'B+';
        } elseif ($value >= 1000000) {
            return round($value / 1000000, 2).'M+';
        } elseif ($value >= 1000) {
            return round($value / 1000, 2).'K+';
        }

        return (string) $value;
    }
}
