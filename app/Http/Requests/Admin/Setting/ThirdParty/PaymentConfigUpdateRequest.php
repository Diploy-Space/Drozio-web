<?php

namespace App\Http\Requests\Admin\Setting\ThirdParty;

use App\Http\Requests\BaseRequest;
use App\Services\New\SettingService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\Rule;

/**
 * @property string $gateway
 */
class PaymentConfigUpdateRequest extends BaseRequest
{
    protected const PAYMOB_ACCEPT_GATEWAY = 'paymob_accept';

    protected $stopOnFirstFailure = true;

    public function __construct(
        protected readonly SettingService $settingService,
    ) {}

    public function rules(): array
    {
        $validationRules = [
            'gateway' => 'required',
            Rule::in(['ssl_commerz', 'sixcash', 'worldpay', 'payfast', 'swish', 'esewa', 'maxicash', 'hubtel', 'viva_wallet', 'tap', 'thawani', 'moncash', 'pvit', 'ccavenue', 'foloosi', 'iyzi_pay', 'xendit', 'fatoorah', 'hyper_pay', 'amazon_pay', 'paypal', 'stripe', 'razor_pay', 'senang_pay', 'paytabs', 'paystack', 'paymob_accept', 'paytm', 'flutterwave', 'liqpay', 'bkash', 'mercadopago', 'cash_after_service', 'digital_payment', 'momo']),
            'mode' => 'required|in:live,test',
            'gateway_title' => 'required',
        ];
        $additionalDataRules = $this->getAdditionalDataRules();

        return array_merge($validationRules, $additionalDataRules);
    }

    public function messages(): array
    {
        return [
            'gateway.required' => translate('The gateway field is required'),
            'gateway_title.required' => translate('The gateway title field is required'),
            'gateway_image.required' => translate(':field is required', ['field' => translate('Gateway image')]),
        ];
    }

