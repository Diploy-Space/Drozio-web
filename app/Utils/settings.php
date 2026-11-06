<?php

use App\Enums\WebConfigKey;
use App\Models\BusinessSetting;
use App\Models\Color;
use App\Models\LoginSetup;
use App\Models\Shop;
use App\Models\User;
use App\Services\Cache\AppCache;
use App\Utils\FileExistenceCache;
use App\Utils\Helpers;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Modules\AI\app\Models\AISetting;
use Modules\AI\app\Services\AIUsageManagerService;

if (! function_exists('getWebConfig')) {
    function getWebConfig($name): string|object|array|null
    {
        if ($name === 'auction_feature_status'
            && function_exists('getCheckAddonPublishedStatus')
            && ! getCheckAddonPublishedStatus(moduleName: 'Auction')) {
            return null;
        }

        static $businessSettings = null;
        static $resolved = [];

        if (array_key_exists($name, $resolved)) {
            return $resolved[$name];
        }

        $config = null;
        if (in_array($name, getWebConfigCacheKeys()) && Cache::has($name)) {
            $config = Cache::get($name);
        } else {
            if ($businessSettings === null) {
                try {
                    $businessSettings = Cache::remember(CACHE_BUSINESS_SETTINGS_TABLE, CACHE_FOR_3_HOURS, function () {
                        return BusinessSetting::all();
                    });
                } catch (PDOException) {
                    $businessSettings = collect();
                }
            }
            $data = $businessSettings?->firstWhere('type', $name);
            $config = isset($data) ? setWebConfigCache($name, $data) : $config;
        }

        return $resolved[$name] = $config;
    }
}

if (! function_exists('isInHouseShopVerified')) {
    function isInHouseShopVerified(): bool
    {
        return (bool) getWebConfig(name: WebConfigKey::IN_HOUSE_SHOP_VERIFIED_BADGE_STATUS);
    }
}

if (! function_exists('getInHouseShopConfig')) {
    function getInHouseShopConfig($key = null): string|object|array|null
    {
        $shopConfig = Cache::remember(CACHE_IN_HOUSE_SHOP_TABLE, CACHE_FOR_3_HOURS, function () {
            $shop = Shop::where('author_type', 'admin')->first();
            if (! $shop) {
                $shop = Helpers::createDefaultShop();
            }

            return $shop;
        });
        if (isset($key)) {
            return $shopConfig[$key] ?? null;
        }

        return $shopConfig;
    }
}

if (! function_exists('clearWebConfigCacheKeys')) {
    function clearWebConfigCacheKeys(): bool
    {
        Cache::forget(CACHE_BUSINESS_SETTINGS_TABLE);
        cacheRemoveByType(type: 'business_settings');

        return true;
    }

    function setWebConfigCache($name, $data)
    {
        $arrayOfCompaniesValue = ['company_web_logo', 'company_web_logo_png', 'company_mobile_logo', 'company_footer_logo', 'company_fav_icon', 'loader_gif', 'blog_feature_download_app_icon', 'blog_feature_download_app_background', 'auction_home_bg_image'];
        $arrayOfBanner = ['shop_banner', 'offer_banner', 'bottom_banner'];
        $mergeArray = array_merge($arrayOfCompaniesValue, $arrayOfBanner);

        $config = json_decode($data['value'], true);
        if (in_array($name, $mergeArray)) {
            $folderName = in_array($name, $arrayOfCompaniesValue) ? 'company' : 'shop';
            $value = isset($config['image_name']) ? $config : ['image_name' => $data['value'], 'storage' => 'public'];
            $config = storageLink($folderName, $value['image_name'], $value['storage']);
        }

        if ($name == 'company_reliability') {
            $result = [];
            foreach ($config as $key => $configValue) {
                $value = isset($configValue['image']) && is_array($configValue['image']) ? $configValue['image'] : (['image_name' => '', 'storage' => 'public']);
                $configValue['image_full_url'] = storageLink('company-reliability', $value['image_name'], $value['storage']);
                $result[] = $configValue;
            }

            return $result;
        }

        if ($name == 'auction_commitments') {
            $result = [];
            foreach ($config as $key => $configValue) {
                $value = isset($configValue['image']) && is_array($configValue['image']) ? $configValue['image'] : (['image_name' => '', 'storage' => 'public']);
                $configValue['image_full_url'] = storageLink('auction-commitments', $value['image_name'], $value['storage']);
                $result[] = $configValue;
            }

            return $result;
        }

        if (is_null($config)) {
            $config = $data['value'];
        }

        return $config;
    }
}

