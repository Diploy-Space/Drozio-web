<?php

use App\Enums\BusinessModule;
use App\Services\New\BusinessSettingService;
use App\Services\New\ShopService;
use Carbon\Carbon;

if (!function_exists('checkVendorAbility')) {
    function checkVendorAbility(string $type, string $status, object|array|null $vendor = null, ?string $businessModule = null)
    {
        if ($status == 'temporary_close') {
            $businessModule = $businessModule ?? BusinessModule::RETAIL;

            if ($type == 'inhouse') {
                if ($businessModule === BusinessModule::RETAIL) {
                    return getInHouseShopConfig(key: 'temporary_close') ?? 0;
                }

                $settingKey = BusinessSettingService::IN_HOUSE_AVAILABILITY_SETTINGS[$businessModule] ?? null;

                return $settingKey ? (getWebConfig(name: $settingKey)['status'] ?? 0) : 0;
            }

            $column = ShopService::TEMPORARY_CLOSE_COLUMNS[$businessModule] ?? null;

            return $column ? ($vendor[$column] ?? 0) : 0;
        }

        if ($status == 'vacation_status' && in_array($type, ['inhouse', 'vendor'])) {
            if ($type == 'inhouse') {
                $inHouseShop = getInHouseShopConfig();
                $vacationModules = getWebConfig(name: BusinessSettingService::IN_HOUSE_VACATION_MODULES);
                $vacationStatus = $inHouseShop['vacation_status'] ?? 0;
                $vacationDurationType = $inHouseShop['vacation_duration_type'] ?? 'until_change';
                $vacationStartDate = $inHouseShop['vacation_start_date'];
                $vacationEndDate = $inHouseShop['vacation_end_date'];
            } else {
                $vacationModules = $vendor['vacation_modules'] ?? null;
                $vacationStatus = $vendor['vacation_status'] ?? 0;
                $vacationDurationType = $vendor['vacation_duration_type'] ?? 'until_change';
                $vacationStartDate = $vendor['vacation_start_date'] ?? null;
                $vacationEndDate = $vendor['vacation_end_date'] ?? null;
            }

            if (is_string($vacationModules)) {
                $vacationModules = json_decode($vacationModules, true);
            }

            if (is_array($vacationModules) && !in_array($businessModule ?? BusinessModule::RETAIL, $vacationModules, true)) {
                return false;
            }

            if ($vacationStatus) {
                if ($vacationDurationType == 'until_change') {
                    return $vacationStatus;
                } else {
                    if (!is_null($vacationStartDate) && !is_null($vacationEndDate)) {
                        $start = Carbon::parse($vacationStartDate);
                        $end = Carbon::parse($vacationEndDate);
                        $today = Carbon::now();
                        return $today->between($start, $end);
                    }
                    return false;
                }
            }
        }

        return false;
    }
}

if (! function_exists('getVendorAvailabilityState')) {
    function getVendorAvailabilityState(string $type, object|array|null $vendor = null, ?string $businessModule = null): ?string
    {
        if (checkVendorAbility(type: $type, status: 'temporary_close', vendor: $vendor, businessModule: $businessModule)) {
            return 'temporary_close';
        }

        if (checkVendorAbility(type: $type, status: 'vacation_status', vendor: $vendor, businessModule: $businessModule)) {
            return 'vacation';
        }

        return null;
    }
}
