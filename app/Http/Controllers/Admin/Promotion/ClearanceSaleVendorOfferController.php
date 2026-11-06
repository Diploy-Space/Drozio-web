<?php

namespace App\Http\Controllers\Admin\Promotion;

use App\Http\Controllers\Controller;
use App\Services\New\BusinessSettingService;
use App\Services\New\Clearance\StockClearanceSetupService;
use Devrabiul\ToastMagic\Facades\ToastMagic;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class ClearanceSaleVendorOfferController extends Controller
{
    private const VENDOR_OFFER_HOMEPAGE_KEY = 'stock_clearance_vendor_offer_in_homepage';

    private const OFFER_RELATIONS = ['vendorProducts.reviews', 'products', 'shop.seller'];

    private const SEARCH_RELATIONS = ['vendorProducts.reviews', 'products', 'shop'];

    public function __construct(
        private readonly StockClearanceSetupService $stockClearanceSetupService,
        private readonly BusinessSettingService $businessSettingService,
    ) {}

    public function index(): View
    {
        $allVendorList = $this->runningOffers(relations: self::OFFER_RELATIONS);
        $vendorList = $this->runningOffers(relations: self::OFFER_RELATIONS, onlyFeatured: true);

        return view('admin-views.deal.clearance-sale.vendor-offers', [
            'allVendorList' => $this->withReviewSummary($allVendorList),
            'vendorList' => $this->withReviewSummary($vendorList),
            'clearanceConfig' => $this->stockClearanceSetupService->findBy(
                params: ['setup_by' => StockClearanceSetupService::SETUP_BY_ADMIN],
                relations: [],
            ),
        ]);
    }

    public function getSearchedVendorsView(Request $request): JsonResponse
    {
        $allVendorList = $this->runningOffers(
            relations: self::SEARCH_RELATIONS,
            searchValue: $request['searchValue'],
        );

        $allVendorList = $this->withReviewSummary($allVendorList);

        return response()->json([
            'result' => view('admin-views.deal.clearance-sale.partials._search-vendor', compact('allVendorList'))->render(),
        ]);
    }

    public function updateVendorOfferStatus(Request $request): JsonResponse
    {
        $this->businessSettingService->updateOrInsert(
            params: ['type' => self::VENDOR_OFFER_HOMEPAGE_KEY],
            data: ['value' => $request->get('homepage-status', 0)],
        );

        return response()->json([
            'status' => true,
            'message' => translate(':item updated successfully', ['item' => translate('Vendor offer status')]),
        ]);
    }

    public function addClearanceVendorProduct(Request $request): JsonResponse
    {
        $setup = $this->stockClearanceSetupService->findBy(params: ['shop_id' => $request['shopId']], relations: []);

        if ($setup === null) {
            return response()->json(['status' => false, 'message' => translate('Vendor offer not found')]);
        }

        if ($setup->show_in_homepage_once) {
            return response()->json(['status' => false, 'message' => translate('Vendor already exists')]);
        }

        $this->stockClearanceSetupService->update(
            id: $setup->id,
            data: ['show_in_homepage' => 1, 'show_in_homepage_once' => 1],
        );

        return response()->json([
            'status' => true,
            'message' => translate(':item added successfully', ['item' => translate('Vendor')]),
        ]);
    }

    public function updateVendorStatus(Request $request): JsonResponse
    {
        $isUpdated = $this->stockClearanceSetupService->update(
            id: $request['id'],
            data: ['show_in_homepage' => $request->get('status', 0)],
        );

        if ($isUpdated === null) {
            return response()->json(['status' => false, 'message' => translate('Vendor offer not found')], 404);
        }

        return response()->json(['status' => true, 'message' => translate('Vendor status updated successfully')]);
    }

    public function deleteVendorOffer(string|int $id): RedirectResponse
    {
        $this->stockClearanceSetupService->update(id: $id, data: ['show_in_homepage_once' => 0])
            ? ToastMagic::success(translate('Vendor deleted successfully'))
            : ToastMagic::error(translate('Vendor offer not found'));

        return back();
    }

    private function runningOffers(array $relations, bool $onlyFeatured = false, ?string $searchValue = null): Collection
    {
        return $this->stockClearanceSetupService->getList(
            searchValue: $searchValue,
            dataLimit: 'all',
            relations: $relations,
            filters: [
                'setup_by' => StockClearanceSetupService::SETUP_BY_VENDOR,
                'is_active' => 1,
                'show_in_homepage_once' => $onlyFeatured ? 1 : 0,
                StockClearanceSetupService::FILTER_RUNNING => true,
            ],
            sortBy: StockClearanceSetupService::SORT_OLDEST,
        );
    }

    private function withReviewSummary(Collection $offers): Collection
    {
        return $offers->map(function ($offer) {
            $reviews = $offer?->vendorProducts?->pluck('reviews')->collapse();

            $offer->products_count = $offer?->products?->count() ?? 0;

            if ($reviews) {
                $offer->average_rating = $reviews->avg('rating') ?? 0;
                $offer->review_count = $reviews->count();
            }

            return $offer;
        });
    }
}
