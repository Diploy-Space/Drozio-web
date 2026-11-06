<?php

namespace App\Http\Controllers\RestAPI\v6\Customer;

use App\Enums\AuthErrorCode;
use App\Http\Controllers\RestAPI\v6\BaseApiController;
use App\Http\Requests\Customer\Address\AddRequest;
use App\Http\Requests\Customer\Address\UpdateRequest;
use App\Http\Resources\V6\Customer\ShippingAddressResource;
use App\Services\New\BusinessSettingService;
use App\Services\New\DeliveryRestriction\CountryCodeService;
use App\Services\New\DeliveryRestriction\ZipCodeService;
use App\Services\New\GuestUserService;
use App\Services\New\ShippingAddressService;
use App\Traits\CustomerOwnerTrait;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\ApiPlayground\app\Attributes\ApiDoc;

class AddressController extends BaseApiController
{
    use CustomerOwnerTrait;

    public function __construct(
        private readonly ShippingAddressService $shippingAddressService,
        private readonly GuestUserService $guestUserService,
        private readonly CountryCodeService $countryCodeService,
        private readonly ZipCodeService $zipCodeService,
        private readonly BusinessSettingService $businessSettingService,
    ) {}

    #[ApiDoc(
        summary: 'Shipping addresses of the customer or guest',
        description: 'Send a bearer token, or a guest_id for an unauthenticated shopper.',
        tag: 'Address',
        query: ['guest_id' => 'nullable|integer', 'limit' => 'nullable|string', 'offset' => 'nullable|integer', 'search' => 'nullable|string'],
        status: [401],
        auth: false,
        guard: 'api',
    )]
    public function getList(Request $request): JsonResponse
    {
        $owner = $this->resolveCustomerOwner($request);

        if ($owner === null) {
            return $this->customerOwnerNotResolved();
        }

        $args = [
            'search' => $request->query('search'),
        ];

        $limit = $request->query('limit', DEFAULT_DATA_LIMIT);

        $offset = (int) $request->query('offset', 1);

        $addresses = $this->shippingAddressService->getList(
            searchValue: $args['search'],
            dataLimit: $limit,
            page: $offset,
            filters: $this->customerOwnerFilters($owner),
        );

        return $this->responseFormatter(
            config: config('response.data_fetched'),
            content: [
                'data' => ShippingAddressResource::collection($addresses),
                'params' => $this->paramsFormatter($args),
                'pagination' => $this->paginateFormatter($addresses),
            ],
        );
    }

    #[ApiDoc(
        summary: 'Add a shipping address',
        tag: 'Address',
        status: [201, 401, 403, 422],
        auth: false,
        guard: 'api',
    )]
    public function create(AddRequest $request): JsonResponse
    {
        $owner = $this->resolveCustomerOwner($request);

        if ($owner === null) {
            return $this->customerOwnerNotResolved();
        }

        $refusal = $this->deliveryRefusal($request);

        if ($refusal !== null) {
            return $refusal;
        }

        $address = $this->shippingAddressService->create(
            data: $request->validated() + [
                'customer_id' => $owner['id'],
                'is_guest' => (int) $owner['is_guest'],
            ],
        );

        return $this->responseFormatter(
            config: config('response.address_added'),
            content: ['data' => new ShippingAddressResource($address)],
        );
    }

    #[ApiDoc(
        summary: 'Update a shipping address',
        description: 'The address must belong to the caller; the owning customer or guest is never taken from the request body.',
        tag: 'Address',
        status: [401, 403, 404, 422],
        auth: false,
        guard: 'api',
    )]
    public function update(UpdateRequest $request, string|int $id): JsonResponse
    {
        $owner = $this->resolveCustomerOwner($request);

        if ($owner === null) {
            return $this->customerOwnerNotResolved();
        }

        $address = $this->findOwnedAddress(owner: $owner, id: $id);

        if ($address === null) {
            return $this->addressNotFound();
        }

        $refusal = $this->deliveryRefusal($request);

        if ($refusal !== null) {
            return $refusal;
        }

        $updated = $this->shippingAddressService->update(id: $address->id, data: $request->validated());

        return $this->responseFormatter(
            config: config('response.address_updated'),
            content: ['data' => new ShippingAddressResource($updated)],
        );
    }

    #[ApiDoc(
        summary: 'Delete a shipping address',
        tag: 'Address',
        status: [401, 404],
        auth: false,
        guard: 'api',
    )]
    public function delete(Request $request, string|int $id): JsonResponse
    {
        $owner = $this->resolveCustomerOwner($request);

        if ($owner === null) {
            return $this->customerOwnerNotResolved();
        }

        $address = $this->findOwnedAddress(owner: $owner, id: $id);

        if ($address === null) {
            return $this->addressNotFound();
        }

        $this->shippingAddressService->delete(id: $address->id);

        return $this->responseFormatter(config: config('response.address_removed'));
    }

    protected function guestUserService(): GuestUserService
    {
        return $this->guestUserService;
    }

    private function findOwnedAddress(array $owner, string|int $id): mixed
    {
        return $this->shippingAddressService->find(
            id: $id,
            ownerId: $owner['id'],
            isGuest: $owner['is_guest'],
        );
    }

    private function deliveryRefusal(Request $request): ?JsonResponse
    {
        $countryRestricted = (bool) $this->businessSettingService->getValueByType(type: 'delivery_country_restriction', default: 0);

        if ($countryRestricted && ! $this->countryCodeService->isAllowed(countryName: (string) $request['country'])) {
            return $this->authError(
                config: config('response.delivery_country_unavailable'),
                errorCode: AuthErrorCode::DELIVERY_AREA_RESTRICTED,
            );
        }

        $zipRestricted = (bool) $this->businessSettingService->getValueByType(type: 'delivery_zip_code_area_restriction', default: 0);

        if ($zipRestricted && ! $this->zipCodeService->isAllowed(zipCode: (string) $request['zip'])) {
            return $this->authError(
                config: config('response.delivery_zip_unavailable'),
                errorCode: AuthErrorCode::DELIVERY_AREA_RESTRICTED,
            );
        }

        return null;
    }

    private function addressNotFound(): JsonResponse
    {
        return $this->authError(
            config: config('response.address_not_found'),
            errorCode: AuthErrorCode::ACCOUNT_NOT_FOUND,
        );
    }
}
