<?php

namespace Modules\Blog\app\Http\Requests\Admin\PrioritySetup;

use Illuminate\Validation\Rule;
use Modules\Blog\app\Enums\PrioritySetting;
use Modules\Blog\app\Http\Requests\BaseRequest;

class UpdateRequest extends BaseRequest
{
    public function rules(): array
    {
        return [
            'type' => ['required', Rule::in(PrioritySetting::ALL)],
            'sort_by' => ['required', Rule::in(['most_clicked', 'a_to_z', 'z_to_a'])],
        ];
    }

    public function messages(): array
    {
        return [
            'type.required' => translate(':field is required', ['field' => translate('Sorting list')]),
            'type.in' => translate('This sorting list does not exist'),
            'sort_by.required' => translate('Select how this list is sorted'),
            'sort_by.in' => translate('This sorting order is not available'),
        ];
    }
}
