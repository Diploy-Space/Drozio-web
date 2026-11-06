<?php

namespace App\Http\Requests\Admin\Setting\ThirdParty;

use App\Http\Requests\BaseRequest;
use App\Services\New\SettingService;

/**
 * @property string $gateway
 */
class SmsModuleUpdateRequest extends BaseRequest
{
    private const SMS_CONFIG_TYPE = 'sms_config';

    private const TWO_FACTOR_GATEWAY = '2factor';

    private const TWO_FACTOR_DEFAULT_OTP_TEMPLATE = 'Your OTP is: #OTP#';

    private const GATEWAY_RULES = [
        'releans' => [
            'status' => 'required|in:1,0',
            'api_key' => 'required',
            'from' => 'required',
            'otp_template' => 'required',
        ],
        'twilio' => [
            'status' => 'required|in:1,0',
            'sid' => 'required',
            'messaging_service_sid' => 'required',
            'token' => 'required',
            'from' => 'required',
            'otp_template' => 'required',
        ],
        'nexmo' => [
            'status' => 'required|in:1,0',
            'api_key' => 'required',
            'api_secret' => 'required',
            'token' => 'required',
            'from' => 'required',
            'otp_template' => 'required',
        ],
        '2factor' => [
            'status' => 'required|in:1,0',
            'api_key' => 'required',
            'otp_template' => 'required',
        ],
        'msg91' => [
            'status' => 'required|in:1,0',
            'template_id' => 'required',
            'auth_key' => 'required',
        ],
        'hubtel' => [
            'status' => 'required|in:1,0',
            'sender_id' => 'required',
            'client_id' => 'required',
            'client_secret' => 'required',
            'otp_template' => 'required',
        ],
        'paradox' => [
            'status' => 'required|in:1,0',
            'api_key' => 'required',
            'sender_id' => 'required',
        ],
        'signal_wire' => [
            'status' => 'required|in:1,0',
            'project_id' => 'required',
            'token' => 'required',
            'space_url' => 'required',
            'from' => 'required',
            'otp_template' => 'required',
        ],
        '019_sms' => [
            'status' => 'required|in:1,0',
            'password' => 'required',
            'username' => 'required',
            'username_for_token' => 'required',
            'sender' => 'required',
            'otp_template' => 'required',
        ],
        'viatech' => [
            'status' => 'required|in:1,0',
            'api_url' => 'required',
            'api_key' => 'required',
            'sender_id' => 'required',
            'otp_template' => 'required',
        ],
        'global_sms' => [
            'status' => 'required|in:1,0',
            'user_name' => 'required',
            'password' => 'required',
            'from' => 'required',
            'otp_template' => 'required',
        ],
        'akandit_sms' => [
            'status' => 'required|in:1,0',
            'username' => 'required',
            'password' => 'required',
            'otp_template' => 'required',
        ],
        'sms_to' => [
            'status' => 'required|in:1,0',
            'api_key' => 'required',
            'sender_id' => 'required',
            'otp_template' => 'required',
        ],
        'alphanet_sms' => [
            'status' => 'required|in:1,0',
            'api_key' => 'required',
            'otp_template' => 'required',
        ],
    ];

    public function __construct(
        private readonly SettingService $settingService,
    ) {
        parent::__construct();
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['status' => $this->has('status') ? (int) $this['status'] : 0]);

        if ($this['gateway'] == self::TWO_FACTOR_GATEWAY && ! $this['otp_template']) {
            $this->ensureTwoFactorOtpTemplate();
        }
    }

    private function ensureTwoFactorOtpTemplate(): void
    {
        $twoFactor = $this->settingService->findBy(params: [
            'key_name' => self::TWO_FACTOR_GATEWAY,
            'settings_type' => self::SMS_CONFIG_TYPE,
        ]);

        if ($twoFactor === null || empty($twoFactor['live_values'])) {
            return;
        }

        $credential = array_merge($twoFactor['live_values'], [
            'otp_template' => $twoFactor['live_values']['otp_template'] ?? self::TWO_FACTOR_DEFAULT_OTP_TEMPLATE,
        ]);

        $this->settingService->update(id: $twoFactor->id, data: [
            'live_values' => $credential,
            'test_values' => $credential,
        ]);
    }

    public function rules(): array
    {
        return array_merge([
            'gateway' => 'required|in:'.implode(',', array_keys(self::GATEWAY_RULES)),
            'mode' => 'required|in:live,test',
        ], self::GATEWAY_RULES[$this['gateway']] ?? []);
    }

    public function messages(): array
    {
        return [
            'gateway.required' => translate('The gateway field is required'),
        ];
    }
}
