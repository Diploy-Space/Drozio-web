<?php

namespace App\Http\Requests\Admin\Setting\Delivery;

use App\Http\Requests\BaseRequest;
use App\Services\New\DeliveryCountryCodeService;
use Illuminate\Validation\Rule;

class CountryCodeAddRequest extends BaseRequest
{
    protected $stopOnFirstFailure = true;

    public function __construct(
        private readonly DeliveryCountryCodeService $deliveryCountryCodeService,
    ) {
        parent::__construct();
    }

    public function rules(): array
    {
        return [
            'country_code' => ['required', 'array'],
            'country_code.*' => ['string', Rule::notIn($this->storedCountryCodes())],
        ];
    }

    public function messages(): array
    {
        return [
            'country_code.required' => translate('Select at least one country'),
            'country_code.array' => translate('Select at least one country'),
            'country_code.*.string' => translate('The country field is invalid'),
            'country_code.*.not_in' => translate('This country is already on the delivery list'),
        ];
    }

    /** @return array<int, string> */
    private function storedCountryCodes(): array
    {
        return $this->deliveryCountryCodeService->getList(dataLimit: 'all')->pluck('country_code')->all();
    }
}
