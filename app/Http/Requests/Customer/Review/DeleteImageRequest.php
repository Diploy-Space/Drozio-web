<?php

namespace App\Http\Requests\Customer\Review;

use App\Http\Requests\BaseRequest;

class DeleteImageRequest extends BaseRequest
{
    public function rules(): array
    {
        return [
            'id' => 'required',
            'name' => 'required|string',
        ];
    }

    public function messages(): array
    {
        return [
            'id.required' => translate('Please select a review'),
            'name.required' => translate('Review image is required'),
            'name.string' => translate('Review image is required'),
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->isApiRequest() && $this->route('id') !== null) {
            $this->merge(['id' => $this->route('id')]);
        }
    }
}
