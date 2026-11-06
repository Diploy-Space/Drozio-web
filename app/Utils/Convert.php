<?php

namespace App\Utils;

class Convert
{
    public static function usd($amount): float|int|null
    {
        $currency_model = getWebConfig(name: 'currency_model');
        if ($currency_model == 'multi_currency') {
            $default = Helpers::getCurrencyById(getWebConfig(name: 'system_default_currency'));
            $usd = Helpers::getCurrencyByCode('USD')->exchange_rate ?? 1;
            $rate = $default['exchange_rate'] / $usd;
            $value = floatval($amount) / floatval($rate);
        } else {
            $value = floatval($amount);
        }

        return $value;
    }

    public static function usdPaymentModule($amount, $currency): float|int|null
    {
        $currency = strtoupper(trim($currency));
        $currencyModel = getWebConfig(name: 'currency_model');
        if ($currencyModel == 'multi_currency') {
            if ($currency == 'USD') {
                return floatval($amount);
            }

            $default = Helpers::getCurrencyById(getWebConfig(name: 'system_default_currency'));
            $currentCurrency = Helpers::getCurrencyByCode($currency)->exchange_rate ?? 1;

            if ($default['code'] == 'USD') {
                return floatval($amount) / $currentCurrency;
            }
            $rate = $default['exchange_rate'] >= $currentCurrency ? ($default['exchange_rate'] / $currentCurrency) : ($currentCurrency / $default['exchange_rate']);
            $defaultAmount = $default['exchange_rate'] <= $currentCurrency ? floatval($amount) / floatval($rate) : floatval($amount) * floatval($rate);
            $usdRate = Helpers::getCurrencyByCode('USD')->exchange_rate;

            $value = floatval($default['exchange_rate']) >= floatval($usdRate) ? floatval($defaultAmount) * floatval($usdRate) : floatval($defaultAmount) / floatval($usdRate);
        } else {
            $value = floatval($amount);
        }

        return $value;
    }

    public static function default($amount): float|int
    {
        $currency_model = getWebConfig(name: 'currency_model');
        if ($currency_model == 'multi_currency') {
            $default = Helpers::getCurrencyById(getWebConfig(name: 'system_default_currency'));
            $usd = Helpers::getCurrencyByCode('USD')->exchange_rate ?? 1;
            $rate = $default['exchange_rate'] / $usd;
            $value = floatval($amount) * floatval($rate);
        } else {
            $value = floatval($amount);
        }
        return round($value, 2);
    }

    public static function bdtTousd($amount): float|int
    {
        $currency_model = getWebConfig(name: 'currency_model');
        if ($currency_model == 'multi_currency') {
            $bdt = Helpers::getCurrencyByCode('BDT')->exchange_rate ?? 1;
            $usd = Helpers::getCurrencyByCode('USD')->exchange_rate ?? 1;
            $rate = $bdt / $usd;
            $value = floatval($amount) / floatval($rate);
        } else {
            $value = floatval($amount);
        }

        return $value;
    }

    public static function usdTobdt($amount)
    {
        $currency_model = getWebConfig(name: 'currency_model');
        if ($currency_model == 'multi_currency') {
            $bdt = Helpers::getCurrencyByCode('BDT')->exchange_rate ?? 1;
            $usd = Helpers::getCurrencyByCode('USD')->exchange_rate ?? 1;
            $rate = $usd / $bdt;
            $value = floatval($amount) / floatval($rate);
        } else {
            $value = floatval($amount);
        }

        return $value;
    }

    public static function usdTomyr($amount)
    {
        $currency_model = getWebConfig(name: 'currency_model');
        if ($currency_model == 'multi_currency') {
            $myr = Helpers::getCurrencyByCode('MYR')->exchange_rate ?? 1;
            $usd = Helpers::getCurrencyByCode('USD')->exchange_rate ?? 1;
            $rate = $usd / $myr;
            $value = floatval($amount) / floatval($rate);
        } else {
            $value = floatval($amount);
        }

        return $value;
    }

    public static function usdTozar($amount): float|int
    {
        $currency_model = getWebConfig(name: 'currency_model');
        if ($currency_model == 'multi_currency') {
            $zar = Helpers::getCurrencyByCode('ZAR')->exchange_rate ?? 1;
            $usd = Helpers::getCurrencyByCode('USD')->exchange_rate ?? 1;
            $rate = $usd / $zar;
            $value = floatval($amount) / floatval($rate);
        } else {
            $value = floatval($amount);
        }

        return $value;
    }

    public static function usdToinr($amount): float|int
    {
        $currency_model = getWebConfig(name: 'currency_model');
        if ($currency_model == 'multi_currency') {
            $inr = Helpers::getCurrencyByCode('INR')->exchange_rate ?? 1;
            $usd = Helpers::getCurrencyByCode('USD')->exchange_rate ?? 1;
            $rate = $usd / $inr;
            $value = floatval($amount) / floatval($rate);
        } else {
            $value = floatval($amount);
        }

        return $value;
    }

    public static function usdToegp($amount): float|int
    {
        $currency_model = getWebConfig(name: 'currency_model');
        if ($currency_model == 'multi_currency') {
            $egp = Helpers::getCurrencyByCode('EGP')->exchange_rate ?? 1;
            $usd = Helpers::getCurrencyByCode('USD')->exchange_rate ?? 1;
            $rate = $usd / $egp;
            $value = floatval($amount) / floatval($rate);
        } else {
            $value = floatval($amount);
        }

        return $value;
    }
}
