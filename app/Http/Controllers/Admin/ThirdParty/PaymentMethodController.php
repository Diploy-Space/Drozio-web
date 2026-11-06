<?php

namespace App\Http\Controllers\Admin\ThirdParty;

use App\Enums\GlobalConstant;
use App\Http\Controllers\BaseController;
use App\Http\Requests\Admin\Setting\ThirdParty\PaymentConfigUpdateRequest;
use App\Http\Requests\Admin\Setting\ThirdParty\PaymentStatusUpdateRequest;
use App\Services\New\BusinessSettingService;
use App\Services\New\CurrencyService;
use App\Services\New\SettingService;
use App\Traits\PaymentGatewayTrait;
use App\Traits\Processor;
use Devrabiul\ToastMagic\Facades\ToastMagic;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class PaymentMethodController extends BaseController
{
    use PaymentGatewayTrait;
    use Processor;

    private const CASH_ON_DELIVERY_KEY = 'cash_on_delivery';

    private const DIGITAL_PAYMENT_KEY = 'digital_payment';

    private const OFFLINE_PAYMENT_KEY = 'offline_payment';

    private const GATEWAY_IMAGE_DIRECTORY = 'payment_modules/gateway_image/';

    private const GATEWAY_IMAGE_FORMAT = 'png';

    private const SETUP_GUIDE_KEY = 'digital_payment_setup';

    private const PAYMENT_SETUP_ROUTE_NAME = 'payment_setup';

    public function __construct(
        private readonly SettingService $settingService,
        private readonly CurrencyService $currencyService,
        private readonly BusinessSettingService $businessSettingService,
    ) {}

    public function index(?Request $request, ?string $type = null): View
    {
        return view('admin-views.third-party.payment-method.index', [
            'paymentGatewaysList' => $this->gatewaysWithCurrencySupport(),
            'paymentGatewayPublishedStatus' => config('get_payment_publish_status') ?? 0,
            'paymentUrl' => $this->addonRouteUrl(routeName: self::PAYMENT_SETUP_ROUTE_NAME),
        ] + $this->paymentOptions());
    }

    public function UpdatePaymentConfig(PaymentConfigUpdateRequest $request): RedirectResponse
    {
        if (config('app.mode') == 'demo') {
            ToastMagic::info(translate('This option is disabled for demo'));

            return back();
        }

        $gateway = $this->findGateway(keyName: $request['gateway']);
        $gatewayImage = $this->resolveGatewayImage(request: $request, gateway: $gateway);
        $status = $request->has('status') ? (int) $request['status'] : 0;

        if ($status == 1 && $gateway && ! $this->isUsableWithActiveCurrencies(gateway: $gateway)) {
            $status = 0;
            ToastMagic::error(translate(GATEWAYS_STATUS_UPDATE_FAIL['message']));
        }

        $this->settingService->updateOrInsert(
            params: ['key_name' => $request['gateway'], 'settings_type' => SettingService::PAYMENT_CONFIG_TYPE],
            data: [
                'key_name' => $request['gateway'],
                'live_values' => $request->validated(),
                'test_values' => $request->validated(),
                'settings_type' => SettingService::PAYMENT_CONFIG_TYPE,
                'mode' => $request['mode'],
                'is_active' => $status,
                'additional_data' => json_encode([
                    'gateway_title' => $request['gateway_title'],
                    'gateway_image' => $gatewayImage,
                ]),
            ],
        );

        if ($status != 0) {
            ToastMagic::success(translate('Updated successfully'));
        }
        updateSetupGuideCacheKey(key: self::SETUP_GUIDE_KEY, panel: 'admin');

        return redirect()->route('admin.settings.third-party.payment-method.index');
    }

    public function UpdateStatus(PaymentStatusUpdateRequest $request): RedirectResponse
    {
        if (config('app.mode') == 'demo') {
            ToastMagic::info(translate('This option is disabled for demo'));

            return back();
        }

        $gateway = $this->findGateway(keyName: $request['key_name']);

        if (! $gateway) {
            ToastMagic::error(translate('Not found'));

            return redirect()->route('admin.settings.third-party.payment-method.index');
        }

        if ($request['status'] == 1 && $this->hasBlankConfiguration(values: $gateway['live_values'] ?? [])) {
            ToastMagic::error(translate('Please update the configuration first'));

            return redirect()->route('admin.settings.third-party.payment-method.index');
        }

        $this->settingService->updateWhere(
            params: ['key_name' => $request['key_name'], 'settings_type' => SettingService::PAYMENT_CONFIG_TYPE],
            data: ['is_active' => $request['status'] ?? 0],
        );

        updateSetupGuideCacheKey(key: self::SETUP_GUIDE_KEY, panel: 'admin');
        ToastMagic::success(translate('Updated successfully'));

        return redirect()->route('admin.settings.third-party.payment-method.index');
    }

    /** @return array<int, Model> */
    private function gatewaysWithCurrencySupport(): array
    {
        $gateways = $this->defaultGateways();
        $currencies = $this->currencyService->getList(dataLimit: 'all');

        foreach ($gateways as $gateway) {
            foreach ($this->currencySupport(gateway: $gateway, currencies: $currencies) as $attribute => $value) {
                $gateway[$attribute] = $value;
            }
        }

        return $gateways->sortBy(fn (Model $gateway): int => count($gateway['live_values']))->values()->all();
    }

    private function defaultGateways(): Collection
    {
        return $this->settingService->getList(
            dataLimit: 'all',
            filters: [
                'settings_type' => [SettingService::PAYMENT_CONFIG_TYPE],
                'key_name' => GlobalConstant::DEFAULT_PAYMENT_GATEWAYS,
            ],
        );
    }

    private function findGateway(?string $keyName): ?Model
    {
        return $this->settingService->findBy(params: [
            'key_name' => $keyName,
            'settings_type' => SettingService::PAYMENT_CONFIG_TYPE,
        ]);
    }

    /**
     * @return array{is_enabled_to_use: int, total_supported_currencies: int, must_required_for_currency: int, supported_currency: array<int, string>}
     */
    private function currencySupport(Model $gateway, Collection $currencies): array
    {
        $supportedCurrencies = $this->getPaymentGatewaySupportedCurrencies(key: $gateway->key_name);

        $supportForCurrency = $currencies
            ->filter(fn (Model $currency): bool => $currency->status == 1 && array_key_exists($currency->code, $supportedCurrencies))
            ->pluck('code')
            ->values()
            ->all();

        return [
            'is_enabled_to_use' => $supportForCurrency === [] ? 0 : 1,
            'total_supported_currencies' => count($supportForCurrency),
            'must_required_for_currency' => count($supportForCurrency) === 1 ? 1 : 0,
            'supported_currency' => $supportForCurrency,
        ];
    }

    private function isUsableWithActiveCurrencies(Model $gateway): bool
    {
        return $this->currencySupport(
            gateway: $gateway,
            currencies: $this->currencyService->getList(dataLimit: 'all'),
        )['is_enabled_to_use'] == 1;
    }

    private function resolveGatewayImage(PaymentConfigUpdateRequest $request, ?Model $gateway): string
    {
        $storedImage = $this->storedGatewayImage(gateway: $gateway);

        if (! $request->has('gateway_image')) {
            return $storedImage;
        }

        return $this->file_uploader(
            self::GATEWAY_IMAGE_DIRECTORY,
            self::GATEWAY_IMAGE_FORMAT,
            $request['gateway_image'],
            $storedImage,
        );
    }

    private function storedGatewayImage(?Model $gateway): string
    {
        $additionalData = $gateway?->additional_data;

        return $additionalData === null ? '' : (json_decode($additionalData)?->gateway_image ?? '');
    }

    /** @param  array<string, mixed>  $values */
    private function hasBlankConfiguration(array $values): bool
    {
        foreach ($values as $value) {
            if (empty($value) && $value != 0) {
                return true;
            }
        }

        return false;
    }

    /** @return array<string, array<string, mixed>|null> */
    private function paymentOptions(): array
    {
        $settings = $this->businessSettingService
            ->getList(dataLimit: 'all', filters: [
                'type' => [self::CASH_ON_DELIVERY_KEY, self::DIGITAL_PAYMENT_KEY, self::OFFLINE_PAYMENT_KEY],
            ])
            ->pluck('value', 'type');

        return [
            'cashOnDelivery' => $this->decodedSetting(value: $settings[self::CASH_ON_DELIVERY_KEY] ?? null),
            'digitalPayment' => $this->decodedSetting(value: $settings[self::DIGITAL_PAYMENT_KEY] ?? null),
            'offlinePayment' => $this->decodedSetting(value: $settings[self::OFFLINE_PAYMENT_KEY] ?? null),
        ];
    }

    /** @return array<string, mixed>|null */
    private function decodedSetting(mixed $value): ?array
    {
        $decoded = $value === null ? null : json_decode((string) $value, true);

        return is_array($decoded) ? $decoded : null;
    }

    private function addonRouteUrl(string $routeName): string
    {
        foreach (config('addon_admin_routes') ?? [] as $routeGroup) {
            foreach ($routeGroup as $route) {
                if ($route['name'] === $routeName) {
                    return $route['url'];
                }
            }
        }

        return '';
    }
}
