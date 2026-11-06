<?php

namespace App\Http\Requests\Shared\Report\ProductStock;

use App\Http\Requests\BaseRequest;
use App\Services\New\ProductStockReportService;
use Illuminate\Validation\Rule;

class FilterRequest extends BaseRequest
{
    private const CATEGORY_ALL = 'all';

    public function rules(): array
    {
        return [
            'seller_id' => ['nullable', Rule::when(! in_array($this['seller_id'], [ProductStockReportService::SELLER_ALL, ProductStockReportService::SELLER_IN_HOUSE], true), ['integer', 'min:1'])],
            'category_id' => ['nullable', Rule::when($this['category_id'] !== self::CATEGORY_ALL, ['integer', 'min:1'])],
            'sort' => ['nullable', Rule::in(ProductStockReportService::SORTS)],
            'search' => ['nullable', 'string', 'max:255'],
            'page' => ['nullable', 'integer', 'min:1'],
        ];
    }

    public function messages(): array
    {
        return [
            'seller_id.integer' => translate('Choose a valid vendor'),
            'seller_id.min' => translate('Choose a valid vendor'),
            'category_id.integer' => translate('Choose a valid category'),
            'category_id.min' => translate('Choose a valid category'),
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
            null, ProductStockReportService::SELLER_ALL => null,
            ProductStockReportService::SELLER_IN_HOUSE => ProductStockReportService::SELLER_IN_HOUSE,
            default => (int) $sellerId,
        };
    }

    public function categoryId(): ?int
    {
        $categoryId = $this->validated('category_id');

        return $categoryId === null || $categoryId === self::CATEGORY_ALL ? null : (int) $categoryId;
    }

    public function sort(): string
    {
        return $this->validated('sort') ?? ProductStockReportService::SORT_STOCK_LOW_TO_HIGH;
    }
}