    protected function getAdditionalDataRules(): array
    {
        collect(['status'])->each(fn ($item, $key) => $this[$item] = $this->has($item) ? (int) $this[$item] : 0);

        if ($this['gateway'] == self::PAYMOB_ACCEPT_GATEWAY && ! $this['supported_country'] && ! $this['secret_key']) {
            $this->ensurePaymobAcceptDefaults();
        }

        $additionalDataRules = [];
        if ($this['gateway'] == 'ssl_commerz') {
            $additionalDataRules = [
                'status' => 'required|in:1,0',
                'store_id' => 'required',
                'store_password' => 'required',
            ];
        } elseif ($this['gateway'] == 'paypal') {
            $additionalDataRules = [
                'status' => 'required|in:1,0',
                'client_id' => 'required',
                'client_secret' => 'required',
            ];
        } elseif ($this['gateway'] == 'stripe') {
            $additionalDataRules = [
                'status' => 'required|in:1,0',
                'api_key' => 'required',
                'published_key' => 'required',
            ];
        } elseif ($this['gateway'] == 'razor_pay') {
            $additionalDataRules = [
                'status' => 'required|in:1,0',
                'api_key' => 'required',
                'api_secret' => 'required',
            ];
        } elseif ($this['gateway'] == 'senang_pay') {
            $additionalDataRules = [
                'status' => 'required|in:1,0',
                'callback_url' => 'required',
                'secret_key' => 'required',
                'merchant_id' => 'required',
            ];
        } elseif ($this['gateway'] == 'paytabs') {
            $additionalDataRules = [
                'status' => 'required|in:1,0',
                'profile_id' => 'required',
                'server_key' => 'required',
                'base_url' => 'required',
            ];
        } elseif ($this['gateway'] == 'paystack') {
            $additionalDataRules = [
                'status' => 'required|in:1,0',
                'public_key' => 'required',
                'secret_key' => 'required',
                'merchant_email' => 'required',
            ];
        } elseif ($this['gateway'] == 'paymob_accept') {
            $additionalDataRules = [
                'status' => 'required|in:1,0',
                'supported_country' => 'required',
                'public_key' => 'required',
                'secret_key' => 'required',
                'integration_id' => 'required',
                'hmac' => 'required',
            ];
        } elseif ($this['gateway'] == 'mercadopago') {
            if (! isset($this['supported_country']) || $this['supported_country'] == '') {
                $this->merge(['supported_country' => 'argentina']);
            }
            $additionalDataRules = [
                'status' => 'required|in:1,0',
                'supported_country' => 'required',
                'access_token' => 'required',
                'public_key' => 'required',
            ];
        } elseif ($this['gateway'] == 'liqpay') {
            $additionalDataRules = [
                'status' => 'required|in:1,0',
                'private_key' => 'required',
                'public_key' => 'required',
            ];
        } elseif ($this['gateway'] == 'flutterwave') {
            $additionalDataRules = [
                'status' => 'required|in:1,0',
                'secret_key' => 'required',
                'public_key' => 'required',
                'hash' => 'required',
            ];
        } elseif ($this['gateway'] == 'paytm') {
            $additionalDataRules = [
                'status' => 'required|in:1,0',
                'merchant_key' => 'required',
                'merchant_id' => 'required',
                'merchant_website_link' => 'required',
            ];
        } elseif ($this['gateway'] == 'bkash') {
            $additionalDataRules = [
                'status' => 'required|in:1,0',
                'app_key' => 'required',
                'app_secret' => 'required',
                'username' => 'required',
                'password' => 'required',
            ];
        } elseif ($this['gateway'] == 'cash_after_service') {
            $additionalDataRules = [
                'status' => 'required|in:1,0',
            ];
        } elseif ($this['gateway'] == 'digital_payment') {
            $additionalDataRules = [
                'status' => 'required|in:1,0',
            ];
        } elseif ($this['gateway'] == 'momo') {
            $additionalDataRules = [
                'status' => 'required|in:1,0',
                'api_key' => 'required',
                'api_user' => 'required',
                'subscription_key' => 'required',
            ];
        } elseif ($this['gateway'] == 'hyper_pay') {
            $additionalDataRules = [
                'status' => 'required|in:1,0',
                'entity_id' => 'required',
                'access_code' => 'required',
            ];
        } elseif ($this['gateway'] == 'amazon_pay') {
            $additionalDataRules = [
                'status' => 'required|in:1,0',
                'pass_phrase' => 'required',
                'access_code' => 'required',
                'merchant_identifier' => 'required',
            ];
        } elseif ($this['gateway'] == 'sixcash') {
            $additionalDataRules = [
                'status' => 'required|in:1,0',
                'public_key' => 'required',
                'secret_key' => 'required',
                'merchant_number' => 'required',
                'base_url' => 'required',
            ];
        } elseif ($this['gateway'] == 'worldpay') {
            $additionalDataRules = [
                'status' => 'required|in:1,0',
                'OrgUnitId' => 'required',
                'jwt_issuer' => 'required',
                'mac' => 'required',
                'merchantCode' => 'required',
                'xml_password' => 'required',
            ];
        } elseif ($this['gateway'] == 'payfast') {
            $additionalDataRules = [
                'status' => 'required|in:1,0',
                'merchant_id' => 'required',
                'secured_key' => 'required',
            ];
        } elseif ($this['gateway'] == 'swish') {
            $additionalDataRules = [
                'status' => 'required|in:1,0',
                'number' => 'required',
            ];
        } elseif ($this['gateway'] == 'esewa') {
            $additionalDataRules = [
                'status' => 'required|in:1,0',
                'merchantCode' => 'required',
            ];
        } elseif ($this['gateway'] == 'maxicash') {
            $additionalDataRules = [
                'status' => 'required|in:1,0',
                'merchantId' => 'required',
                'merchantPassword' => 'required',
            ];
        } elseif ($this['gateway'] == 'hubtel') {
            $additionalDataRules = [
                'status' => 'required|in:1,0',
                'account_number' => 'required',
                'api_id' => 'required',
                'api_key' => 'required',
            ];
        } elseif ($this['gateway'] == 'viva_wallet') {
            $additionalDataRules = [
                'status' => 'required|in:1,0',
                'client_id' => 'required',
                'client_secret' => 'required',
                'source_code' => 'required',
            ];
        } elseif ($this['gateway'] == 'tap') {
            $additionalDataRules = [
                'status' => 'required|in:1,0',
                'secret_key' => 'required',
            ];
        } elseif ($this['gateway'] == 'thawani') {
            $additionalDataRules = [
                'status' => 'required|in:1,0',
                'public_key' => 'required',
                'private_key' => 'required',
            ];
        } elseif ($this['gateway'] == 'moncash') {
            $additionalDataRules = [
                'status' => 'required|in:1,0',
                'client_id' => 'required',
                'secret_key' => 'required',
            ];
        } elseif ($this['gateway'] == 'pvit') {
            $additionalDataRules = [
                'status' => 'required|in:1,0',
                'mc_tel_merchant' => 'required',
                'access_token' => 'required',
                'mc_merchant_code' => 'required',
            ];
        } elseif ($this['gateway'] == 'ccavenue') {
            $additionalDataRules = [
                'status' => 'required|in:1,0',
                'merchant_id' => 'required',
                'working_key' => 'required',
                'access_code' => 'required',
            ];
        } elseif ($this['gateway'] == 'foloosi') {
            $additionalDataRules = [
                'status' => 'required|in:1,0',
                'merchant_key' => 'required',
            ];
        } elseif ($this['gateway'] == 'iyzi_pay') {
            $additionalDataRules = [
                'status' => 'required|in:1,0',
                'api_key' => 'required',
                'secret_key' => 'required',
                'base_url' => 'required',
            ];
        } elseif ($this['gateway'] == 'xendit') {
            $additionalDataRules = [
                'status' => 'required|in:1,0',
                'api_key' => 'required',
            ];
        } elseif ($this['gateway'] == 'fatoorah') {
            $additionalDataRules = [
                'status' => 'required|in:1,0',
                'api_key' => 'required',
            ];
        }

        return $additionalDataRules;
    }

    protected function findPaymentGateway(?string $keyName): ?Model
    {
        return $this->settingService->findBy(params: [
            'key_name' => $keyName,
            'settings_type' => SettingService::PAYMENT_CONFIG_TYPE,
        ]);
    }

    private function ensurePaymobAcceptDefaults(): void
    {
        $gateway = $this->findPaymentGateway(keyName: self::PAYMOB_ACCEPT_GATEWAY);
        $defaults = $this->paymobAcceptDefaults();

        $gateway === null
            ? $this->settingService->create(data: $defaults)
            : $this->settingService->update(id: $gateway->id, data: $defaults);
    }

    /** @return array<string, mixed> */
    private function paymobAcceptDefaults(): array
    {
        return [
            'key_name' => self::PAYMOB_ACCEPT_GATEWAY,
            'live_values' => $this->paymobAcceptValues(mode: 'live'),
            'test_values' => $this->paymobAcceptValues(mode: 'test'),
            'settings_type' => SettingService::PAYMENT_CONFIG_TYPE,
            'mode' => 'test',
            'is_active' => 0,
            'additional_data' => null,
        ];
    }

    /** @return array<string, string> */
    private function paymobAcceptValues(string $mode): array
    {
        return [
            'gateway' => '',
            'mode' => $mode,
            'status' => '0',
            'supported_country' => '',
            'public_key' => '',
            'secret_key' => '',
            'integration_id' => '',
            'hmac' => '',
        ];
    }
}
