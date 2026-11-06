<?php

namespace App\Http\Requests\Customer\Review;

use App\Http\Requests\BaseRequest;
use App\Services\New\ShopService;
use Closure;

class ShopListRequest extends BaseRequest
{
    public function __construct(private readonly ShopService $shopService) {}

    public function rules(): array
    {
        return [
            'shop_id' => ['required', 'integer', 'min:0', $this->shopExistsRule()],
            'offset' => ['nullable', 'integer', 'min:0'],
        ];
    }

    public function messages(): array
    {
        return [
            'shop_id.required' => translate('Please select a shop'),
            'shop_id.integer' => translate('Please select a valid shop'),
            'shop_id.min' => translate('Please select a valid shop'),
            'offset.integer' => translate('Please provide a valid page number'),
            'offset.min' => translate('Please provide a valid page number'),
        ];
    }

    public function pageNumber(): int
    {
        return (int) $this['offset'] + 1;
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
