<?php

namespace App\Http\Requests\Customer\Review;

use App\Http\Requests\BaseRequest;
use App\Services\New\ReviewService;
use Closure;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;

class AddRequest extends BaseRequest
{
    public function __construct(private readonly ReviewService $reviewService) {}

    public function rules(): array
    {
        $rules = [
            'rating' => 'required|integer|between:1,5',
            'comment' => 'required',
        ];

        if ($this->isApiRequest()) {
            $rules['product_id'] = 'required';
            $rules['order_id'] = 'required';
            $rules['fileUpload'] = ['nullable', $this->allowedExtensionRule()];
            $rules['fileUpload.*'] = ['nullable', 'image'];
        }

        return $rules;
    }

    public function messages(): array
    {
        return [
            'rating.required' => translate('Please rate the quality').'!',
            'rating.integer' => translate('Please rate the quality between 1 and 5 stars'),
            'rating.between' => translate('Please rate the quality between 1 and 5 stars'),
            'comment.required' => translate('The comment is required').'!',
            'product_id.required' => translate('Please select a product'),
            'order_id.required' => translate('Please select an order'),
            'fileUpload.*.image' => translate('Please upload a valid image'),
        ];
    }

    private function allowedExtensionRule(): Closure
    {
        return function (string $attribute, mixed $value, Closure $fail): void {
            $files = array_filter(Arr::wrap($value), static fn ($file): bool => $file instanceof UploadedFile);
            $disallowedExtension = $this->reviewService->hasDisallowedExtension(files: $files);

            if ($disallowedExtension !== null) {
                $fail(translate('Invalid file format given').' '.$disallowedExtension);
            }
        };
    }
}
