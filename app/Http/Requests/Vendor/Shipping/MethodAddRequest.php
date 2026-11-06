<?php

namespace App\Http\Requests\Vendor\Shipping;

use App\Http\Requests\BaseRequest;
use App\Services\New\ShippingMethodService;
use Illuminate\Validation\Validator;

class MethodAddRequest extends BaseRequest
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
            'cost' => ['required', 'numeric', 'min:0'],
        ];
    }

    public function messages(): array
    {
        return [
            'title.required' => translate('The title field is required'),
            'title.string' => translate('The title must be text'),
            'title.max' => translate('Title must not be longer than 100 characters'),
            'duration.required' => translate('The shipping duration field is required'),
            'duration.string' => translate('The shipping duration must be text'),
            'duration.max' => translate('The shipping duration is too long'),
            'cost.required' => translate('The cost field is required'),
            'cost.numeric' => translate('The cost must be a number'),
            'cost.min' => translate('The cost cannot be negative'),
        ];
    }

    public function after(): array
    {
        if ($this->isApiRequest()) {
            return [];
        }

        return [
            function (Validator $validator) {
                $exists = $this->shippingMethodService->hasDuplicateTitle(
                    title: (string) $this['title'],
                    creatorType: ShippingMethodService::CREATOR_SELLER,
                    creatorId: auth('seller')->id(),
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
