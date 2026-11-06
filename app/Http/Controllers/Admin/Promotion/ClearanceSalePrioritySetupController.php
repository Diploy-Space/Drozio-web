<?php

namespace App\Http\Controllers\Admin\Promotion;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ClearanceSale\PrioritySetupUpdateRequest;
use App\Services\New\BusinessSettingService;
use App\Services\New\Clearance\StockClearanceSetupService;
use App\Traits\InHouseTrait;
use Devrabiul\ToastMagic\Facades\ToastMagic;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ClearanceSalePrioritySetupController extends Controller
{
    private const PRODUCT_PRIORITY = 'stock_clearance_product_list_priority';

    private const VENDOR_PRIORITY = 'stock_clearance_vendor_priority';

    use InHouseTrait;

    public function __construct(
        private readonly StockClearanceSetupService $stockClearanceSetupService,
        private readonly BusinessSettingService $businessSettingService,
    ) {}

    public function index(): View
    {
        $prioritySetup = $this->getPrioritySetup();

        return view('admin-views.deal.clearance-sale.priority-setup', [
            ...$prioritySetup,
            'availableVendors' => $this->getAvailableVendors(priorityShopIds: $prioritySetup['stockClearanceVendors']),
        ]);
    }

    public function updateConfig(PrioritySetupUpdateRequest $request): RedirectResponse
    {
        $this->storePrioritySetup(data: $request->all());
        ToastMagic::success(translate(':item updated successfully', ['item' => translate('Priority Setup')]));

        return back();
    }

    /** @return array{stockClearancePriority: ?array<string, mixed>, stockClearanceVendors: array<int, int>} */
    private function getPrioritySetup(): array
    {
        $settings = $this->businessSettingService
            ->getList(dataLimit: 'all', filters: ['type' => [self::PRODUCT_PRIORITY, self::VENDOR_PRIORITY]])
            ->pluck('value', 'type');

        return [
            'stockClearancePriority' => json_decode((string) $settings->get(self::PRODUCT_PRIORITY), true),
            'stockClearanceVendors' => json_decode((string) $settings->get(self::VENDOR_PRIORITY), true) ?? [],
        ];
    }

    /** @param  array<int, int>  $priorityShopIds */
    private function getAvailableVendors(array $priorityShopIds): Collection
    {
        $order = array_flip(array_values($priorityShopIds));
        $inHouseShop = $this->getInHouseShopObject();

        return $this->stockClearanceSetupService
            ->getList(dataLimit: 'all', relations: ['shop'], filters: ['is_active' => 1])
            ->pluck('shop')
            ->map(fn (?Model $shop) => $shop ?? $inHouseShop)
            ->sortBy(fn (Model $shop) => $order[$shop->id] ?? PHP_INT_MAX)
            ->values();
    }

    /** @param  array<string, mixed>  $data */
    private function storePrioritySetup(array $data): void
    {
        DB::transaction(function () use ($data): void {
            $this->businessSettingService->updateOrInsert(
                params: ['type' => self::VENDOR_PRIORITY],
                data: ['value' => json_encode($data['vendor_priorities_id'] ?? [])],
            );
            $this->businessSettingService->updateOrInsert(params: ['type' => self::PRODUCT_PRIORITY], data: ['value' => json_encode([
                'custom_sorting_status' => $data['custom_sorting_status'] ?? 0,
                'sort_by' => $data['sort_by'] ?? null,
                'out_of_stock_product' => $data['out_of_stock_product'] ?? null,
                'temporary_close_sorting' => $data['temporary_close_sorting'] ?? null,
            ])]);
        });
    }
}
