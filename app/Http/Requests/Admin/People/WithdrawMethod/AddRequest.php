<?php

namespace App\Http\Requests\Admin\People\WithdrawMethod;

use App\Http\Requests\BaseRequest;
use App\Services\New\WithdrawalMethodService;
use Illuminate\Validation\Validator;

class AddRequest extends BaseRequest
{
    protected $stopOnFirstFailure = true;

    public function __construct(
        private readonly WithdrawalMethodService $withdrawalMethodService,
    ) {
        parent::__construct();
    }

    public function rules(): array
    {
        return [
            'method_name' => 'required|string|max:255',
            'field_type' => 'required|array',
            'field_type.*' => 'required|string',
            'field_name' => 'required|array',
            'field_name.*' => 'required|string',
            'placeholder_text' => 'required|array',
            'placeholder_text.*' => 'required',
            'is_default' => 'nullable|in:0,1',
        ];
    }

    public function messages(): array
    {
        return [
            'method_name.required' => translate('Method name is required'),
            'method_name.string' => translate('Method name is not valid'),
            'method_name.max' => translate('Method name must not exceed 255 characters'),
            'field_type.required' => translate('Input field type is required'),
            'field_type.array' => translate('Input field type is not valid'),
            'field_type.*.required' => translate('Input field type is required'),
            'field_type.*.string' => translate('Input field type is not valid'),
            'field_name.required' => translate('Field name is required'),
            'field_name.array' => translate('Field name is not valid'),
            'field_name.*.required' => translate('Field name is required'),
            'field_name.*.string' => translate('Field name is not valid'),
            'placeholder_text.required' => translate('Placeholder text is required'),
            'placeholder_text.array' => translate('Placeholder text is not valid'),
            'placeholder_text.*.required' => translate('Placeholder text is required'),
            'is_default.in' => translate('Set as default method is not valid'),
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if ($this->isMethodNameTaken()) {
                    $validator->errors()->add('method_name', translate('This withdraw method name already exists'));
                }
            },
        ];
    }

    private function isMethodNameTaken(): bool
    {
        return $this->withdrawalMethodService->getList(
            dataLimit: 'all',
            filters: [
                WithdrawalMethodService::FILTER_USER_TYPE => $this->route('userType'),
                'method_name' => (string) $this['method_name'],
            ],
        )->isNotEmpty();
    }
}
