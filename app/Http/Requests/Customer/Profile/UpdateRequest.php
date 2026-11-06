<?php

namespace App\Http\Requests\Customer\Profile;

use App\Http\Requests\BaseRequest;
use App\Services\New\CustomerService;
use App\Traits\CalculatorTrait;
use App\Traits\RecaptchaTrait;
use Closure;
use Illuminate\Validation\Validator;

class UpdateRequest extends BaseRequest
{
    use CalculatorTrait;
    use RecaptchaTrait;

    public const NAME_MAX_LENGTH = 25;

    protected $stopOnFirstFailure = true;

    public function __construct(private readonly CustomerService $customerService)
    {
        parent::__construct();
    }

    public function rules(): array
    {
        return [
            'f_name' => 'required|string|max:'.self::NAME_MAX_LENGTH,
            'l_name' => 'required|string|max:'.self::NAME_MAX_LENGTH,
            'phone' => [
                'required',
                'max:20',
                function (string $attribute, mixed $value, Closure $fail): void {
                    if ($this->isTakenByAnotherCustomer(column: 'phone', value: $value)) {
                        $fail(translate('Phone number has already been taken'));
                    }
                },
            ],
            'email' => [
                'nullable',
                'email',
                'max:255',
                function (string $attribute, mixed $value, Closure $fail): void {
                    if ($this->isTakenByAnotherCustomer(column: 'email', value: $value)) {
                        $fail('validation.unique')->translate();
                    }
                },
            ],
            'image' => getRulesStringForImageValidation(
                rules: ['nullable'],
                skipMimes: ['.svg', '.gif'],
                maxSize: getFileUploadMaxSize(unit: 'kb'),
                isDisallowed: true
            ),
        ];
    }

    public function messages(): array
    {
        return [
            'f_name.required' => translate(':field is required', ['field' => translate('First Name')]),
            'f_name.max' => translate('First name may not be greater than :max characters', ['max' => self::NAME_MAX_LENGTH]),
            'l_name.required' => translate(':field is required', ['field' => translate('Last Name')]),
            'l_name.max' => translate('Last name may not be greater than :max characters', ['max' => self::NAME_MAX_LENGTH]),
            'phone.required' => translate(':field is required', ['field' => translate('Phone number')]),
            'phone.max' => translate('The phone number may not be greater than 20 characters'),
            'image.mimes' => translate('The image type must be').'.jpg, .png, .jpeg, .gif, .bmp, .tif, .tiff,.webp',
            'image.max' => translate('The image may not be greater than 2 MB'),
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator) {
                $numericPhoneValue = preg_replace('/[^0-9]/', '', $this['phone']);
                $numericLength = strlen($numericPhoneValue);
                if ($numericLength < 4) {
                    $validator->errors()->add(
                        'phone.min', translate('The phone number must be at least 4 characters')
                    );
                }

                if ($numericLength > 20) {
                    $validator->errors()->add(
                        'phone.max', translate('The phone number may not be greater than 20 characters')
                    );
                }

                if ($this['password'] && ! empty($this['password']) && empty($this['confirm_password'])) {
                    $validator->errors()->add(
                        'confirm_password.required', translate(':field is required', ['field' => translate('Confirm password')])
                    );
                } elseif ($this['confirm_password'] && ! empty($this['confirm_password']) && empty($this['password'])) {
                    $validator->errors()->add(
                        'password.required', translate(':field is required', ['field' => translate('Password')])
                    );
                } elseif ($this['password'] != $this['confirm_password']) {
                    $validator->errors()->add(
                        'password.same:confirm_password', translate('Password must match the confirm password')
                    );
                }
            },
        ];
    }

    private function isTakenByAnotherCustomer(string $column, mixed $value): bool
    {
        $customer = $this->customerService->findBy(params: [$column => $value]);

        return $customer !== null && $customer->id != auth('customer')->id();
    }
}
