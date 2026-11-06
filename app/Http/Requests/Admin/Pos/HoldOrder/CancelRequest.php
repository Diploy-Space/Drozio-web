<?php

namespace App\Http\Requests\Admin\Pos\HoldOrder;

use App\Http\Requests\BaseRequest;
use App\Services\New\Pos\PosHoldOrderService;
use Illuminate\Validation\Rule;

class CancelRequest extends BaseRequest
{
    public function __construct(
        private readonly PosHoldOrderService $posHoldOrderService,
    ) {}

    public function rules(): array
    {
        return [
            'cart_id' => ['required', 'string', Rule::in($this->posHoldOrderService->getHeldCartIds())],
        ];
    }

    public function messages(): array
    {
        return [
            'cart_id.required' => translate(':field is required', ['field' => translate('Held order')]),
            'cart_id.string' => translate(':field must be text', ['field' => translate('Held order')]),
            'cart_id.in' => translate('This order is not currently on hold'),
        ];
    }
}
