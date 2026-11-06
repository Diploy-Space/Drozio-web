<?php

namespace App\Http\Requests\Admin\Report\ProductWishlist;

use App\Http\Requests\BaseRequest;
use App\Services\New\ProductWishlistReportService;
use Illuminate\Validation\Rule;

class FilterRequest extends BaseRequest
{
    public function rules(): array
    {
        return [
            'seller_id' => ['nullable', Rule::when(! in_array($this['seller_id'], [ProductWishlistReportService::SELLER_ALL, ProductWishlistReportService::SELLER_IN_HOUSE], true), ['integer', 'min:1'])],
            'sort' => ['nullable', Rule::in(ProductWishlistReportService::SORTS)],
            'search' => ['nullable', 'string', 'max:255'],
            'page' => ['nullable', 'integer', 'min:1'],
        ];
    }

    public function messages(): array
    {
        return [
            'seller_id.integer' => translate('Choose a valid vendor'),
            'seller_id.min' => translate('Choose a valid vendor'),
            'sort.in' => translate('Choose a valid sort order'),
            'search.string' => translate('Enter a valid search term'),
            'search.max' => translate('The search term is too long'),
            'page.integer' => translate('Choose a valid page'),
            'page.min' => translate('Choose a valid page'),
        ];
    }

    public function sellerId(): int|string|null
    {
        $sellerId = $this->validated('seller_id');

        return match ($sellerId) {
            null, ProductWishlistReportService::SELLER_ALL => null,
            ProductWishlistReportService::SELLER_IN_HOUSE => ProductWishlistReportService::SELLER_IN_HOUSE,
            default => (int) $sellerId,
        };
    }

    public function sort(): string
    {
        return $this->validated('sort') ?? ProductWishlistReportService::SORT_WISHLIST_HIGH_TO_LOW;
    }
}
