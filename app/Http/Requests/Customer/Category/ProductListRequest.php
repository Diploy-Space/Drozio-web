<?php

namespace App\Http\Requests\Customer\Category;

use App\Http\Requests\BaseRequest;

class ProductListRequest extends BaseRequest
{
    public const FACET_BRAND = 'brand_ids';

    public const FACET_PUBLISHING_HOUSE = 'publishing_houses';

    public const FACET_AUTHOR = 'product_authors';

    public const DATA_LIMIT_ALL = 'all';

    public function rules(): array
    {
        return [
            'search' => ['nullable', 'string', 'max:255'],
            'product_type' => ['nullable'],
            self::FACET_BRAND => ['nullable'],
            self::FACET_PUBLISHING_HOUSE => ['nullable'],
            self::FACET_AUTHOR => ['nullable'],
            'limit' => ['nullable', 'integer', 'min:0'],
            'offset' => ['nullable', 'integer'],
        ];
    }

    public function messages(): array
    {
        return [
            'search.string' => translate('Please enter a valid search keyword'),
            'search.max' => translate('The search keyword may not be greater than 255 characters'),
            'limit.integer' => translate('The number of products per page must be a whole number'),
            'limit.min' => translate('The number of products per page cannot be negative'),
            'offset.integer' => translate('The page number must be a whole number'),
        ];
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

    public function dataLimit(): int|string
    {
        $limit = $this->input('limit');

        return $limit === null || $limit === '' ? self::DATA_LIMIT_ALL : (int) $limit;
    }

    public function page(): int
    {
        return max(1, (int) $this->input('offset', 1));
    }
}
