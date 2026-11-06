<?php

namespace App\Http\Requests\Admin\Report\VendorTax;

use App\Http\Requests\Admin\Report\Tax\FilterRequest as TaxFilterRequest;
use Illuminate\Validation\Rule;

class FilterRequest extends TaxFilterRequest
{
    public const SHOP_ALL = 'all';

    public function rules(): array
    {
        return [
            'dates' => ['nullable', 'string', 'regex:/^.+ - .+$/'],
            'shop_id' => ['nullable', 'regex:/^('.self::SHOP_ALL.'|\d+)$/'],
            'search' => ['nullable', 'string', 'max:255'],
            'export_type' => ['nullable', Rule::in([self::EXPORT_TYPE_EXCEL, self::EXPORT_TYPE_CSV])],
            'page' => ['nullable', 'integer', 'min:1'],
        ];
    }

    public function messages(): array
    {
        return [
            'dates.string' => translate('Choose a valid date range'),
            'dates.regex' => translate('Choose a valid date range'),
            'shop_id.regex' => translate('Choose a valid vendor'),
            'search.string' => translate('Enter a valid search term'),
            'search.max' => translate('The search term is too long'),
            'export_type.in' => translate('Choose a valid export type'),
            'page.integer' => translate('Choose a valid page'),
            'page.min' => translate('Choose a valid page'),
        ];
    }

    public function shopId(): ?int
    {
        return empty($this['shop_id']) || $this['shop_id'] === self::SHOP_ALL ? null : (int) $this['shop_id'];
    }
}
