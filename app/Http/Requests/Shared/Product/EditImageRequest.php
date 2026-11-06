<?php

namespace App\Http\Requests\Shared\Product;

use App\Http\Requests\BaseRequest;
use App\Services\New\ProductRetailService;
use Illuminate\Validation\Validator;

class EditImageRequest extends BaseRequest
{
    public function __construct(
        private readonly ProductRetailService $productService
    ) {}

    public function rules(): array
    {
        return [
            'image' => getRulesStringForImageValidation(
                rules: ['nullable', 'image'],
                skipMimes: ['.webp'],
                maxSize: getFileUploadMaxSize(unit: 'kb'),
                isDisallowed: true
            ),
            'removed_images' => ['nullable', 'array'],
            'removed_images.*' => ['string'],
        ];
    }

    public function messages(): array
    {
        return [
            'image.mimes' => translate('The image format is not supported. Allowed formats:').getFileUploadFormats(skip: '.webp', asMessage: 'true'),
            'removed_images.array' => translate('Please choose the images to remove from the product images.'),
            'removed_images.*.string' => translate('Please choose the images to remove from the product images.'),
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator) {
                $product = $this->productService->findUnscoped(id: $this->route('id'));

                if ($product === null) {
                    return;
                }

                $isRemovingEveryImage = $this->productService->isRemovingEveryImage(
                    product: $product,
                    removedImageNames: $this->removedImageNames(),
                    uploadedImageCount: count((array) $this->file('images')),
                );

                if ($isRemovingEveryImage) {
                    $validator->errors()->add('removed_images', translate('You cannot delete all images'));
                }
            },
        ];
    }

    public function removedImageNames(): array
    {
        return array_values(array_filter((array) $this->input('removed_images', []), 'is_string'));
    }
}
