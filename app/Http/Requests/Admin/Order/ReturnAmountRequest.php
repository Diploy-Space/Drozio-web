<?php

namespace App\Http\Requests\Admin\Order;

use App\Http\Requests\BaseRequest;
use App\Services\New\OrderEditHistoryService;
use App\Services\New\OrderService;
use Closure;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\Rule;

class ReturnAmountRequest extends BaseRequest
{
    private ?Model $order = null;

    private bool $isOrderResolved = false;

    public function __construct(
        private readonly OrderService $orderService,
    ) {}

    public function rules(): array
    {
        return [
            'order_id' => ['required', function (string $attribute, mixed $value, Closure $fail): void {
                if ($this->order() === null) {
                    $fail(translate('The selected order is invalid'));
                }
            }],
            'amount' => ['bail', 'required', 'numeric', 'min:1', function (string $attribute, mixed $value, Closure $fail): void {
                $order = $this->order();

                if ($order !== null && ! $this->orderService->matchesEditReturnAmount(order: $order, amount: $value)) {
                    $fail(translate('The entered return amount must match the order return amount'));
                }
            }],
            'order_return_payment_method' => [
                'bail',
                'required',
                'string',
                Rule::in([OrderEditHistoryService::RETURN_PAYMENT_METHOD_WALLET, OrderEditHistoryService::RETURN_PAYMENT_METHOD_MANUALLY]),
                function (string $attribute, mixed $value, Closure $fail): void {
                    $order = $this->order();

                    if ($value === OrderEditHistoryService::RETURN_PAYMENT_METHOD_WALLET && $order !== null && ($order['is_guest'] || getWebConfig(name: 'wallet_status') != 1)) {
                        $fail(translate('Returning the amount to the wallet is not currently possible'));
                    }
                },
            ],
            'order_return_payment_note' => 'required|string|max:255',
        ];
    }

    public function messages(): array
    {
        return [
            'order_id.required' => translate('Order is required'),
            'amount.required' => translate(':field is required', ['field' => translate('Amount')]),
            'amount.numeric' => translate('Amount must be a number'),
            'amount.min' => translate('Amount must be at least 1'),
            'order_return_payment_method.required' => translate('Return payment method is required'),
            'order_return_payment_method.string' => translate('The selected return payment method is invalid'),
            'order_return_payment_method.in' => translate('The selected return payment method is invalid'),
            'order_return_payment_note.required' => translate('Please type your note'),
            'order_return_payment_note.string' => translate('Please type your note'),
            'order_return_payment_note.max' => translate('Note may not be greater than 255 characters'),
        ];
    }

    private function order(): ?Model
    {
        if (! $this->isOrderResolved) {
            $orderId = $this->input('order_id');
            $this->order = is_scalar($orderId) ? $this->orderService->findBy(params: ['id' => $orderId], relations: []) : null;
            $this->isOrderResolved = true;
        }

        return $this->order;
    }
}
