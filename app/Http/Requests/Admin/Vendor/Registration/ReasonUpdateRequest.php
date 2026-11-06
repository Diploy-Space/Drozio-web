<?php

namespace App\Http\Requests\Admin\Vendor\Registration;

use App\Http\Requests\BaseRequest;

class ReasonUpdateRequest extends BaseRequest
{
    public function rules(): array
    {
        return [
            'title' => ['required', 'string'],
            'description' => ['required', 'string'],
            'priority' => ['required', 'integer'],
        ];
    }

    public function messages(): array
    {
        return [
            'title.required' => translate('The title field is required'),
            'description.required' => translate('The description field is required'),
            'priority.required' => translate('Please select priority'),
        ];
    }
}
