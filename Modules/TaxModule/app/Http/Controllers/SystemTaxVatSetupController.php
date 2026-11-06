<?php

namespace Modules\TaxModule\app\Http\Controllers;

use App\Enums\BusinessModule;
use Devrabiul\ToastMagic\Facades\ToastMagic;
use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\TaxModule\app\Http\Requests\SystemTaxSetupUpdateRequest;
use Modules\TaxModule\app\Services\New\SystemTaxSetupService;
use Modules\TaxModule\app\Services\New\TaxService;
use Modules\TaxModule\app\Traits\BusinessModuleTabsTrait;
use Modules\TaxModule\app\Traits\VatTaxConfiguration;

class SystemTaxVatSetupController extends Controller
{
    use BusinessModuleTabsTrait;
    use VatTaxConfiguration;

    private const DEFAULT_TAX_TYPES = ['order_wise', 'product_wise', 'category_wise'];

    private const SETUP_ROUTE_NAMES = [
        BusinessModule::RETAIL => 'admin.settings.vat-tax.system-vat-tax-retail',
        BusinessModule::WHOLESALE => 'admin.settings.vat-tax.system-vat-tax-wholesale',
        BusinessModule::AUCTION => 'admin.settings.vat-tax.system-vat-tax-auction',
    ];

    public function __construct(
        private readonly TaxService $taxService,
        private readonly SystemTaxSetupService $systemTaxSetupService,
    ) {}

    public function retail(Request $request): Renderable
    {
        return $this->renderSetup(
            businessModule: BusinessModule::RETAIL,
            countryCode: $request['country_code'],
        );
    }

    public function wholesale(Request $request): Renderable
    {
        abort_if(! $this->isBusinessModuleAvailable(BusinessModule::WHOLESALE), 404);

        return $this->renderSetup(
            businessModule: BusinessModule::WHOLESALE,
            countryCode: $request['country_code'],
        );
    }

    public function auction(Request $request): Renderable
    {
        abort_if(! $this->isBusinessModuleAvailable(BusinessModule::AUCTION), 404);

        return $this->renderSetup(
            businessModule: BusinessModule::AUCTION,
            countryCode: $request['country_code'],
        );
    }

    public function systemTaxVatStore(SystemTaxSetupUpdateRequest $request): RedirectResponse
    {
        $systemTaxVat = $this->systemTaxSetupService->update(id: $request['id'], data: $request->all());

        if ($systemTaxVat === null) {
            ToastMagic::error(translate('Tax setup not found'));

            return back();
        }

        if (! $systemTaxVat->is_included) {
            $this->systemTaxSetupService->syncAdditionalSetups(
                id: $systemTaxVat->id,
                names: $this->getProjectWiseSystemData($this->additionalTaxKey($systemTaxVat->business_module)) ?? [],
                selectedTaxIds: $request['additional'] ?? [],
                activeFlags: $request['additional_status'] ?? [],
            );
        }

        ToastMagic::success(translate(':item updated successfully', ['item' => translate('Tax Settings')]));

        return redirect()->route(
            self::SETUP_ROUTE_NAMES[$systemTaxVat->business_module] ?? self::SETUP_ROUTE_NAMES[BusinessModule::RETAIL],
        );
    }

    public function vendorStatus(Request $request): JsonResponse|RedirectResponse
    {
        $businessModule = $request['business_module'] ?? BusinessModule::RETAIL;

        $systemTaxVat = $request['id'] == null
            ? $this->systemTaxSetupService->findDefaultForBusinessModule(businessModule: $businessModule, countryCode: $request['country_code'])
            : $this->systemTaxSetupService->find(id: $request['id']);

        $status = $systemTaxVat === null || ! (bool) $systemTaxVat->is_active;

        if ($systemTaxVat === null) {
            $systemTaxVat = $this->systemTaxSetupService->create(data: [
                'business_module' => $businessModule,
                'tax_type' => $request['tax_type'],
                'country_code' => $request['country_code'],
                'is_included' => 1,
                'is_active' => 1,
            ]);
        } else {
            $this->systemTaxSetupService->updateStatus(id: $systemTaxVat->id, status: $status);
        }

        if ($request->ajax()) {
            return response()->json([
                'id' => $systemTaxVat->id,
                'status' => $status,
                'message' => translate('Vendor tax status updated'),
            ]);
        }

        ToastMagic::success(translate('Vendor tax status updated'));

        return back();
    }

    private function renderSetup(string $businessModule, ?string $countryCode): Renderable
    {
        return view('taxmodule::6valley.tax.system_tax_setup', [
            'businessModule' => $businessModule,
            'setupTabs' => $this->businessModuleTabs(
                activeBusinessModule: $businessModule,
                routeNames: self::SETUP_ROUTE_NAMES,
            ),
            'taxVats' => $this->taxService->getList(
                dataLimit: 'all',
                filters: ['is_active' => 1, 'business_module' => $businessModule] + $this->getCountryFilters($countryCode),
                sortBy: TaxService::SORT_LATEST,
            ),
            'systemTaxVat' => $this->systemTaxSetupService->findOrCreateByBusinessModule(businessModule: $businessModule),
            'taxTypes' => $this->getProjectWiseSystemData($this->taxCalculateOnKey($businessModule)) ?: self::DEFAULT_TAX_TYPES,
            'additionalTaxItems' => $this->getProjectWiseSystemData($this->additionalTaxKey($businessModule)) ?: [],
            'categoryListUrl' => $this->categoryListUrl($businessModule),
            'productListUrl' => $this->productListUrl($businessModule),
            'productListLabel' => match ($businessModule) {
                BusinessModule::WHOLESALE => translate('Product list.'),
                BusinessModule::AUCTION => translate('In House Auction list.'),
                default => translate('In House Product list.'),
            },
            'country_code' => null,
        ]);
    }

    private function categoryListUrl(string $businessModule): string
    {
        return match ($businessModule) {
            BusinessModule::WHOLESALE => route('admin.wholesale.category.view'),
            BusinessModule::AUCTION => route('admin.auction.category.view'),
            default => route('admin.retail.category.view'),
        };
    }

    private function productListUrl(string $businessModule): string
    {
        return match ($businessModule) {
            BusinessModule::WHOLESALE => route('admin.wholesale.products.list'),
            BusinessModule::AUCTION => route('admin.auction.products.list', ['type' => 'inhouse', 'auction_status' => 'all']),
            default => route('admin.retail.products.list', ['in-house']),
        };
    }

    private function taxCalculateOnKey(string $businessModule): string
    {
        return $businessModule === BusinessModule::RETAIL
            ? 'tax_calculate_on'
            : 'tax_calculate_on_'.$businessModule;
    }

    private function additionalTaxKey(string $businessModule): string
    {
        return $businessModule === BusinessModule::RETAIL
            ? 'additional_tax'
            : 'additional_tax_'.$businessModule;
    }
}
