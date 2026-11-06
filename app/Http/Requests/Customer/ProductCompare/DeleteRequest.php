<?php

namespace App\Http\Requests\Customer\ProductCompare;

use App\Http\Requests\BaseRequest;

class DeleteRequest extends BaseRequest
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
            'id.required' => translate('Please select a compare list item'),
            'id.integer' => translate('The selected compare list item is not valid'),
        ];
    }
}
