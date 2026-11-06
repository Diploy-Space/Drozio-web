<?php

namespace App\Http\Requests\Admin\Shipping;

use App\Http\Requests\BaseRequest;
use App\Services\New\ShippingMethodService;
use Illuminate\Validation\Validator;

class MethodUpdateRequest extends BaseRequest
{
    public function __construct(
        private readonly ShippingMethodService $shippingMethodService,
    ) {
        parent::__construct();
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:100'],
            'duration' => ['required', 'string', 'max:20'],
            'cost' => ['numeric'],
        ];
    }

    public function messages(): array
    {
        return [
            'title.required' => translate('The title field is required'),
            'title.string' => translate('The title must be text'),
            'title.max' => translate('Title must not be longer than 100 characters'),
            'duration.required' => translate('The shipping duration field is required'),
            'duration.max' => translate('The shipping duration is too long'),
            'cost.numeric' => translate('The cost must be a number'),
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator) {
                $exists = $this->shippingMethodService->hasDuplicateTitle(
                    title: (string) $this->input('title'),
                    creatorType: ShippingMethodService::CREATOR_ADMIN,
                    excludeId: $this->route('id'),
                );

                if ($exists) {
                    $validator->errors()->add(
                        'title',
                        translate('Title already exists')
                    );
                }
            },
        ];
    }
}
