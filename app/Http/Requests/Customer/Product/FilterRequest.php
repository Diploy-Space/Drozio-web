<?php

namespace App\Http\Requests\Customer\Product;

use App\Services\New\ProductRetailService;
use App\Services\New\ShopService;
use Closure;

class FilterRequest extends ListRequest
{
    public const FACET_CATEGORY = 'category';

    public const FACET_BRAND = 'brand';

    public const FACET_PUBLISHING_HOUSE = 'publishing_houses';

    public const FACET_AUTHOR = 'product_authors';

    public function __construct(
        ProductRetailService $productService,
        private readonly ShopService $shopService,
    ) {
        parent::__construct($productService);
    }

    public function rules(): array
    {
        if ($this->isApiRequest()) {
            return $this->apiRules();
        }

        return [
            ...parent::rules(),
            'per_page_product' => ['nullable', 'integer', 'min:1'],
            'shop_id' => ['nullable', 'integer', 'min:0', $this->shopExistsRule()],
        ];
    }

    public function messages(): array
    {
        if ($this->isApiRequest()) {
            return $this->apiMessages();
        }

        return [
            ...parent::messages(),
            'per_page_product.integer' => translate('Please provide a valid number of products per page'),
            'per_page_product.min' => translate('Please provide a valid number of products per page'),
            'shop_id.integer' => translate('Please select a valid shop'),
            'shop_id.min' => translate('Please select a valid shop'),
        ];
    }

    public function searchKeyword(): string
    {
        return base64_decode((string) $this->input('search', ''));
    }

    public function hasSearchKeyword(): bool
    {
        return ! empty($this->input('search'));
    }

    /**
     * @return array<int, mixed>
     */
    public function facetIds(string $facet): array
    {
        $value = $this->input($facet);

        if (is_string($value)) {
            $value = json_decode($value, true);
        }

        return is_array($value) ? array_values($value) : [];
    }

    public function dataLimit(): int
    {
        return (int) $this->input('limit') ?: DEFAULT_DATA_LIMIT;
    }

    public function page(): int
    {
        return max(1, (int) $this->input('offset', 1));
    }

    private function apiRules(): array
    {
        return [
            'search' => ['nullable', 'string'],
            self::FACET_CATEGORY => ['nullable'],
            self::FACET_BRAND => ['nullable', $this->brandListRule()],
            self::FACET_PUBLISHING_HOUSE => ['nullable'],
            self::FACET_AUTHOR => ['nullable'],
            'product_type' => ['nullable'],
            'sort_by' => ['nullable'],
            'offer_type' => ['nullable'],
            'price_min' => ['nullable'],
            'price_max' => ['nullable'],
            'limit' => ['nullable', 'integer', 'min:0'],
            'offset' => ['nullable', 'integer'],
        ];
    }

    private function apiMessages(): array
    {
        return [
            'search.string' => translate('Please enter a valid search keyword'),
            'limit.integer' => translate('The number of products per page must be a whole number'),
            'limit.min' => translate('The number of products per page cannot be negative'),
            'offset.integer' => translate('The page number must be a whole number'),
        ];
    }

    private function brandListRule(): Closure
    {
        return function (string $attribute, mixed $value, Closure $fail): void {
            $decoded = is_string($value) ? json_decode($value, true) : $value;

            if (! is_array($decoded)) {
                $fail(translate('Please select a valid brand'));
            }
        };
    }

    private function shopExistsRule(): Closure
    {
        return function (string $attribute, mixed $value, Closure $fail): void {
            if ((int) $value !== ShopService::IN_HOUSE_ID && ! $this->shopService->exists(['id' => $value])) {
                $fail(translate('This shop does not exist'));
            }
        };
    }
}
