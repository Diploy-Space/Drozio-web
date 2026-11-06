<?php

namespace App\Http\Requests\Admin\Setting\Website;

use App\Http\Requests\BaseRequest;
use Illuminate\Contracts\Validation\ValidationRule;

class SocialMediaUpdateRequest extends BaseRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array|string>
     */
    public function rules(): array
    {
        return [
            'name' => 'required',
            'link' => 'required',
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => translate('The name field is required'),
            'name.unique' => translate('Name has been taken'),
            'link.required' => translate('The link field is required'),
        ];
    }
}