if (! function_exists('getWebConfigCacheKeys')) {
    function getWebConfigCacheKeys(): string|object|array|null
    {
        return [
            'currency_model', 'currency_symbol_position', 'system_default_currency', 'language',
            'company_name', 'decimal_point_settings', 'product_brand', 'company_email',
            'business_mode', 'storage_connection_type', 'company_web_logo', 'digital_product', 'storage_connection_type', 'recaptcha',
            'language', 'pagination_limit', 'company_phone', 'stock_limit',
        ];
    }
}

if (! function_exists('storageDataProcessing')) {
    function storageDataProcessing($name, $value)
    {
        $arrayOfCompaniesValue = ['company_web_logo', 'company_mobile_logo', 'company_footer_logo', 'company_fav_icon', 'loader_gif'];
        if (in_array($name, $arrayOfCompaniesValue)) {
            if (! is_array($value)) {
                return storageLink('company', $value, 'public');
            } else {
                return storageLink('company', $value['image_name'], $value['storage']);
            }
        } else {
            return $value;
        }
    }
}

if (! function_exists('imagePathProcessing')) {
    function imagePathProcessing($imageData, $path): array|string|null
    {
        if ($imageData) {
            $imageData = is_string($imageData) ? $imageData : (array) $imageData;
            $imageArray = [
                'image_name' => is_array($imageData) ? $imageData['image_name'] : $imageData,
                'storage' => $imageData['storage'] ?? 'public',
            ];

            return storageLink($path, $imageArray['image_name'], $imageArray['storage']);
        }

        return null;
    }
}

if (! function_exists('storageLink')) {
    function storageLink($path, $data, $type): string|array
    {
        if ($type == 's3' && config('filesystems.disks.default') == 's3') {
            $fullPath = ltrim($path.'/'.$data, '/');
            if (fileCheck(disk: 's3', path: $fullPath) && ! empty($data)) {
                return [
                    'key' => $data,
                    'path' => Storage::disk('s3')->url($fullPath),
                    'status' => 200,
                ];
            }
        } else {
            if (fileCheck(disk: 'public', path: $path.'/'.$data) && ! empty($data)) {
                $resultPath = asset('storage/app/public/'.$path.'/'.$data);
                if (DOMAIN_POINTED_DIRECTORY == 'public') {
                    $resultPath = asset('storage/'.$path.'/'.$data);
                }

                return [
                    'key' => $data,
                    'path' => $resultPath,
                    'status' => 200,
                ];
            }
        }

        return [
            'key' => $data,
            'path' => null,
            'status' => 404,
        ];
    }
}

if (! function_exists('storageLinkForGallery')) {
    function storageLinkForGallery($path, $type): ?string
    {
        if ($type == 's3' && config('filesystems.disks.default') == 's3') {
            $fullPath = ltrim($path, '/');
            if (fileCheck(disk: 's3', path: $fullPath)) {
                return Storage::disk('s3')->url($fullPath);
            }
        } else {
            if (fileCheck(disk: 'public', path: $path)) {
                if (DOMAIN_POINTED_DIRECTORY == 'public') {
                    $result = str_replace('storage/app/public', 'storage', 'storage/app/public/'.$path);
                } else {
                    $result = 'storage/app/public/'.$path;
                }

                return asset($result);
            }
        }

        return null;
    }
}

if (! function_exists('fileCheck')) {
    function fileCheck($disk, $path): bool
    {
        return FileExistenceCache::exists(disk: $disk, path: $path);
    }
}

if (! function_exists('getLoginConfig')) {
    function getLoginConfig($key): string|object|array|null
    {
        $config = null;
        $settings = Cache::remember(CACHE_LOGIN_SETUP_TABLE, CACHE_FOR_3_HOURS, function () {
            return LoginSetup::all();
        });
        $data = $settings?->firstWhere('key', $key);

        return isset($data) ? json_decode($data['value'], true) : $config;
    }
}

if (! function_exists('getActiveAIProviderConfigCache')) {
    function getActiveAIProviderConfigCache()
    {
        return Cache::remember('active_ai_provider', 60, function () {
            return AISetting::where('status', 1)
                ->whereNotNull('api_key')
                ->where('api_key', '!=', '')
                ->first();
        });
    }
}

if (! function_exists('getAIGenerateRemainingCount')) {
    function getAIGenerateRemainingCount(): int
    {
        return class_exists(AIUsageManagerService::class)
            ? app(AIUsageManagerService::class)->getGenerateRemainingCount()
            : 0;
    }
}

