<?php

namespace Modules\TaxModule\app\Http\Controllers;

use App\Enums\BusinessModule;
use Devrabiul\ToastMagic\Facades\ToastMagic;
use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Maatwebsite\Excel\Facades\Excel;
use Modules\TaxModule\app\Exports\TaxVatExport;
use Modules\TaxModule\app\Http\Requests\TaxAddRequest;
use Modules\TaxModule\app\Http\Requests\TaxUpdateRequest;
use Modules\TaxModule\app\Services\New\TaxService;
use Modules\TaxModule\app\Traits\BusinessModuleTabsTrait;
use Modules\TaxModule\app\Traits\VatTaxConfiguration;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class TaxVatController extends Controller
{
    use BusinessModuleTabsTrait;
    use VatTaxConfiguration;

    private const LIST_ROUTE_NAMES = [
        BusinessModule::RETAIL => 'admin.settings.vat-tax.tax-list-retail',
        BusinessModule::WHOLESALE => 'admin.settings.vat-tax.tax-list-wholesale',
        BusinessModule::AUCTION => 'admin.settings.vat-tax.tax-list-auction',
    ];

    public function __construct(
        private readonly TaxService $taxService,
    ) {}

    public function retail(Request $request): Renderable
    {
        return $this->renderList(businessModule: BusinessModule::RETAIL, request: $request);
    }

    public function wholesale(Request $request): Renderable
    {
        abort_if(! $this->isBusinessModuleAvailable(BusinessModule::WHOLESALE), 404);

        return $this->renderList(businessModule: BusinessModule::WHOLESALE, request: $request);
    }

    public function auction(Request $request): Renderable
    {
        abort_if(! $this->isBusinessModuleAvailable(BusinessModule::AUCTION), 404);

        return $this->renderList(businessModule: BusinessModule::AUCTION, request: $request);
    }

    public function store(TaxAddRequest $request): RedirectResponse
    {
        $this->taxService->create(data: $request->all());
        ToastMagic::success(translate('New tax added successfully'));

        return back();
    }

    public function update(TaxUpdateRequest $request): RedirectResponse
    {
        $taxVat = $this->taxService->update(id: $request['id'], data: $request->all());

        if ($taxVat === null) {
            ToastMagic::error(translate('Tax not found'));

            return back();
        }

        ToastMagic::success(translate('Updated successfully'));

        return to_route($this->listRouteName($taxVat->business_module));
    }

    public function updateStatus(Request $request): JsonResponse|RedirectResponse
    {
        $taxVat = $this->taxService->find(id: $request['id']);

        if ($taxVat === null) {
            if ($request->ajax()) {
                return response()->json(['message' => translate('Tax not found')], 404);
            }
            ToastMagic::error(translate('Tax not found'));

            return back();
        }

        $status = ! (bool) $taxVat->is_active;
        $this->taxService->updateStatus(id: $taxVat->id, status: $status);

        if ($request->ajax()) {
            return response()->json([
                'id' => $taxVat->id,
                'status' => $status,
                'message' => translate('Tax status updated'),
            ]);
        }

        ToastMagic::success(translate('Updated successfully'));

        return to_route($this->listRouteName($taxVat->business_module));
    }

    public function export(Request $request): BinaryFileResponse
    {
        $vatTaxes = $this->taxService->getList(
            searchValue: $request['searchValue'],
            dataLimit: 'all',
            filters: $this->listFilters(
                businessModule: $request['business_module'] ?? BusinessModule::RETAIL,
                countryCode: $request['country_code'],
            ),
            sortBy: TaxService::SORT_LATEST,
        );

        $data = [
            'data' => $vatTaxes,
            'search' => $request['searchValue'] ?? null,
        ];

        return Excel::download(
            new TaxVatExport($data),
            $request['type'] == 'csv' ? 'TaxList.csv' : 'TaxList.xlsx',
        );
    }

    private function renderList(string $businessModule, Request $request): Renderable
    {
        return view('taxmodule::6valley.tax.tax_list', [
            'businessModule' => $businessModule,
            'setupTabs' => $this->businessModuleTabs(
                activeBusinessModule: $businessModule,
                routeNames: self::LIST_ROUTE_NAMES,
            ),
            'vatTaxes' => $this->taxService->getList(
                searchValue: $request['searchValue'],
                dataLimit: getWebConfig(name: 'pagination_limit'),
                filters: $this->listFilters(businessModule: $businessModule, countryCode: $request['country_code']),
                sortBy: TaxService::SORT_NEWEST_ID,
            ),
            'existTaxVatData' => $this->taxService->exists(params: ['business_module' => $businessModule]),
        ]);
    }

    private function listFilters(string $businessModule, ?string $countryCode): array
    {
        return ['business_module' => $businessModule] + $this->getCountryFilters($countryCode);
    }

    private function listRouteName(string $businessModule): string
    {
        return self::LIST_ROUTE_NAMES[$businessModule] ?? self::LIST_ROUTE_NAMES[BusinessModule::RETAIL];
    }
}
