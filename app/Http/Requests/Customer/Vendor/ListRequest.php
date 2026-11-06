<?php

namespace App\Http\Requests\Customer\Vendor;

use App\Http\Requests\BaseRequest;
use App\Services\New\ShopService;
use Illuminate\Validation\Rule;

class ListRequest extends BaseRequest
{
    private const ORDERS = [
        ShopService::VENDOR_ORDER_NAME_ASC,
        ShopService::VENDOR_ORDER_NAME_DESC,
        ShopService::VENDOR_ORDER_PRODUCTS_DESC,
        ShopService::VENDOR_ORDER_PRODUCTS_ASC,
        ShopService::VENDOR_ORDER_RATING_DESC,
        ShopService::VENDOR_ORDER_RATING_ASC,
    ];

    public function rules(): array
    {
        return [
            'shop_name' => ['nullable', 'string', 'max:255'],
            'order_by' => ['nullable', Rule::in(self::ORDERS)],
            'page' => ['nullable', 'integer', 'min:1'],
            'brand_id' => ['nullable', 'integer'],
            'category_id' => ['nullable', 'integer'],
            'sub_category_id' => ['nullable', 'integer'],
            'sub_sub_category_id' => ['nullable', 'integer'],
            'product_type' => ['nullable', 'string'],
            'publishing_house_id' => ['nullable', 'integer'],
            'author_id' => ['nullable', 'integer'],
        ];
    }

    public function messages(): array
    {
        return [
            'shop_name.string' => translate('Please enter a valid shop name'),
            'shop_name.max' => translate('The shop name may not be greater than 255 characters'),
            'order_by.in' => translate('Please select a valid sorting option'),
            'page.integer' => translate('Please select a valid page'),
            'page.min' => translate('Please select a valid page'),
            'brand_id.integer' => translate('Please select a valid brand'),
            'category_id.integer' => translate('Please select a valid category'),
            'sub_category_id.integer' => translate('Please select a valid sub category'),
            'sub_sub_category_id.integer' => translate('Please select a valid sub sub category'),
            'product_type.string' => translate('Please select a valid product type'),
            'publishing_house_id.integer' => translate('Please select a valid publishing house'),
            'author_id.integer' => translate('Please select a valid author'),
        ];
    }
}
