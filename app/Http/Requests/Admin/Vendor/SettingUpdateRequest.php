<?php

namespace App\Http\Requests\Admin\Vendor;

use App\Enums\WebConfigKey;
use App\Http\Requests\BaseRequest;
use App\Services\New\BusinessSettingService;

class SettingUpdateRequest extends BaseRequest
{
    protected $stopOnFirstFailure = true;

    private const DEFAULT_DECIMAL_POINTS = 2;

    private ?int $decimalPoints = null;

    public function __construct(private readonly BusinessSettingService $businessSettingService) {}

    public function rules(): array
    {
        return [
            'commission' => ['nullable', 'numeric', 'min:0', 'max:100', 'decimal:0,'.$this->decimalPoints()],
            'gst_status' => ['nullable', 'boolean'],
            'gst' => ['nullable', 'required_if:gst_status,1', 'string', 'max:50'],
            'seller_pos' => ['nullable', 'boolean'],
            'builder_status' => ['nullable', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'commission.numeric' => translate('The commission percentage must be a number.'),
            'commission.min' => translate('The commission percentage cannot be less than 0.'),
            'commission.max' => translate('The commission percentage cannot be more than 100.'),
            'commission.decimal' => translate('The commission percentage cannot have more than :digits digits after the decimal point.', ['digits' => $this->decimalPoints()]),
            'gst_status.boolean' => translate('The GST number toggle is not valid.'),
            'gst.required_if' => translate('Please enter a GST number.'),
            'gst.max' => translate('The GST number must not be longer than 50 characters.'),
            'seller_pos.boolean' => translate('The vendor POS toggle is not valid.'),
            'builder_status.boolean' => translate('The builder storefront toggle is not valid.'),
        ];
    }

    private function decimalPoints(): int
    {
        if ($this->decimalPoints === null) {
            $this->decimalPoints = (int) $this->businessSettingService->getValueByType(
                type: WebConfigKey::DECIMAL_POINT_SETTINGS,
                default: self::DEFAULT_DECIMAL_POINTS,
            );
        }

        return $this->decimalPoints;
    }
}
