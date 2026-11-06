<?php

namespace App\Http\Requests\Admin\Erp;

use App\Http\Requests\BaseRequest;
use Illuminate\Contracts\Validation\ValidationRule;

class TokenAddRequest extends BaseRequest
{
    /**
     * @return array<string, ValidationRule|array|string>
     */
    public function rules(): array
    {
        return [
            'name' => 'required|string|max:100',
            'webhook_url' => 'required|url|max:500',
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => translate(':field is required', ['field' => translate('Name')]),
            'name.max' => translate('Name may not be greater than 100 characters'),
            'webhook_url.required' => translate('Webhook url is required'),
            'webhook_url.url' => translate('Please provide a valid webhook url'),
            'webhook_url.max' => translate('Webhook url may not be greater than 500 characters'),
        ];
    }
}
