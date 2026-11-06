<?php

namespace App\Http\Requests\Vendor\RestockRequest;

use App\Http\Requests\BaseRequest;
use Carbon\Carbon;

class ListRequest extends BaseRequest
{
    protected function prepareForValidation(): void
    {
        $brandIds = $this->input('brand_ids');

        if (is_string($brandIds) && $brandIds !== '') {
            $decoded = json_decode($brandIds, true);
            $this->merge(['brand_ids' => is_array($decoded) ? $decoded : $brandIds]);
        }
    }

    public function rules(): array
    {
        return [
            'limit' => 'nullable|integer|min:1',
            'offset' => 'nullable|integer|min:1',
            'search' => 'nullable|string|max:255',
            'category_id' => ['nullable', 'regex:/^(all|\d+)$/'],
            'brand_ids' => 'nullable|array',
            'brand_ids.*' => 'integer',
            'restock_start_date' => 'nullable|date',
            'restock_end_date' => 'nullable|date',
        ];
    }

    public function messages(): array
    {
        return [
            'limit.integer' => translate('The number of items per page must be a whole number'),
            'limit.min' => translate('The number of items per page must be at least 1'),
            'offset.integer' => translate('The page number must be a whole number'),
            'offset.min' => translate('The page number must be at least 1'),
            'search.string' => translate('The search text must be text'),
            'search.max' => translate('The search text is too long'),
            'category_id.regex' => translate('The selected category is not valid'),
            'brand_ids.array' => translate('Brands must be sent as a list'),
            'brand_ids.*.integer' => translate('The selected brand is not valid'),
            'restock_start_date.date' => translate('The restock start date is not a valid date'),
            'restock_end_date.date' => translate('The restock end date is not a valid date'),
        ];
    }

    public function brandIds(): array
    {
        return array_values((array) ($this->validated('brand_ids') ?? []));
    }

    public function dateRange(): array
    {
        $startDate = $this->validated('restock_start_date');
        $endDate = $this->validated('restock_end_date');

        if (empty($startDate) || empty($endDate)) {
            return [];
        }

        return [Carbon::parse($startDate)->startOfDay(), Carbon::parse($endDate)->endOfDay()];
    }
}
