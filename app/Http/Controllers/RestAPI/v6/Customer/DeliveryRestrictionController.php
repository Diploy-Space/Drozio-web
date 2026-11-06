<?php

namespace App\Http\Controllers\RestAPI\v6\Customer;

use App\Http\Controllers\RestAPI\v6\BaseApiController;
use App\Services\New\BusinessSettingService;
use App\Services\New\DeliveryRestriction\CountryCodeService;
use App\Services\New\DeliveryRestriction\ZipCodeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\ApiPlayground\app\Attributes\ApiDoc;

class DeliveryRestrictionController extends BaseApiController
{
    public function __construct(
        private readonly CountryCodeService $countryCodeService,
        private readonly ZipCodeService $zipCodeService,
        private readonly BusinessSettingService $businessSettingService,
    ) {}

    #[ApiDoc(
        summary: 'Countries the store delivers to',
        description: 'When restriction is switched off the list is empty and every country is accepted.',
        tag: 'Delivery Restriction',
        query: ['search' => 'nullable|string'],
        auth: false,
    )]
    public function getCountries(Request $request): JsonResponse
    {
        $isRestricted = (bool) $this->businessSettingService->getValueByType(type: 'delivery_country_restriction', default: 0);

        $countries = $isRestricted
            ? $this->countryCodeService->getList(searchValue: $request->query('search'))
            : collect();

        return $this->responseFormatter(
            config: config('response.data_fetched'),
            content: [
                'data' => $countries->all(),
                'is_restricted' => $isRestricted,
                'total_size' => $countries->count(),
                'params' => $this->paramsFormatter(['search' => $request->query('search')]),
            ],
        );
    }

    #[ApiDoc(
        summary: 'Postcodes the store delivers to',
        description: 'When restriction is switched off the list is empty and every postcode is accepted.',
        tag: 'Delivery Restriction',
        query: ['search' => 'nullable|string'],
        auth: false,
    )]
    public function getZipCodes(Request $request): JsonResponse
    {
        $isRestricted = (bool) $this->businessSettingService->getValueByType(type: 'delivery_zip_code_area_restriction', default: 0);

        $zipCodes = $isRestricted
            ? $this->zipCodeService->getList(searchValue: $request->query('search'))
            : collect();

        return $this->responseFormatter(
            config: config('response.data_fetched'),
            content: [
                'data' => $zipCodes->map(static fn (string $zipCode): array => ['zip_code' => $zipCode])->all(),
                'is_restricted' => $isRestricted,
                'total_size' => $zipCodes->count(),
                'params' => $this->paramsFormatter(['search' => $request->query('search')]),
            ],
        );
    }
}
