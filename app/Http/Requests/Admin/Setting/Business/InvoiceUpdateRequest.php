<?php

namespace App\Http\Requests\Admin\Setting\Business;

use App\Http\Requests\BaseRequest;
use App\Services\New\BusinessSettingService;

class InvoiceUpdateRequest extends BaseRequest
{
    private const SETTINGS_KEY = 'invoice_settings';

    private const LOGO_TYPE_CUSTOM = 'custom';

    public function __construct(private readonly BusinessSettingService $businessSettingService) {}

    public function rules(): array
    {
        return [
            'terms_and_condition' => 'required|string',
            'image' => [$this->isNewLogoRequired() ? 'required' : 'nullable', 'mimes:jpg,jpeg,png,gif', 'max:1024'],
        ];
    }

    public function messages(): array
    {
        return [
            'terms_and_condition.required' => translate(':field is required', ['field' => translate('Terms & Conditions')]),
            'terms_and_condition.string' => translate('Please enter valid terms and conditions'),
            'image.required' => translate('Please upload a logo to show on the invoice'),
            'image.mimes' => translate('The image must be a file of type jpg jpeg png gif'),
            'image.max' => translate('The image may not be greater than 1 MB'),
        ];
    }

    private function isNewLogoRequired(): bool
    {
        if (! $this->boolean('invoice_logo_status') || $this->input('invoice_logo_type') !== self::LOGO_TYPE_CUSTOM) {
            return false;
        }

        $storedSettings = json_decode((string) $this->businessSettingService->findBy(params: ['type' => self::SETTINGS_KEY])?->value, true) ?: [];
        $storedImage = $storedSettings['image'] ?? null;
        $storedImageName = is_array($storedImage) ? ($storedImage['image_name'] ?? '') : (string) $storedImage;
        $storedImageDisk = is_array($storedImage) ? ($storedImage['storage'] ?? 'public') : 'public';

        return $storedImageName === '' || storageLink(rtrim(BusinessSettingService::COMPANY_IMAGE_DIRECTORY, '/'), $storedImageName, $storedImageDisk)['path'] === null;
    }
}
