<?php

namespace App\Http\Requests\Deliveryman\Order;

use App\Http\Requests\BaseRequest;

class DeliveryProofAddRequest extends BaseRequest
{
    public function rules(): array
    {
        return [
            'image' => 'nullable|array',
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
            'image.array' => translate('Please upload the delivery photos as a list of images'),
            'image.*.required' => translate('Please upload a delivery photo'),
            'image.*.image' => translate('The delivery photo must be an image'),
            'image.*.mimes' => translate('The image type must be one of :formats', [
                'formats' => str_replace(',', ', ', getFileUploadFormats(skip: ['.svg', '.gif'])),
            ]),
            'image.*.max' => translate('Image size exceeds maximum size').' '.getFileUploadMaxSize().'MB',
        ];
    }
}
