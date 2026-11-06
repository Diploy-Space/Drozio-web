<?php

namespace App\Http\Requests\Customer\MaintenanceMode;

use App\Http\Requests\BaseRequest;
use App\Services\New\MaintenanceModeService;
use Illuminate\Validation\Rule;

class ShowRequest extends BaseRequest
{
    public const SYSTEM_VENDOR = 'vendor';

    private const SYSTEM_PARAMETERS = [self::SYSTEM_VENDOR, 'web'];

    public function rules(): array
    {
        return [
            'maintenance_system' => ['nullable', Rule::in(self::SYSTEM_PARAMETERS)],
        ];
    }

    public function messages(): array
    {
        return [
            'maintenance_system.in' => translate('Please select a valid system'),
        ];
    }

    public function system(): string
    {
        return $this['maintenance_system'] === self::SYSTEM_VENDOR
            ? MaintenanceModeService::SYSTEM_VENDOR_PANEL
            : MaintenanceModeService::SYSTEM_USER_WEBSITE;
    }
}
