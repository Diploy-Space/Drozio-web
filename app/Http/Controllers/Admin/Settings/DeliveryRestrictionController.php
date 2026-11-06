<?php

namespace App\Http\Controllers\Admin\Settings;

use App\Enums\GlobalConstant;
use App\Enums\WebConfigKey;
use App\Http\Controllers\BaseController;
use App\Http\Requests\Admin\Setting\Delivery\CountryCodeAddRequest;
use App\Http\Requests\Admin\Setting\Delivery\ZipCodeAddRequest;
use App\Services\New\BusinessSettingService;
use App\Services\New\DeliveryCountryCodeService;
use App\Services\New\DeliveryZipCodeService;
use Devrabiul\ToastMagic\Facades\ToastMagic;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DeliveryRestrictionController extends BaseController
{
    private const COUNTRY_RESTRICTION = 'delivery_country_restriction';

    private const ZIP_CODE_RESTRICTION = 'delivery_zip_code_area_restriction';

    private const COUNTRY_SEARCH = 'search_country';

    private const ZIP_CODE_SEARCH = 'search_zip_code';

    private const COUNTRY_PAGE = 'country_page';

    private const ZIP_CODE_PAGE = 'zip_code_page';

    /** @var array<string, string>|null */
    private ?array $countryNames = null;

    public function __construct(
        private readonly BusinessSettingService $businessSettingService,
        private readonly DeliveryCountryCodeService $deliveryCountryCodeService,
        private readonly DeliveryZipCodeService $deliveryZipCodeService,
    ) {}

    public function index(?Request $request, ?string $type = null): View
    {
        $dataLimit = $this->businessSettingService->findBy(params: ['type' => WebConfigKey::PAGINATION_LIMIT])?->value;

        $countrySearch = $request[self::COUNTRY_SEARCH];

        $storedCountries = $this->deliveryCountryCodeService->getList(
            dataLimit: $dataLimit,
            page: $this->currentPage(request: $request, pageName: self::COUNTRY_PAGE),
            filters: $countrySearch ? ['country_code' => $this->matchingCountryCodes($countrySearch)] : [],
            sortBy: DeliveryCountryCodeService::SORT_NEWEST,
        )->setPageName(self::COUNTRY_PAGE)
            ->through(fn ($storedCountry) => $this->withCountryName($storedCountry));

        $storedZipCodes = $this->deliveryZipCodeService->getList(
            searchValue: $request[self::ZIP_CODE_SEARCH],
            dataLimit: $dataLimit,
            page: $this->currentPage(request: $request, pageName: self::ZIP_CODE_PAGE),
            sortBy: DeliveryZipCodeService::SORT_NEWEST,
        )->setPageName(self::ZIP_CODE_PAGE);

        return view('admin-views.business-settings.delivery-restriction', [
            'countryOptions' => $this->countryOptions(),
            'storedCountries' => $storedCountries,
            'storedZipCodes' => $storedZipCodes,
            ...$this->getRestrictionSetup(),
        ]);
    }

    public function add(CountryCodeAddRequest $request): RedirectResponse
    {
        foreach ($request['country_code'] as $countryCode) {
            $this->deliveryCountryCodeService->create(data: ['country_code' => $countryCode]);
        }

        ToastMagic::success(translate(':item added successfully', ['item' => translate('Delivery Country')]));

        return back();
    }

    public function delete(Request $request): RedirectResponse
    {
        $this->deliveryCountryCodeService->delete(id: $request['id'])
            ? ToastMagic::success(translate('Delivery country deleted successfully'))
            : ToastMagic::error(translate('Not found'));

        return back();
    }

    public function addZipCode(ZipCodeAddRequest $request): RedirectResponse
    {
        $missingZipCodes = $this->missingZipCodes($request->zipCodes());

        DB::transaction(function () use ($missingZipCodes): void {
            foreach ($missingZipCodes as $zipCode) {
                $this->deliveryZipCodeService->create(data: ['zipcode' => $zipCode]);
            }
        });

        $missingZipCodes
            ? ToastMagic::success(translate('Delivery zip code added successfully'))
            : ToastMagic::warning(translate('Delivery zip code already exists'));

        return back();
    }

    public function deleteZipCode(Request $request): RedirectResponse
    {
        $this->deliveryZipCodeService->delete(id: $request['id'])
            ? ToastMagic::success(translate('Delivery zip code deleted successfully'))
            : ToastMagic::error(translate('Not found'));

        return back();
    }

    public function countryRestrictionStatusChange(Request $request): JsonResponse|RedirectResponse
    {
        return $this->updateRestrictionStatus(
            request: $request,
            setting: self::COUNTRY_RESTRICTION,
            message: translate(':item changed successfully', ['item' => translate('Delivery country restriction status')]),
        );
    }

    public function zipcodeRestrictionStatusChange(Request $request): JsonResponse|RedirectResponse
    {
        return $this->updateRestrictionStatus(
            request: $request,
            setting: self::ZIP_CODE_RESTRICTION,
            message: translate(':item changed successfully', ['item' => translate('Delivery zip code restriction status')]),
        );
    }

    /**
     * @param  array<int, string>  $zipCodes
     * @return array<int, string>
     */
    private function missingZipCodes(array $zipCodes): array
    {
        $storedZipCodes = $this->deliveryZipCodeService->getList(dataLimit: 'all')->pluck('zipcode')->all();

        return array_values(array_diff($zipCodes, $storedZipCodes));
    }

    /** @return array<string, string> */
    private function countryOptions(): array
    {
        return array_diff_key($this->countryNames(), array_flip($this->storedCountryCodes()));
    }

    /** @return array<int, string> */
    private function matchingCountryCodes(string $searchValue): array
    {
        $codes = [];

        foreach ($this->countryNames() as $code => $name) {
            if (stripos($name, $searchValue) !== false || stripos($code, $searchValue) !== false) {
                $codes[] = $code;
            }
        }

        return $codes;
    }

    private function withCountryName(mixed $storedCountry): mixed
    {
        $storedCountry->country_name = $this->countryNames()[$storedCountry->country_code] ?? $storedCountry->country_code;

        return $storedCountry;
    }

    /** @return array<int, string> */
    private function storedCountryCodes(): array
    {
        return $this->deliveryCountryCodeService->getList(dataLimit: 'all')->pluck('country_code')->all();
    }

    /** @return array<string, string> */
    private function countryNames(): array
    {
        return $this->countryNames ??= array_column(GlobalConstant::COUNTRIES, 'name', 'code');
    }

    private function currentPage(Request $request, string $pageName): int
    {
        $page = (int) $request->get($pageName, 1);

        return $page < 1 ? 1 : $page;
    }

    private function updateRestrictionStatus(Request $request, string $setting, string $message): JsonResponse|RedirectResponse
    {
        $this->businessSettingService->updateOrInsert(
            params: ['type' => $setting],
            data: ['value' => $request->get('status', 0) ? 1 : 0],
        );

        return $request->ajax()
            ? response()->json(['message' => $message, 'status' => true])
            : back();
    }

    /** @return array<string, bool> */
    private function getRestrictionSetup(): array
    {
        $settings = $this->businessSettingService
            ->getList(dataLimit: 'all', filters: ['type' => [self::COUNTRY_RESTRICTION, self::ZIP_CODE_RESTRICTION]])
            ->pluck('value', 'type');

        return [
            'countryRestrictionStatus' => (bool) $settings->get(self::COUNTRY_RESTRICTION),
            'zipCodeRestrictionStatus' => (bool) $settings->get(self::ZIP_CODE_RESTRICTION),
        ];
    }
}