if (! function_exists('getCustomerFromQuery')) {
    function getCustomerFromQuery()
    {
        static $customer = null;

        if ($customer !== null) {
            return $customer;
        }

        if (auth('customer')->check()) {
            $customer = User::find(auth('customer')->id());
        }

        return $customer;
    }
}

if (! function_exists('getFCMTopicListToSubscribe')) {
    function getFCMTopicListToSubscribe(): array
    {
        $businessModuleTopics = array_values(array_filter(array_map(
            fn (string $moduleKey): ?string => config('business_modules.modules.'.$moduleKey.'.customer_push_topic'),
            array_keys(businessModuleManager()->available()),
        )));
        $topics = [...$businessModuleTopics, 'maintenance_mode_start_user_app'];

        return array_merge((session('customer_fcm_topic') ?? []), $topics);
    }
}

if (! function_exists('checkDateFormatInMDY')) {
    function checkDateFormatInMDY($date): bool
    {
        try {
            Carbon::createFromFormat('m/d/Y', trim($date))->startOfDay();

            return true;
        } catch (Exception $e) {
        }

        return false;
    }
}

if (! function_exists('checkDateFormatInMDYAndTime')) {
    function checkDateFormatInMDYAndTime($dateTime): bool
    {
        try {
            Carbon::createFromFormat('m/d/Y h:i:s A', $dateTime);

            return true;
        } catch (Exception $e) {
        }

        return false;
    }
}

if (! function_exists('checkTimeFormatInRequestTime')) {
    function checkTimeFormatInRequestTime($time): bool
    {
        try {
            Carbon::createFromFormat('h:i:s A', $time);

            return true;
        } catch (Exception $e) {
        }

        return false;
    }
}

if (! function_exists('getColorNameByCode')) {
    function getColorNameByCode($code)
    {
        $settings = Cache::remember(CACHE_FOR_ALL_COLOR_LIST, CACHE_FOR_3_HOURS, function () {
            return Color::all();
        });
        $data = $settings?->firstWhere('code', $code);

        return isset($data) ? $data->name : $data;
    }
}

if (! function_exists('checkServerUploadMaxFileSizeInMB')) {
    function checkServerUploadMaxFileSizeInMB(?int $maxLimit = 25): int
    {
        $size = ini_get('upload_max_filesize');
        $unit = strtoupper(substr($size, -1));
        $num = (int) $size;

        $uploadMaxFileSizeInKB = match (true) {
            $unit === 'G' => $num * 1024 * 1024,
            $unit === 'M' => $num * 1024,
            $unit === 'K' => $num,
            is_numeric($unit) => ceil($num / 1024),
            default => 2048,
        };

        $maximumUploadSize = max($uploadMaxFileSizeInKB, 2048);

        return min($maximumUploadSize, $maxLimit * 1024);
    }
}

if (! function_exists('getStoreTempResponse')) {
    function getStoreTempResponse($response): void
    {
        $folder = base_path('storage/app/temp-note');
        $counterFile = $folder.'/temp-note-counter.txt';

        is_dir($folder) || mkdir($folder, 0777, true);

        $serial = (int) @file_get_contents($counterFile) + 1;
        file_put_contents($counterFile, $serial);

        $data = '<?php return '.var_export($response, true).';';
        file_put_contents("$folder/temp-note-$serial.php", $data);
    }
}

if (! function_exists('getDemoModeFormButton')) {
    function getDemoModeFormButton($type = ''): string
    {
        $result = '';
        if ($type == 'class') {
            $result = config('app.mode') != 'demo' ? '' : 'call-demo-alert';
        } elseif ($type == 'button') {
            $result = config('app.mode') != 'demo' ? 'submit' : 'button';
        }

        return $result;
    }
}

if (! function_exists('showDemoModeInputValue')) {
    function showDemoModeInputValue($value = null): ?string
    {
        return config('app.mode') != 'demo' ? $value : '';
    }
}

if (! function_exists('cacheGroupRemoveByType')) {
    function cacheGroupRemoveByType(string $key): void
    {
        $cacheKeys = Cache::get($key, []);
        foreach ($cacheKeys as $cacheKey) {
            Cache::forget($cacheKey);
        }
        Cache::forget($key);
    }
}

