<?php

namespace App\Http\Requests\Admin\Product;

use App\Http\Requests\BaseRequest;
use Illuminate\Contracts\Validation\ValidationRule;

class DenyRequest extends BaseRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array|string>
     */
    public function rules(): array
    {
        return [
            'denied_note' => 'required',
        ];
    }
    public function messages(): array
    {
        return [
            'denied_note.required' => translate('The denied note field is required').'.',
        ];
    }
}
