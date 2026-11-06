<?php

namespace App\Http\Requests\Customer\Product;

use App\Http\Requests\BaseRequest;
use Illuminate\Validation\Rule;

class ViewStyleUpdateRequest extends BaseRequest
{
    public function rules(): array
    {
        return [
            'value' => ['required', Rule::in(ListRequest::PRODUCT_VIEW_STYLES)],
        ];
    }

    public function messages(): array
    {
        return [
            'value.required' => translate('Please select a view style'),
            'value.in' => translate('Please select a valid view style'),
        ];
    }
}
