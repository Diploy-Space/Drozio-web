<?php

namespace App\Http\Requests\Admin\Promotion\Banner;

use App\Http\Requests\BaseRequest;
use App\Services\New\BannerService;
use Illuminate\Validation\Rule;

class BannerStoreRequest extends BaseRequest
{
    protected $stopOnFirstFailure = true;

    public function __construct(
        private readonly BannerService $bannerService,
    ) {}

    public function rules(): array
    {
        return [
            'banner_type' => ['required', Rule::in(array_keys($this->bannerService->getBannerTypes()))],
            'resource_type' => ['required', Rule::in(array_keys($this->bannerService->getResourceTypes()))],
            'url' => 'required_if:resource_type,'.BannerService::RESOURCE_CUSTOM.'|nullable|url',
            'image' => getRulesStringForImageValidation(
                rules: ['required', 'image'],
                skipMimes: ['.svg'],
                maxSize: getFileUploadMaxSize(unit: 'kb'),
                isDisallowed: true
            ),
        ];
    }

    public function messages(): array
    {
        return [
            'banner_type.required' => translate('Please select a banner type'),
            'banner_type.in' => translate('The selected banner type is not available for this theme'),
            'resource_type.required' => translate('Please select a resource type'),
            'resource_type.in' => translate('The selected resource type is not available'),
            'url.required_if' => translate('The url field is required'),
            'url.url' => translate('Please enter a valid banner url'),
            'image.required' => translate('The image is required'),
            'image.image' => translate('The banner image must be an image file'),
            'image.max' => translate('The banner image must not be larger than :size MB', ['size' => getFileUploadMaxSize()]),
            'image.mimes' => translate('The banner image must be one of these formats: :formats', ['formats' => getFileUploadFormats(skip: '.svg', asMessage: 'true')]),
        ];
    }
}
