<?php

namespace App\Http\Requests\Shared\Order;

use App\Http\Requests\BaseRequest;
use App\Services\New\OrderService;
use Closure;

class DueAmountMarkAsPaidRequest extends BaseRequest
{
    public function __construct(
        private readonly OrderService $orderService,
    ) {}

    public function rules(): array
    {
        return [
            'order_id' => ['required', function (string $attribute, mixed $value, Closure $fail): void {
                if (! $this->orderService->exists(params: ['id' => $value])) {
                    $fail(translate('The selected order is invalid'));
                }
            }],
        ];
    }

    public function messages(): array
    {
        return [
            'order_id.required' => translate('Order is required'),
        ];
    }
}
