<?php

namespace App\Http\Requests\Customer\Auth;

use App\Http\Requests\BaseRequest;
use App\Services\New\Auth\CustomerAuthService;
use Closure;

class RegistrationRequest extends BaseRequest
{
    public function __construct(private readonly CustomerAuthService $customerAuthService)
    {
        parent::__construct();
    }

    public function rules(): array
    {
        $rules = [
            'f_name' => 'required|string|max:100',
            'email' => ['required', 'email', 'max:100', $this->unusedIdentity()],
            'phone' => ['required', 'string', 'max:20', $this->unusedIdentity()],
            'password' => 'required|string|min:8',
            'referral_code' => ['nullable', 'string', 'max:255', $this->knownReferralCode()],
        ];

        if ($this->isApiRequest()) {
            $rules['l_name'] = 'required|string|max:100';

            return $rules;
        }

        $rules['l_name'] = 'nullable|string|max:100';
        $rules['password'] = 'required|string|min:8|same:con_password';

        return $rules;
    }

    public function messages(): array
    {
        return [
            'f_name.required' => translate('First name is required'),
            'f_name.max' => translate('First name must not be longer than 100 characters'),
            'l_name.required' => translate('Last name is required'),
            'l_name.max' => translate('Last name must not be longer than 100 characters'),
            'email.required' => translate('Email is required'),
            'email.email' => translate('Enter a valid email address'),
            'phone.required' => translate('Phone number is required'),
            'phone.max' => translate('Phone number must not be longer than 20 characters'),
            'password.required' => translate('Password is required'),
            'password.min' => translate('Password must be at least 8 characters'),
            'password.same' => translate('Password and confirm password do not match'),
            'referral_code.max' => translate('Referral code must not be longer than 255 characters'),
        ];
    }

    private function knownReferralCode(): Closure
    {
        return function (string $attribute, mixed $value, Closure $fail): void {
            if ($this->customerAuthService->findByReferralCode(code: (string) $value) === null) {
                $fail(translate('This referral code does not belong to any account'));
            }
        };
    }

    private function unusedIdentity(): Closure
    {
        return function (string $attribute, mixed $value, Closure $fail): void {
            if ($this->customerAuthService->identityExists(identity: (string) $value)) {
                $fail(translate('This :field is already registered', ['field' => translate($attribute === 'email' ? 'email' : 'phone number')]));
            }
        };
    }
}
