<?php

namespace App\Http\Requests\Customer\Auth;

use App\Http\Requests\BaseRequest;
use App\Services\New\LoginSetupService;
use Devrabiul\ToastMagic\Facades\ToastMagic;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class LoginByEmailOrPhoneRequest extends BaseRequest
{
    public const LOGIN_TYPE_OTP = 'otp-login';

    public const LOGIN_TYPE_MANUAL = 'manual-login';

    private const OTP_PHONE_MIN_LENGTH = 6;

    private const OTP_PHONE_MAX_LENGTH = 20;

    private ?array $loginOptions = null;

    public function __construct(
        private readonly LoginSetupService $loginSetupService,
    ) {}

    /**
     * @return array<string, ValidationRule|array|string>
     */
    public function rules(): array
    {
        if ($this->isOtpLogin()) {
            return [
                'phone' => 'required|min:'.self::OTP_PHONE_MIN_LENGTH.'|max:'.self::OTP_PHONE_MAX_LENGTH,
            ];
        }

        if ($this->isManualLogin()) {
            return [
                'user_identity' => 'required',
                'password' => 'required',
            ];
        }

        return [];
    }

    public function messages(): array
    {
        return [
            'phone.required' => translate('Phone number is required'),
            'phone.min' => translate('The phone number must be at least :min characters', ['min' => self::OTP_PHONE_MIN_LENGTH]),
            'phone.max' => translate('The phone number may not be greater than 20 characters'),
            'user_identity.required' => translate('The email or phone field is required'),
            'password.required' => translate('The password field is required'),
        ];
    }

    public function isOtpLogin(): bool
    {
        return $this->input('login_type') === self::LOGIN_TYPE_OTP
            && ! empty($this->loginOptions()[LoginSetupService::LOGIN_OPTION_OTP]);
    }

    public function isManualLogin(): bool
    {
        return $this->input('login_type') === self::LOGIN_TYPE_MANUAL
            && ! empty($this->loginOptions()[LoginSetupService::LOGIN_OPTION_MANUAL]);
    }

    protected function failedValidation(Validator $validator): void
    {
        if (! $this->isOtpLogin()) {
            parent::failedValidation($validator);

            return;
        }

        $errors = $this->formatLegacyValidationErrors($validator);

        if ($this->ajax()) {
            throw new HttpResponseException(response()->json(['errors' => $errors], 403));
        }

        foreach ($errors as $error) {
            ToastMagic::error($error['message']);
        }

        throw new HttpResponseException(redirect()->back());
    }

    private function loginOptions(): array
    {
        return $this->loginOptions ??= $this->loginSetupService->getLoginOptions();
    }
}
