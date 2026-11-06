<?php

namespace Modules\AI\app\Http\Requests\ShoppingAssistant;

use App\Http\Requests\BaseRequest;

class UploadImageRequest extends BaseRequest
{
    private const MAX_IMAGE_KILOBYTES = 5120;

    private const KILOBYTES_PER_MEGABYTE = 1024;

    public function rules(): array
    {
        return [
            'image' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:'.self::MAX_IMAGE_KILOBYTES],
        ];
    }

    public function messages(): array
    {
        return [
            'image.required' => translate('The image is required'),
            'image.image' => translate('The image must be an image file'),
            'image.mimes' => translate('Only JPG JPEG PNG or WEBP images are allowed'),
            'image.max' => translate('The image must not be larger than :size MB', [
                'size' => self::MAX_IMAGE_KILOBYTES / self::KILOBYTES_PER_MEGABYTE,
            ]),
        ];
    }
}
