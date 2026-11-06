<?php

namespace App\Http\Requests\Admin\Setting\ThirdParty;

use App\Http\Requests\BaseRequest;

class OfflinePaymentAddRequest extends BaseRequest
{
    public function rules(): array
    {
        return [
            'method_name' => 'required|string|max:255',
            'input_name' => 'required|array|min:1',
            'input_name.*' => 'required|string|max:255',
            'input_data' => 'required|array|min:1',
            'input_data.*' => 'required|string|max:255',
            'customer_input' => 'required|array|min:1',
            'customer_input.*' => 'required|string|max:255',
            'customer_placeholder' => 'required|array|min:1',
            'customer_placeholder.*' => 'required|string|max:255',
        ];
    }

    public function messages(): array
    {
        return [
            'method_name.required' => translate(':field is required', ['field' => translate('Method name')]),
            'input_name.*.required' => translate(':field is required', ['field' => translate('Input field Name')]),
            'input_data.*.required' => translate(':field is required', ['field' => translate('Input data')]),
            'customer_input.*.required' => translate('Customer input field is required'),
            'customer_placeholder.*.required' => translate('Customer placeholder is required'),
        ];
    }
}
