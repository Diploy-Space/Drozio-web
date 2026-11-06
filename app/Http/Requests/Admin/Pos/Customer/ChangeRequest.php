<?php

namespace App\Http\Requests\Admin\Pos\Customer;

use App\Http\Requests\BaseRequest;
use App\Services\New\CustomerService;
use App\Services\New\Pos\PosCartService;
use Illuminate\Validation\Validator;

class ChangeRequest extends BaseRequest
{
    public function __construct(
        private readonly CustomerService $customerService,
    ) {}

    public function rules(): array
    {
        return [
            'user_id' => ['required', 'integer', 'min:0'],
        ];
    }

    public function messages(): array
    {
        return [
            'user_id.required' => translate(':field is required', ['field' => translate('Customer')]),
            'user_id.integer' => translate(':field must be a valid customer', ['field' => translate('Customer')]),
            'user_id.min' => translate(':field must be a valid customer', ['field' => translate('Customer')]),
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if ($validator->errors()->has('user_id')) {
                    return;
                }

                $customerId = (int) $this->input('user_id');
                if ($customerId !== PosCartService::WALK_IN_CUSTOMER_ID && ! $this->customerService->exists(params: ['id' => $customerId])) {
                    $validator->errors()->add('user_id', translate('Selected customer could not be found'));
                }
            },
        ];
    }
}
