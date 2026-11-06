<?php

namespace App\Http\Requests\Vendor\Shipping;

use App\Http\Requests\BaseRequest;
use Illuminate\Contracts\Validation\ValidationRule;

class MethodStatusUpdateRequest extends BaseRequest
{
    protected function prepareForValidation(): void
    {
        $this->merge(['id' => $this->route('id') ?? $this->input('id')]);
    }

    /**
     * @return array<string, ValidationRule|array|string>
     */
    public function rules(): array
    {
        return [
            'id' => 'required',
            'status' => 'required|in:1,0',
        ];
    }

    public function messages(): array
    {
        return [
            'id.required' => translate('Please choose the shipping method you want to turn on or off.'),
            'status.required' => translate('Please choose whether the shipping method is on or off.'),
            'status.in' => translate('Please choose whether the shipping method is on or off.'),
        ];
    }
}
