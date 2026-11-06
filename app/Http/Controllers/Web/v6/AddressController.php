<?php

namespace App\Http\Controllers\Web\v6;

use App\Http\Controllers\Controller;
use App\Http\Requests\Customer\Address\AddRequest;
use App\Http\Requests\Customer\Address\DeleteRequest;
use App\Http\Requests\Customer\Address\UpdateRequest;
use App\Services\New\DeliveryCountryCodeService;
use App\Services\New\DeliveryZipCodeService;
use App\Services\New\ShippingAddressService;
use Devrabiul\ToastMagic\Facades\ToastMagic;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\RedirectResponse;

class AddressController extends Controller
{
    private const COUNTRY_RESTRICTION_SETTING = 'delivery_country_restriction';

    private const ZIP_CODE_RESTRICTION_SETTING = 'delivery_zip_code_area_restriction';

    private const DEFAULT_LOCATION_SETTING = 'default_location';

    private const DEFAULT_THEME = 'default';

    public function __construct(
        private readonly ShippingAddressService $shippingAddressService,
        private readonly DeliveryZipCodeService $deliveryZipCodeService,
        private readonly DeliveryCountryCodeService $deliveryCountryCodeService,
    ) {}

    public function create(): View
    {
        $zipRestrictStatus = getWebConfig(name: self::ZIP_CODE_RESTRICTION_SETTING);

        return view(themeViewName(key: 'account_address_add'), [
            'countries' => $this->deliveryCountries(),
            'zip_restrict_status' => $zipRestrictStatus,
            'zip_codes' => $this->deliveryZipCodes(isRestricted: $zipRestrictStatus),
            'default_location' => getWebConfig(name: self::DEFAULT_LOCATION_SETTING),
        ]);
    }

    public function index(): View|RedirectResponse
    {
        $customerId = $this->resolveCustomerId();

        if ($customerId === null) {
            return redirect()->route('home');
        }

        $zipRestrictStatus = getWebConfig(name: self::ZIP_CODE_RESTRICTION_SETTING);
        $countries = $this->deliveryCountries();

        return view(themeViewName(key: 'account_address'), [
            'shippingAddresses' => $this->shippingAddressService->getList(dataLimit: 'all', filters: ['customer_id' => $customerId]),
            'country_restrict_status' => getWebConfig(name: self::COUNTRY_RESTRICTION_SETTING),
            'zip_restrict_status' => $zipRestrictStatus,
            'countries' => $countries,
            'zip_codes' => $this->deliveryZipCodes(isRestricted: $zipRestrictStatus),
            'countriesName' => array_column($countries, 'name'),
            'countriesCode' => array_column($countries, 'code'),
        ]);
    }

    public function store(AddRequest $request): RedirectResponse
    {
        $deliveryRefusal = $this->deliveryRefusalMessage(country: $request['country'], zipCode: $request['zip']);

        if ($deliveryRefusal !== null) {
            ToastMagic::error($deliveryRefusal);

            return back();
        }

        $this->shippingAddressService->createAccountAddress(customerId: $this->resolveCustomerId(), data: $request->all());

        ToastMagic::success(translate(':item added successfully', ['item' => translate('Address')]));

        return theme_root_path() === self::DEFAULT_THEME ? back() : redirect()->route('user-profile');
    }

    public function edit(string $id): View|RedirectResponse
    {
        $customerId = $this->resolveCustomerId();

        $shippingAddress = $customerId === null
            ? null
            : $this->shippingAddressService->find(id: $id, ownerId: (string) $customerId);

        if ($shippingAddress === null) {
            ToastMagic::warning(translate('Access denied'));

            return back();
        }

        $zipRestrictStatus = getWebConfig(name: self::ZIP_CODE_RESTRICTION_SETTING);
        $deliveryCountries = $this->deliveryCountries();

        return view(themeViewName(key: 'account_address_edit'), [
            'shippingAddress' => $shippingAddress,
            'country_restrict_status' => getWebConfig(name: self::COUNTRY_RESTRICTION_SETTING),
            'zip_restrict_status' => $zipRestrictStatus,
            'delivery_countries' => $deliveryCountries,
            'delivery_zipcodes' => $this->deliveryZipCodes(isRestricted: $zipRestrictStatus),
            'countriesName' => array_column($deliveryCountries, 'name'),
            'countriesCode' => array_column($deliveryCountries, 'code'),
        ]);
    }

    public function update(UpdateRequest $request): RedirectResponse
    {
        $deliveryRefusal = $this->deliveryRefusalMessage(country: $request['country'], zipCode: $request['zip']);

        if ($deliveryRefusal !== null) {
            ToastMagic::error($deliveryRefusal);

            return back();
        }

        $customerId = $this->resolveCustomerId();

        if ($customerId === null) {
            ToastMagic::error(translate('Insufficient permission!'));
        } else {
            $this->shippingAddressService->updateAccountAddress(
                id: $request['id'],
                data: $request->all(),
                ownerId: (string) $customerId,
            );

            ToastMagic::success(translate(':item updated successfully', ['item' => translate('Address')]));
        }

        return theme_root_path() === self::DEFAULT_THEME
            ? redirect()->route('account-address')
            : redirect()->route('user-profile');
    }

    public function delete(DeleteRequest $request): RedirectResponse
    {
        $customerId = $this->resolveCustomerId();

        if ($customerId === null) {
            return redirect()->back();
        }

        $shippingAddress = $this->shippingAddressService->find(id: $request['id'], ownerId: (string) $customerId);

        if ($shippingAddress !== null) {
            $this->shippingAddressService->delete(id: $shippingAddress->id);
        }

        ToastMagic::success(translate('Address deleted successfully'));

        return redirect()->back();
    }

    private function deliveryRefusalMessage(?string $country, mixed $zipCode): ?string
    {
        if (getWebConfig(name: self::COUNTRY_RESTRICTION_SETTING) && ! $this->deliveryCountryCodeService->hasCountryName(countryName: $country)) {
            return translate('Delivery unavailable in this country!');
        }

        if (getWebConfig(name: self::ZIP_CODE_RESTRICTION_SETTING) && ! $this->deliveryZipCodeService->hasZipCode(zipCode: $zipCode)) {
            return translate('Delivery unavailable in this zip code area');
        }

        return null;
    }

    /**
     * @return array<int|string, array{code: string, name: string}>
     */
    private function deliveryCountries(): array
    {
        if (! getWebConfig(name: self::COUNTRY_RESTRICTION_SETTING)) {
            return COUNTRIES;
        }

        return $this->deliveryCountryCodeService->getDeliveryCountryArray(
            deliveryCountryCodes: $this->deliveryCountryCodeService->getList(dataLimit: 'all', sortBy: DeliveryCountryCodeService::SORT_OLDEST),
        );
    }

    private function deliveryZipCodes(mixed $isRestricted): Collection|int
    {
        return $isRestricted
            ? $this->deliveryZipCodeService->getList(dataLimit: 'all', sortBy: DeliveryZipCodeService::SORT_OLDEST)
            : 0;
    }

    private function resolveCustomerId(): ?int
    {
        return auth('customer')->check() ? (int) auth('customer')->id() : null;
    }
}