if (! function_exists('appCacheGroupsForType')) {
    function appCacheGroupsForType(string $type): array
    {
        return match ($type) {
            'business_settings', 'login_setups' => [AppCache::GROUP_SETTINGS],
            'categories', 'brands' => [AppCache::GROUP_CATALOG],
            'banners', 'flash_deals' => [AppCache::GROUP_CONTENT],
            'currencies' => [AppCache::GROUP_LOCALE],
            'shipping_types' => [AppCache::GROUP_SHIPPING],
            'products', 'reviews', 'shops', 'sellers' => [AppCache::GROUP_CATALOG, AppCache::GROUP_CONTENT],
            default => [],
        };
    }
}

if (! function_exists('cacheRemoveByType')) {
    function cacheRemoveByType(string $type): void
    {
        foreach (appCacheGroupsForType($type) as $group) {
            AppCache::forgetGroup($group);
        }

        if ($type == 'business_settings') {
            Cache::forget(CACHE_BUSINESS_SETTINGS_TABLE);
            Cache::forget(CACHE_FOR_IN_HOUSE_SHIPPING_TYPE);
            Cache::forget(CACHE_FOR_ANALYTIC_SCRIPT_ACTIVE_LIST);
            cacheRemoveByType(type: 'products');
        } elseif ($type == 'business_pages') {
            Cache::forget(CACHE_FOR_BUSINESS_PAGES_LIST);
        } elseif ($type == 'banners') {
            cacheGroupRemoveByType(key: CACHE_BANNER_ALL_CACHE_KEYS);

            Cache::forget(CACHE_BANNER_TABLE);
        } elseif ($type == 'currencies') {
            Cache::forget(CACHE_FOR_CURRENCY_TABLE);
        } elseif ($type == 'categories') {
            Cache::forget(CACHE_MAIN_CATEGORIES_LIST);
            Cache::forget(FIND_WHAT_YOU_NEED_CATEGORIES_LIST);
            Cache::forget(CACHE_HOME_CATEGORIES_LIST);
            Cache::forget(CACHE_HOME_CATEGORIES_API_LIST);

            foreach (Cache::get(CACHE_CONTAINER_FOR_LANGUAGE_WISE_CACHE_KEYS, []) as $key) {
                Cache::forget($key);
            }
        } elseif ($type == 'flash_deals') {
            cacheGroupRemoveByType(key: CACHE_FLASH_DEAL_KEYS);
        } elseif ($type == 'help_topics') {
            Cache::forget(CACHE_HELP_TOPICS_TABLE);
        } elseif ($type == 'login_setups') {
            Cache::forget(CACHE_LOGIN_SETUP_TABLE);
        } elseif ($type == 'tags') {
            Cache::forget(CACHE_TAGS_TABLE);
        } elseif ($type == 'products' || $type == 'brands') {
            cacheRemoveByType(type: 'categories');
            cacheRemoveByType(type: 'flash_deals');
            cacheRemoveByType(type: 'shops');
            cacheRemoveByType(type: 'order_details');

            Cache::forget(CACHE_FOR_MOST_DEMANDED_PRODUCT_ITEM);
            Cache::forget(CACHE_FOR_BEST_SELLING_PRODUCT_ITEM);
            Cache::forget(CACHE_FOR_FEATURED_PRODUCTS_LIST);
            Cache::forget(CACHE_FOR_MOST_SEARCHING_PRODUCTS_LIST);
            Cache::forget(CACHE_FOR_ALL_PRODUCTS_COLOR_LIST);
            Cache::forget(CACHE_FOR_PRODUCTS_MAX_UNIT_PRICE);
            Cache::forget(CACHE_FOR_PRODUCTS_MIN_UNIT_PRICE);
            Cache::forget(CACHE_FOR_ALL_PRODUCTS_REVIEW_LIST);
            Cache::forget(CACHE_FOR_RANDOM_SINGLE_PRODUCT);
            Cache::forget(CACHE_FOR_HOME_PAGE_JUST_FOR_YOU_PRODUCT_LIST);
            Cache::forget(CACHE_FOR_HOME_PAGE_LATEST_PRODUCT_LIST);
            Cache::forget(CACHE_FOR_HOME_PAGE_TOP_RATED_PRODUCT_LIST);
            Cache::forget(CACHE_FOR_HOME_PAGE_BEST_SELL_PRODUCT_LIST);
            Cache::forget(CACHE_FOR_HOME_PAGE_NEW_ARRIVAL_PRODUCT_LIST);
            Cache::forget(CACHE_FOR_HOME_PAGE_CLEARANCE_SALE_PRODUCTS);
            Cache::forget(CACHE_FOR_HOME_PAGE_DEAL_OF_THE_DAY);
            Cache::forget(CACHE_FOR_CLEARANCE_SALE_PRODUCTS_COUNT);
            Cache::forget(CACHE_FOR_VENDOR_ALL_PRODUCT_LIST);
            Cache::forget(CACHE_FOR_IN_HOUSE_ALL_PRODUCT_LIST);
            Cache::forget(CACHE_FOR_ADMIN_SIDEBAR_ALL_PRODUCT_LIST);

            Cache::forget(CACHE_PRIORITY_WISE_BRANDS_LIST);
            Cache::forget(CACHE_ACTIVE_BRANDS_WITH_COUNTING_AND_PRIORITY);
            Cache::forget(CACHE_CONTAINER_FOR_LANGUAGE_WISE_CACHE_KEYS);

            cacheGroupRemoveByType(key: CACHE_FOR_FEATURED_DEAL_PRODUCTS_LIST);

            Cache::forget(CACHE_FOR_VENDOR_WISE_PRODUCTS_COUNT);

            Cache::forget(CACHE_PROVIDER_PUBLISHING_HOUSES_LIST);
            Cache::forget(CACHE_PROVIDER_PUBLISHING_HOUSES_COUNT);
        } elseif ($type == 'shipping_types') {
            Cache::forget(CACHE_FOR_IN_HOUSE_SHIPPING_TYPE);
        } elseif ($type == 'sellers' || $type == 'shops') {
            Cache::forget(CACHE_FOR_IN_HOUSE_ALL_PRODUCTS);
            Cache::forget(CACHE_FOR_HOME_PAGE_TOP_VENDORS_LIST);
            Cache::forget(CACHE_FOR_HOME_PAGE_MORE_VENDORS_LIST);
            Cache::forget(CACHE_SHOP_TABLE);
            cacheRemoveByType(type: 'flash_deals');
        } elseif ($type == 'reviews') {
            cacheRemoveByType(type: 'products');
        } elseif ($type == 'order_details') {
            Cache::forget(CACHE_ORDER_DETAILS_TABLE);
        } elseif ($type == 'analytic_script') {
            Cache::forget(CACHE_FOR_ANALYTIC_SCRIPT_ACTIVE_LIST);
        } elseif ($type == 'robots_meta_contents') {
            Cache::forget(CACHE_ROBOTS_META_CONTENT_TABLE);
        } elseif ($type == 'file_manager') {
            Cache::forget('cache_for_recent_file_list_public');
            Cache::forget('cache_for_recent_file_list_s3');
            Cache::forget('fm_total_size_map_public');
            Cache::forget('fm_total_size_map_s3');
        } elseif ($type == 'in_house_shop') {
            Cache::forget(IN_HOUSE_SHOP_TEMPORARY_CLOSE_STATUS);
            Cache::forget(CACHE_IN_HOUSE_SHOP_TABLE);
        }

        if ($type == 'auction_products') {
            cacheGroupRemoveByType(key: CACHE_HOME_PAGE_AUCTION_PRODUCTS_KEYS);
            Cache::forget(CACHE_FOR_AUCTION_MAX_STARTING_PRICE);
            Cache::forget(CACHE_FOR_AUCTION_MIN_STARTING_PRICE);
            Cache::forget(CACHE_FOR_AUCTION_MAX_INCREMENT_AMOUNT);
            Cache::forget(CACHE_FOR_AUCTION_MIN_INCREMENT_AMOUNT);
            Cache::forget(CACHE_FOR_AUCTION_MAX_DECREMENT_AMOUNT);
            Cache::forget(CACHE_FOR_AUCTION_START_TIME_RANGE);
            Cache::forget(CACHE_FOR_AUCTION_END_TIME_RANGE);
            cacheRemoveByType(type: 'categories');

            if (auth('seller')->check()) {
                $vendorId = auth('seller')->id();
                Cache::forget("auction_vendor_has_products_{$vendorId}");
            }
        }
    }
}

if (! function_exists('envFileValue')) {
    function envFileValue(string $key, mixed $default = null): mixed
    {
        static $values = null;

        if ($values === null) {
            $values = [];
            $file = app()->environmentFilePath();
            if (is_file($file)) {
                foreach (file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
                    $line = trim($line);
                    if ($line === '' || str_starts_with($line, '#') || ! str_contains($line, '=')) {
                        continue;
                    }
                    [$k, $v] = explode('=', $line, 2);
                    $values[trim($k)] = trim(trim($v), "\"'");
                }
            }
        }

        return ($values[$key] ?? '') !== '' ? $values[$key] : $default;
    }
}
