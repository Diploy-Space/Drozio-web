<?php

namespace App\Http\Requests\Vendor\PaymentInfo;

use App\Http\Requests\BaseRequest;
use App\Services\New\VendorWithdrawMethodInfoService;
use App\Services\New\WithdrawalMethodService;
use Illuminate\Validation\Validator;

class UpdateRequest extends BaseRequest
{
    public function __construct(
        private readonly WithdrawalMethodService $withdrawalMethodService,
        private readonly VendorWithdrawMethodInfoService $vendorWithdrawMethodInfoService,
    ) {
        parent::__construct();
    }

    public function rules(): array
    {
        return [
            'method_name' => [
                'required',
                'string',
                'max:255',
            ],
            'withdraw_method_id' => 'required|exists:withdrawal_methods,id',
        ];
    }

    public function messages(): array
    {
        return [
            'method_name.required' => translate('The method name field is required'),
            'method_name.string' => translate('Please enter a valid method name'),
            'method_name.max' => translate('The method name must not exceed 255 characters'),
            'withdraw_method_id.required' => translate('The payment method field is required'),
            'withdraw_method_id.exists' => translate('The selected payment method is invalid'),
            'id.exists' => translate('This payment information is not valid'),
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator) {
                $methodFields = $this->methodFields();

                if ($this->isMethodNameTaken()) {
                    $validator->errors()->add(
                        'method_name', translate('Name has been taken')
                    );
                }

                if ($methodFields === null) {
                    $validator->errors()->add(
                        'withdraw_method_id', translate('The selected withdraw method is invalid')
                    );
                }

                foreach ($methodFields ?? [] as $field) {
                    if (isset($field['is_required']) && $field['is_required'] == 1) {
                        if (! isset($this['method_info'][$field['input_name']]) || empty($this['method_info'][$field['input_name']])) {
                            $validator->errors()->add(
                                'method_info_'.$field['input_name'], translate(':field is required', ['field' => $field['input_name']])
                            );
                        }
                    }
                }
            },
        ];
    }

    private function isMethodNameTaken(): bool
    {
        return $this->vendorWithdrawMethodInfoService->getList(
            dataLimit: 'all',
            filters: ['user_id' => auth('seller')->id(), 'method_name' => $this['method_name']],
        )->where('id', '!=', $this->input('id') ?: null)->isNotEmpty();
    }

    /** @return array<int, array<string, mixed>>|null */
    private function methodFields(): ?array
    {
        $method = $this->withdrawalMethodService->find(id: $this['withdraw_method_id']);

        return $method === null ? null : (array) $method->method_fields;
    }
}
