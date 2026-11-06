<?php

namespace App\Http\Requests\Customer\Category;

use App\Http\Requests\BaseRequest;

class ChildrenRequest extends BaseRequest
{
    protected function prepareForValidation(): void
    {
        $this->merge(['id' => $this->route('id')]);
    }

    public function rules(): array
    {
        return [
            'id' => ['required', 'integer'],
        ];
    }

    public function messages(): array
    {
        return [
            'id.required' => translate('Please select a category'),
            'id.integer' => translate('The selected category is not valid'),
        ];
    }
}
