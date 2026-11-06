<?php

namespace App\Http\Requests\Customer\Product;

use App\Http\Requests\BaseRequest;
use App\Services\New\ProductRetailService;
use Illuminate\Validation\Rule;

class ListRequest extends BaseRequest
{
    public const PRODUCT_VIEW_STYLES = ['grid-view', 'list-view'];

    private const VIEW_DATA_KEYS = [
        'id',
        'name',
        'brand_id',
        'category_id',
        'sub_category_id',
        'sub_sub_category_id',
        'data_from',
        'offer_type',
        'sort_by',
        'page_no' => 'page',
        'min_price',
        'max_price',
        'product_type',
        'shop_id',
        'author_id',
        'publishing_house_id',
        'search_category_value',
        'product_name',
    ];

    public const INPUT_BRAND_IDS = 'brand_ids';

    public const INPUT_CATEGORY_IDS = 'category';

    public const INPUT_PUBLISHING_HOUSE_IDS = 'publishing_houses';

    public const INPUT_AUTHOR_IDS = 'product_authors';

    public function __construct(private readonly ProductRetailService $productService) {}

    public function rules(): array
    {
        if ($this->isApiRequest()) {
            return [
                'search' => ['nullable', 'string'],
                'product_type' => ['nullable', 'string'],
                'offer_type' => ['nullable', 'string'],
                'product_id' => ['nullable', 'integer'],
                'limit' => ['nullable', 'integer', 'min:1'],
                'offset' => ['nullable', 'integer'],
            ];
        }

        return [
            'min_price' => ['nullable', 'numeric'],
            'max_price' => ['nullable', 'numeric'],
            'rating' => ['nullable', 'array'],
            'rating.*' => ['integer', 'between:1,5'],
            'sort_by' => ['nullable', Rule::in(ProductRetailService::STOREFRONT_SORTS)],
            'offer_type' => ['nullable', Rule::in(ProductRetailService::STOREFRONT_OFFER_TYPES)],
            'category_ids' => ['nullable', 'array'],
            'sub_category_ids' => ['nullable', 'array'],
            'sub_sub_category_ids' => ['nullable', 'array'],
            'brand_ids' => ['nullable', 'array'],
            'author_ids' => ['nullable', 'array'],
            'publishing_house_ids' => ['nullable', 'array'],
            'color_ids' => ['nullable', 'array'],
        ];
    }

    public function messages(): array
    {
        return [
            'min_price.numeric' => translate('Minimum price must be a number'),
            'max_price.numeric' => translate('Maximum price must be a number'),
            'rating.array' => translate('Please select a valid rating'),
            'rating.*.integer' => translate('Please select a valid rating'),
            'rating.*.between' => translate('Please select a valid rating'),
            'sort_by.in' => translate('Please select a valid sorting option'),
            'offer_type.in' => translate('Please select a valid offer type'),
            'category_ids.array' => translate('Please select a valid category'),
            'sub_category_ids.array' => translate('Please select a valid sub category'),
            'sub_sub_category_ids.array' => translate('Please select a valid sub sub category'),
            'brand_ids.array' => translate('Please select a valid brand'),
            'author_ids.array' => translate('Please select a valid author'),
            'publishing_house_ids.array' => translate('Please select a valid publishing house'),
            'color_ids.array' => translate('Please select a valid color'),
            'search.string' => translate('Please enter a valid search keyword'),
            'product_type.string' => translate('Please select a valid product type'),
            'offer_type.string' => translate('Please select a valid offer type'),
            'product_id.integer' => translate('Please select a valid product'),
            'limit.integer' => translate('The number of products per page must be a whole number'),
            'limit.min' => translate('The number of products per page must be at least 1'),
            'offset.integer' => translate('The page number must be a whole number'),
        ];
    }

    /**
     * @return array<int, mixed>
     */
    public function getInputIds(string $key): array
    {
        $value = $this->input($key);

        if (is_string($value)) {
            $value = json_decode($value, true);
        }

        return is_array($value) ? array_values($value) : [];
    }

    public function getLimit(int $default): int
    {
        return (int) $this->input('limit') ?: $default;
    }

    public function getPage(): int
    {
        return max(1, (int) $this->input('offset', 1));
    }

    /**
     * @return array<string, mixed>
     */
    public function filters(?string $dataFrom = null, ?string $offerType = null): array
    {
        $filters = $this->productService->getStorefrontFilters($this->all());

        if ($dataFrom !== null) {
            $filters[ProductRetailService::FILTER_DATA_FROM] = $dataFrom;
        }

        if ($offerType !== null) {
            $filters[ProductRetailService::FILTER_OFFER_TYPE] = $offerType;
        }

        return $filters;
    }

    /**
     * @return array<string, mixed>
     */
    public function viewData(): array
    {
        $data = [];

        foreach (self::VIEW_DATA_KEYS as $dataKey => $requestKey) {
            $data[is_int($dataKey) ? $requestKey : $dataKey] = $this[$requestKey];
        }

        $data['page'] = $this['page'] ?? 1;

        return $data;
    }

    public function productViewStyle(): ?string
    {
        return in_array($this['product_view'], self::PRODUCT_VIEW_STYLES, true) ? $this['product_view'] : null;
    }
}
