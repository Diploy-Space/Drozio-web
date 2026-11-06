<?php

namespace App\Http\Controllers\Admin\Settings;

use App\Enums\GlobalConstant;
use App\Http\Controllers\BaseController;
use App\Http\Requests\Admin\Setting\System\CurrencyAddRequest;
use App\Http\Requests\Admin\Setting\System\CurrencyUpdateRequest;
use App\Services\New\BusinessSettingService;
use App\Services\New\CurrencyService;
use App\Services\New\SettingService;
use App\Traits\CalculatorTrait;
use App\Traits\PaymentGatewayTrait;
use Devrabiul\ToastMagic\Facades\ToastMagic;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class CurrencyController extends BaseController
{
    use CalculatorTrait;
    use PaymentGatewayTrait;

    private const SYSTEM_DEFAULT_CURRENCY_KEY = 'system_default_currency';

    private const CURRENCY_MODEL_KEY = 'currency_model';

    private const DIGITAL_PAYMENT_KEY = 'digital_payment';

    private const PAGINATION_LIMIT_KEY = 'pagination_limit';

    private const MULTI_CURRENCY_MODEL = 'multi_currency';

    private const SINGLE_CURRENCY_MODEL = 'single_currency';

    private const LIST_DATA_LIMIT = 10;

    /** @var array<int, int> */
    private const UNDELETABLE_CURRENCY_IDS = [1, 2, 3, 4, 5, 6, 7];

    /** @var array<int, string> */
    private const CURRENCY_SESSION_KEYS = ['usd', 'default', 'system_default_currency_info', 'currency_code', 'currency_symbol', 'currency_exchange_rate'];

    /** @var array<string, array{key: string, label: string}> currency code => the gateway that cannot lose it */
    private const GATEWAY_BOUND_CURRENCIES = [
        'BDT' => ['key' => 'ssl_commerz', 'label' => 'SSLCOMMERZ'],
        'INR' => ['key' => 'razor_pay', 'label' => 'RAZOR PAY'],
        'MYR' => ['key' => 'senang_pay', 'label' => 'SENANG PAY'],
        'ZAR' => ['key' => 'paystack', 'label' => 'PAYSTACK'],
    ];

    public function __construct(
        private readonly CurrencyService $currencyService,
        private readonly BusinessSettingService $businessSettingService,
        private readonly SettingService $settingService,
    ) {}

    public function index(?Request $request, ?string $type = null): View
    {
        $currencies = $this->currencyService->getList(
            searchValue: $request['searchValue'],
            dataLimit: self::LIST_DATA_LIMIT,
        );

        $this->appendGatewaySupport(currencies: $currencies);

        return view('admin-views.system-setup.currency.view', [
            'currencies' => $currencies,
            'activeCurrencies' => $this->currencyService->getList(dataLimit: 'all', filters: ['status' => 1]),
            'currencyModel' => $this->businessSettingService->findBy(params: ['type' => self::CURRENCY_MODEL_KEY]),
            'default' => $this->businessSettingService->findBy(params: ['type' => self::SYSTEM_DEFAULT_CURRENCY_KEY]),
            'digitalPaymentStatus' => $this->digitalPaymentStatus(),
        ]);
    }

    public function add(CurrencyAddRequest $request): RedirectResponse
    {
        if ($this->currencyService->findBy(params: ['code' => $request['code']]) !== null) {
            ToastMagic::warning(translate('Currency already exists'));

            return redirect()->back();
        }

        $this->currencyService->create(data: $this->currencyPayload(data: $request->all()));

        updateSetupGuideCacheKey(key: 'currency_setup', panel: 'admin');
        ToastMagic::success(translate('New Currency inserted successfully'));

        return redirect()->back();
    }

    public function update(CurrencyUpdateRequest $request): RedirectResponse
    {
        if ($request['exchange_rate'] <= 0) {
            ToastMagic::error(translate('Exchange rate cannot be zero'));

            return back();
        }

        $currency = $this->currencyService->find(id: $request['id']);

        if ($currency === null) {
            ToastMagic::warning(translate('Currency not found'));

            return back();
        }

        $boundGateway = $this->gatewayBlockingCodeChange(currency: $currency, code: $request['code']);

        if ($boundGateway !== null) {
            ToastMagic::warning(translate('Before update :currency, disable the :gateway payment gateway', [
                'currency' => $currency->code,
                'gateway' => $boundGateway,
            ]));

            return back();
        }

        $this->currencyService->update(id: $currency->id, data: $this->currencyPayload(data: $request->all()));

        updateSetupGuideCacheKey(key: 'currency_setup', panel: 'admin');
        ToastMagic::success(translate('Currency updated successfully'));

        return redirect()->back();
    }

    public function delete(Request $request): RedirectResponse
    {
        if (in_array($request['id'], self::UNDELETABLE_CURRENCY_IDS)) {
            ToastMagic::warning(translate('Default currency cannot be deleted'));

            return redirect()->back();
        }

        $this->currencyService->delete(id: $request['id']);
        ToastMagic::success(translate('Currency deleted successfully'));

        return redirect()->back();
    }

    public function status(Request $request): JsonResponse
    {
        if ($request['status'] != 1) {
            if ($this->currencyService->getList(dataLimit: 'all', filters: ['status' => 1])->count() == 1) {
                return response()->json([
                    'status' => 0,
                    'message' => translate('You cannot disable all currencies.'),
                ]);
            }

            if ($this->isRequiredForActiveGateway(id: $request['id'], searchValue: $request['searchValue'])) {
                return response()->json([
                    'status' => 0,
                    'message' => translate('Check payment gateway settings before disabling a currency they depend on.'),
                ]);
            }
        }

        $this->currencyService->updateStatus(id: $request['id'], status: (bool) $request->get('status', 0));
        updateSetupGuideCacheKey(key: 'currency_setup', panel: 'admin');

        return response()->json([
            'status' => 1,
            'message' => translate('Currency status changed successfully'),
        ]);
    }

    public function updateSystemCurrency(Request $request): RedirectResponse
    {
        if (in_array(0, $request['exchange_rate'])) {
            ToastMagic::error(translate('Exchange rate cannot be zero'));

            return back();
        }

        $this->businessSettingService->updateWhere(
            params: ['type' => self::SYSTEM_DEFAULT_CURRENCY_KEY],
            data: ['value' => $request['default_currency_id']],
        );

        if ($this->currencyModel() === self::MULTI_CURRENCY_MODEL) {
            foreach ($request['exchange_rate'] as $exchangeRateCode => $exchangeRateValue) {
                $this->currencyService->updateWhere(params: ['code' => $exchangeRateCode], data: ['exchange_rate' => $exchangeRateValue]);
            }
        }

        $this->forgetCurrencySessions();
        ToastMagic::success(translate(':item updated successfully', ['item' => translate('System Default currency')]));

        return redirect()->route('admin.settings.system-setup.currency.view');
    }

    public function checkSystemCurrency(Request $request): JsonResponse
    {
        $defaultCurrency = $this->currencyService->findBy(params: ['code' => $request['code']]);

        if ($this->currencyModel() !== self::MULTI_CURRENCY_MODEL) {
            $this->currencyService->updateWhere(params: ['code' => $defaultCurrency?->code], data: ['exchange_rate' => 1]);
            $this->businessSettingService->updateWhere(
                params: ['type' => self::SYSTEM_DEFAULT_CURRENCY_KEY],
                data: ['value' => $defaultCurrency?->id],
            );

            return response()->json([
                'mode' => self::SINGLE_CURRENCY_MODEL,
                'message' => translate('System default currency changed successfully'),
            ]);
        }

        return response()->json([
            'mode' => self::MULTI_CURRENCY_MODEL,
            'view' => view('admin-views.system-setup.currency._default-currency', [
                'currencyExchangeRate' => $this->exchangeRatesAgainstDefault(defaultCurrency: $defaultCurrency),
                'defaultCurrency' => $defaultCurrency,
            ])->render(),
        ]);
    }

    /** @param  array<string, mixed>  $data */
    private function currencyPayload(array $data): array
    {
        return [
            'name' => $data['name'],
            'symbol' => $data['symbol'],
            'code' => $data['code'],
            'exchange_rate' => $data['exchange_rate'] ?? 1,
        ];
    }

    private function digitalPaymentStatus(): int
    {
        $digitalPayment = $this->businessSettingService->getValueByType(type: self::DIGITAL_PAYMENT_KEY);

        return is_array($digitalPayment) ? (int) ($digitalPayment['status'] ?? 0) : 0;
    }

    private function currencyModel(): ?string
    {
        return $this->businessSettingService->findBy(params: ['type' => self::CURRENCY_MODEL_KEY])?->value;
    }

    private function forgetCurrencySessions(): void
    {
        cacheRemoveByType(type: 'business_settings');

        foreach (self::CURRENCY_SESSION_KEYS as $sessionKey) {
            session()->forget($sessionKey);
        }
    }

    private function appendGatewaySupport(Collection|LengthAwarePaginator $currencies): void
    {
        $activePaymentGateways = $this->getActivePaymentGateways();

        foreach ($currencies as $currency) {
            $support = $this->resolveGatewaySupport(
                currencyCode: $currency->code,
                currencies: $currencies,
                paymentGateways: $activePaymentGateways,
            );

            $currency->is_enabled_to_use = $support['is_enabled_to_use'];
            $currency->total_supported_gateway = $support['total_supported_gateway'];
            $currency->must_required_for_gateway = $support['must_required_for_gateway'];
            $currency->supported_gateway = $support['supported_gateway'];
        }
    }

    private function isRequiredForActiveGateway(mixed $id, ?string $searchValue): bool
    {
        $currency = $this->currencyService->find(id: $id);

        if ($currency === null) {
            return false;
        }

        $currencies = $this->currencyService->getList(
            searchValue: $searchValue,
            dataLimit: $this->businessSettingService->getValueByType(type: self::PAGINATION_LIMIT_KEY) ?? DEFAULT_DATA_LIMIT,
        );

        return (bool) $this->resolveGatewaySupport(
            currencyCode: $currency->code,
            currencies: $currencies,
            paymentGateways: $this->getActivePaymentGateways(),
        )['must_required_for_gateway'];
    }

    private function getActivePaymentGateways(): Collection
    {
        $filters = [
            'settings_type' => SettingService::PAYMENT_CONFIG_TYPE,
            'is_active' => 1,
        ];

        if (! config('get_payment_publish_status')) {
            $filters['key_name'] = GlobalConstant::DEFAULT_PAYMENT_GATEWAYS;
        }

        return $this->settingService->getList(dataLimit: 'all', filters: $filters);
    }

    /**
     * @return array{is_enabled_to_use: int, total_supported_gateway: int, must_required_for_gateway: int, supported_gateway: array<int, string>}
     */
    private function resolveGatewaySupport(
        ?string $currencyCode,
        Collection|LengthAwarePaginator $currencies,
        Collection $paymentGateways,
    ): array {
        $isEnabledToUse = 0;
        $totalSupportedGateway = 0;
        $supportedGateway = [];
        $mustRequiredForGateway = 1;

        foreach ($paymentGateways as $paymentGateway) {
            $supportedCurrencies = $this->getPaymentGatewaySupportedCurrencies(key: $paymentGateway->key_name);

            if (! $supportedCurrencies || ! array_key_exists($currencyCode, $supportedCurrencies)) {
                continue;
            }

            $isEnabledToUse = 1;
            $totalSupportedGateway += 1;
            $supportedGateway[] = $paymentGateway->key_name;

            foreach ($currencies as $currency) {
                if ($currency->status == 1 && $currency->code != $currencyCode && array_key_exists($currency->code, $supportedCurrencies)) {
                    $mustRequiredForGateway = 0;
                }
            }
        }

        if (count($supportedGateway) == 0) {
            $mustRequiredForGateway = 0;
        }

        return [
            'is_enabled_to_use' => $isEnabledToUse,
            'total_supported_gateway' => $totalSupportedGateway,
            'must_required_for_gateway' => $mustRequiredForGateway,
            'supported_gateway' => $supportedGateway,
        ];
    }

    private function gatewayBlockingCodeChange(Model $currency, ?string $code): ?string
    {
        $boundGateway = self::GATEWAY_BOUND_CURRENCIES[$currency->code] ?? null;

        if ($boundGateway === null || $currency->code === $code) {
            return null;
        }

        $config = $this->settingService->findBy(params: ['key_name' => $boundGateway['key']]);

        return $config?->is_active ? $boundGateway['label'] : null;
    }

    /** @return array<int, Model> */
    private function exchangeRatesAgainstDefault(?Model $defaultCurrency): array
    {
        $currencyExchangeRate = [];

        foreach ($this->currencyService->getList(dataLimit: 'all') as $currency) {
            $currency->exchange_rate = $this->getDivideWithDynamicPrecision(
                numerator: $currency->exchange_rate,
                denominator: $defaultCurrency?->exchange_rate,
                maxPrecision: 20,
            );

            $currencyExchangeRate[] = $currency;
        }

        return $currencyExchangeRate;
    }
}
