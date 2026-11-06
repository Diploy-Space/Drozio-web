<?php

namespace App\Http\Requests\Customer\Brand;

use App\Http\Requests\BaseRequest;
use Illuminate\Validation\Rule;

class ListRequest extends BaseRequest
{
    public const ORDER_ASCENDING = 'asc';

    public const ORDER_DESCENDING = 'desc';

    public function rules(): array
    {
        return [
            'search' => ['nullable', 'string', 'max:255'],
            'order_by' => ['nullable', Rule::in([self::ORDER_ASCENDING, self::ORDER_DESCENDING])],
            'page' => ['nullable', 'integer', 'min:1'],
            'limit' => ['nullable', 'string'],
            'offset' => ['nullable', 'integer'],
            'sort_by' => ['nullable', 'string'],
            'shop_slug' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'search.string' => translate('Please enter a valid search keyword'),
            'search.max' => translate('The search keyword may not be greater than 255 characters'),
            'order_by.in' => translate('Please select a valid sorting option'),
            'page.integer' => translate('Please select a valid page'),
            'page.min' => translate('Please select a valid page'),
            'limit.string' => translate('Please select a valid number of brands per page'),
            'offset.integer' => translate('The page number must be a whole number'),
            'sort_by.string' => translate('Please select a valid sorting option'),
            'shop_slug.string' => translate('Please select a valid shop'),
            'shop_slug.max' => translate('Please select a valid shop'),
        ];
    }

    public function shopSlug(): ?string
    {
        return $this->filled('shop_slug') ? (string) $this->input('shop_slug') : null;
    }
}
