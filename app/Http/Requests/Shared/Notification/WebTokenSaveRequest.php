<?php

namespace App\Http\Requests\Shared\Notification;

use App\Http\Requests\BaseRequest;
use Illuminate\Contracts\Validation\ValidationRule;

class WebTokenSaveRequest extends BaseRequest
{
    /**
     * @return array<string, ValidationRule|array|string>
     */
    public function rules(): array
    {
        return [
            'token' => 'required|string',
        ];
    }

    public function messages(): array
    {
        return [
            'token.required' => translate(':field is required', ['field' => translate('Token')]),
        ];
    }
}
