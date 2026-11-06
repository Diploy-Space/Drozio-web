<?php

namespace App\Http\Requests\Vendor\Product;

use App\Http\Requests\BaseRequest;
use App\Services\New\ProductRetailService;
use Illuminate\Validation\Rule;

class ListRequest extends BaseRequest
{
    private const PRODUCT_STATUSES = ['0', '1'];

    private const PRODUCT_TYPES = [
        ProductRetailService::PRODUCT_TYPE_PHYSICAL,
        ProductRetailService::PRODUCT_TYPE_DIGITAL,
    ];

    private const LIST_FIELDS = [
        'product_status',
        'filter_product_types',
        'filter_brand_ids',
        'filter_category_ids',
        'filter_sub_category_ids',
        'filter_sub_sub_category_ids',
    ];

    protected function prepareForValidation(): void
    {
        $this->decodeJsonEncodedArrays(fieldPatterns: self::LIST_FIELDS);

        foreach (self::LIST_FIELDS as $field) {
            $value = $this->input($field);

            if (is_scalar($value) && $value !== '') {
                $this->merge([$field => [$value]]);
            }
        }
    }

    public function rules(): array
    {
        return [
            'product_status' => ['nullable', 'array'],
            'product_status.*' => ['nullable', Rule::in(self::PRODUCT_STATUSES)],
            'filter_product_types' => ['nullable', 'array'],
            'filter_product_types.*' => ['nullable', Rule::in(self::PRODUCT_TYPES)],
            'filter_brand_ids' => ['nullable', 'array'],
            'filter_brand_ids.*' => ['integer'],
            'filter_category_ids' => ['nullable', 'array'],
            'filter_category_ids.*' => ['integer'],
            'filter_sub_category_ids' => ['nullable', 'array'],
            'filter_sub_category_ids.*' => ['integer'],
            'filter_sub_sub_category_ids' => ['nullable', 'array'],
            'filter_sub_sub_category_ids.*' => ['integer'],
        ];
    }

    public function messages(): array
    {
        return [
            'product_status.array' => translate('Send the product statuses as a list'),
            'product_status.*.in' => translate('Choose a valid product status'),
            'filter_product_types.array' => translate('Send the product types as a list'),
            'filter_product_types.*.in' => translate('Choose a valid product type'),
            'filter_brand_ids.array' => translate('Please send the brands as a list.'),
            'filter_brand_ids.*.integer' => translate('Please choose a valid brand.'),
            'filter_category_ids.array' => translate('Please send the categories as a list.'),
            'filter_category_ids.*.integer' => translate('Please choose a valid category.'),
            'filter_sub_category_ids.array' => translate('Please send the sub categories as a list.'),
            'filter_sub_category_ids.*.integer' => translate('Please choose a valid sub category.'),
            'filter_sub_sub_category_ids.array' => translate('Please send the sub sub categories as a list.'),
            'filter_sub_sub_category_ids.*.integer' => translate('Please choose a valid sub sub category.'),
        ];
    }

    /** @return array<int, string> */
    public function selectedValues(string $field): array
    {
        $values = array_map('strval', (array) ($this->validated($field) ?? []));

        return array_values(array_unique(array_filter($values, static fn (string $value): bool => $value !== '')));
    }

    /** @return array<int, int> */
    public function selectedIds(string $field): array
    {
        return array_values(array_unique(array_map('intval', (array) ($this->validated($field) ?? []))));
    }
}
