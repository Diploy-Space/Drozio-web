<?php

namespace App\Http\Requests\Vendor\PaymentInfo;

use App\Http\Requests\BaseRequest;
use App\Services\Admin\ThirdParty\WithdrawMethodService;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class ApiRequest extends BaseRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array|string>
     */
    public function rules(): array
    {
        $rules = [
            'method_name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('vendor_withdraw_method_infos', 'method_name')->ignore($this->input('id')),
            ],
            'withdraw_method_id' => 'required|exists:withdrawal_methods,id',
        ];

        if ($this->input('id')) {
            $rules['id'] = 'exists:vendor_withdraw_method_infos,id';
        }

        return $rules;
    }

    public function messages(): array
    {
        return [
            'method_name.required' => translate('The method name field is required'),
            'method_name.string' => translate('Please enter a valid method name'),
            'method_name.max' => translate('The method name must not exceed 255 characters'),
            'method_name.unique' => translate('Name has been taken'),
            'withdraw_method_id.required' => translate('The withdraw method field is required'),
            'withdraw_method_id.exists' => translate('The selected withdraw method is invalid'),
            'id.exists' => translate('This payment information is not valid'),
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator) {
                $methodFields = app(WithdrawMethodService::class)->findMethodFields($this['withdraw_method_id']);

                if ($methodFields === null) {
                    $validator->errors()->add(
                        'withdraw_method_id', translate('The selected withdraw method is invalid')
                    );
                }

                foreach ($methodFields ?? [] as $field) {
                    if (isset($field['is_required']) && $field['is_required'] == 1) {
                        if (! isset($this['method_info'][$field['input_name']]) || empty($this['method_info'][$field['input_name']])) {
                            $validator->errors()->add(
                                'method_info_'.$field['input_name'], translate(':field is required', ['field' => $field['input_name']])
                            );
                        }
                    }
                }

            },
        ];
    }
}
