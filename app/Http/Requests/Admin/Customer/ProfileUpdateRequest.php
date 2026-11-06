<?php

namespace App\Http\Requests\Admin\Customer;

use App\Http\Requests\BaseRequest;
use App\Traits\CalculatorTrait;
use App\Traits\RecaptchaTrait;
use Illuminate\Validation\Rule;

class ProfileUpdateRequest extends BaseRequest
{
    use CalculatorTrait;
    use RecaptchaTrait;

    public const NAME_MAX_LENGTH = 50;

    protected $stopOnFirstFailure = true;

    public function rules(): array
    {
        return [
            'f_name' => ['required', 'string', 'max:'.self::NAME_MAX_LENGTH],
            'l_name' => ['required', 'string', 'max:'.self::NAME_MAX_LENGTH],
            'email' => [
                'required',
                'max:50',
                Rule::unique('users', 'email')->ignore($this->id, 'id'),
            ],
            'image' => getRulesStringForImageValidation(
                rules: ['nullable', 'image'],
                skipMimes: ['.svg'],
            ),
        ];
    }

    public function messages(): array
    {
        return [
            'f_name.required' => translate(':field is required', ['field' => translate('First Name')]),
            'f_name.max' => translate('First name must not be longer than :max characters', ['max' => self::NAME_MAX_LENGTH]),
            'l_name.required' => translate(':field is required', ['field' => translate('Last Name')]),
            'l_name.max' => translate('Last name must not be longer than :max characters', ['max' => self::NAME_MAX_LENGTH]),
            'phone.required' => translate(':field is required', ['field' => translate('Phone number')]),
            'phone.unique' => translate('Phone number has already been taken'),
            'phone.max' => translate('The phone number may not be greater than 20 characters'),
            'image.mimes' => translate('The image type must be').'.jpg, .png, .jpeg, .gif, .bmp, .tif, .tiff,.webp',
        ];
    }
}
