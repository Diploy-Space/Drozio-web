<?php

namespace Modules\Courier\app\Http\Requests\Admin;

use Closure;
use Modules\Courier\app\Services\New\CourierService;

class ReviseCourierDetailsRequest extends SendToCourierRequest
{
    public function rules(): array
    {
        return ['provider' => ['required', 'string', $this->providerMustHoldTheShipment()]] + parent::rules();
    }

    private function providerMustHoldTheShipment(): Closure
    {
        return function (string $attribute, mixed $value, Closure $fail): void {
            $shipment = app(CourierService::class)->activeShipmentForOwner((string) $this->input('host_order_reference'));

            if ($shipment === null) {
                $fail(translate('This order is not assigned to a delivery partner'));

                return;
            }

            if ($shipment->provider !== (string) $value) {
                $fail(translate('This order is assigned to a different delivery partner'));
            }
        };
    }
}
