<?php

namespace App\Http\Requests\Admin\ClearanceSale;

use App\Http\Requests\BaseRequest;
use Illuminate\Validation\Rule;

class PrioritySetupUpdateRequest extends BaseRequest
{
    protected $stopOnFirstFailure = true;

    public function rules(): array
    {
        return [
            'sort_by' => ['nullable', Rule::in([
                'latest_created', 'clearance_expiration_date', 'most_order', 'reviews_count', 'rating', 'a_to_z', 'z_to_a',
            ])],
            'out_of_stock_product' => ['nullable', Rule::in(['desc', 'hide'])],
            'temporary_close_sorting' => ['nullable', Rule::in(['default', 'desc'])],
            'vendor_priorities_id' => ['nullable', 'array'],
            'vendor_priorities_id.*' => ['integer'],
        ];
    }

    public function messages(): array
    {
        return [
            'sort_by.in' => translate('This sorting order is not available'),
            'out_of_stock_product.in' => translate('This out of stock option is not available'),
            'temporary_close_sorting.in' => translate('This temporarily closed shop option is not available'),
            'vendor_priorities_id.array' => translate('Select the shops from the list'),
            'vendor_priorities_id.*.integer' => translate('Select the shops from the list'),
        ];
    }
}
