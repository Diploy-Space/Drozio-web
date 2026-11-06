<?php

namespace App\Http\Requests\Deliveryman\Order;

use App\Http\Requests\BaseRequest;

class DeliveryVerificationRequest extends BaseRequest
{
    protected bool $useLegacyRequestErrorFormat = true;

    public function rules(): array
    {
        return [
            'order_id' => 'required',
            'image.*' => getRulesStringForImageValidation(
                rules: ['required'],
                skipMimes: ['.svg', '.gif'],
                maxSize: getFileUploadMaxSize(unit: 'kb'),
            ),
        ];
    }

    public function messages(): array
    {
        return [
            'order_id.required' => translate('Order is required'),
            'image.*.required' => translate('The image field is required'),
            'image.*.mimes' => translate('The image type must be').getFileUploadFormats(skip: '.svg,.gif', asMessage: 'true'),
            'image.*.max' => translate('Maximum image size cannot exceed').' '.getFileUploadMaxSize().'MB',
        ];
    }
}
