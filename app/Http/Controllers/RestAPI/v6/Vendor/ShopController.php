<?php

namespace App\Http\Controllers\RestAPI\v6\Vendor;

use App\Enums\AuthErrorCode;
use App\Enums\BusinessModule;
use App\Enums\WebConfigKey;
use App\Http\Controllers\RestAPI\v6\BaseApiController;
use App\Http\Requests\Vendor\Shop\AvailabilityUpdateRequest;
use App\Http\Requests\Vendor\Shop\UpdateRequest;
use App\Http\Requests\Vendor\Shop\VacationUpdateRequest;
use App\Http\Resources\V6\Vendor\ShopResource;
use App\Services\New\BusinessSettingService;
use App\Services\New\OrderService;
use App\Services\New\ProductRetailService;
use App\Services\New\ProductService;
use App\Services\New\ReviewService;
use App\Services\New\ShopService;
use App\Services\New\ShopTinDocumentService;
use App\Services\New\VendorService;
use App\Utils\VendorVerificationCriteria;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Modules\ApiPlayground\app\Attributes\ApiDoc;
use Modules\Auction\app\Enums\DeliveryStatus;
use Modules\Auction\app\Enums\OwnerType;
use Modules\Auction\app\Services\New\ProductService as AuctionProductService;

class ShopController extends BaseApiController
{
    /** @var array<int, string> */
    private const VENDOR_FIELDS = ['minimum_order_amount', 'free_delivery_status', 'free_delivery_over_amount', 'stock_limit', 'wholesale_free_delivery_status', 'wholesale_free_delivery_over_amount', 'wholesale_minimum_order_amount'];

    /** @var array<int, string> */
    private const WHOLESALE_VENDOR_FIELDS = ['wholesale_free_delivery_status', 'wholesale_free_delivery_over_amount'];

    /** @var array<int, string> */
    private const AMOUNT_FIELDS = ['minimum_order_amount', 'free_delivery_over_amount', 'wholesale_free_delivery_over_amount', 'wholesale_minimum_order_amount'];

    /** @var array<int, string> */
    private const STATUS_FIELDS = ['free_delivery_status', 'wholesale_free_delivery_status'];

    /** @var array<int, string> */
    private const TIN_FIELDS = ['tax_identification_number', 'tin_expire_date'];

    /** @var array<int, string> */
    private const TOTALS_MODULES = [BusinessModule::RETAIL, BusinessModule::WHOLESALE];

    public function __construct(
        private readonly ShopService $shopService,
        private readonly VendorService $vendorService,
        private readonly ProductRetailService $productService,
        private readonly OrderService $orderService,
        private readonly ReviewService $reviewService,
        private readonly ShopTinDocumentService $shopTinDocumentService,
        private readonly BusinessSettingService $businessSettingService,
    ) {}

    #[ApiDoc(
        summary: 'Get the signed-in vendor shop',
        description: 'Returns the shop with its rating, product, order and review totals. The flat total_* fields count retail only; module_totals carries total_orders, total_products and total_reviews per available business module (retail, wholesale), and, while the Auction module is available, total_auction_created (every auction the vendor created, in any state) and total_delivered (those whose delivery status is delivered). Every total is an aggregate query, so the response cost does not grow with the size of the catalogue.',
        tag: 'Shop (Vendor)',
        status: [401, 403, 404],
        guard: 'seller',
    )]
    public function getShop(Request $request): JsonResponse
    {
        $seller = $request->attributes->get('seller');
        $shop = $this->shopService->findBy(params: ['seller_id' => $seller->id]);

        if ($shop === null) {
            return $this->shopNotFound();
        }

        $sellerId = (int) $seller->id;
        $rating = $this->reviewService->getRatingSummary(
            filters: [ReviewService::FILTER_PRODUCT_SELLER_ID => $sellerId],
            includeInactive: true,
        );

        return $this->responseFormatter(
            config: config('response.data_fetched'),
            content: ['data' => (new ShopResource($shop))->resolve() + $this->moduleTinDocuments(shopId: (int) $shop->id) + [
                'setup_guide_app' => $this->shopService->getSetupGuideAppSteps(shop: $shop),
                'stock_limit' => $seller->stock_limit,
                'rating' => $rating['avg_rating'],
                'rating_count' => $rating['total_review'],
                'total_reviews' => $rating['total_review'],
                'total_products' => $this->productService->count(filters: [
                    'added_by' => ProductService::ADDED_BY_SELLER,
                    'user_id' => $sellerId,
                ]),
                'total_orders' => $this->orderService->count(filters: [
                    'seller_is' => OrderService::SELLER_IS_SELLER,
                    'seller_id' => $sellerId,
                ]),
                'module_totals' => $this->moduleTotals(sellerId: $sellerId),
            ],
            ],
        );
    }

    #[ApiDoc(
        summary: 'Get the verified badge criteria for the signed-in vendor',
        description: 'Mirrors the Verification Criteria panel on the vendor shop page. show_prompt is true only while the vendor is not yet verified and the verified badge is switched on; is_qualified is true once every criterion is met. Each criterion carries its label split into leading, highlight and trailing so the highlighted target can be styled, the vendor current value and whether it is met.',
        tag: 'Shop (Vendor)',
        status: [401, 403],
        guard: 'seller',
    )]
    public function getVerificationCriteria(Request $request): JsonResponse
    {
        $seller = $request->attributes->get('seller');

        return $this->responseFormatter(
            config: config('response.data_fetched'),
            content: ['data' => VendorVerificationCriteria::overview(
                metrics: $this->vendorService->getVerificationMetrics(id: $seller->id),
                isVerified: (bool) $seller->is_verified,
                isBadgeEnabled: (bool) $this->businessSettingService->getValueByType(type: WebConfigKey::VENDOR_VERIFIED_BADGE_STATUS, default: 0),
            )],
        );
    }

    #[ApiDoc(
        summary: 'Update the signed-in vendor shop',
        description: 'Only the fields sent are written, so a partial update never blanks the shop name, address or contact. Replaced banners delete the file they supersede.',
        tag: 'Shop (Vendor)',
        status: [401, 403, 404, 422],
        guard: 'seller',
    )]
    public function updateShop(UpdateRequest $request): JsonResponse
    {
        $seller = $request->attributes->get('seller');
        $shop = $this->shopService->findBy(params: ['seller_id' => $seller->id]);

        if ($shop === null) {
            return $this->shopNotFound();
        }

        $data = $request->validated();
        $vendorData = $this->vendorColumns($data);

        if ($vendorData !== []) {
            $this->vendorService->updateBy(params: ['id' => $seller->id], data: $vendorData);
        }

        $this->shopService->update(
            id: $shop->id,
            data: $data,
            images: [
                'image' => $request->file('logo'),
                'banner' => $request->file('banner'),
                'bottom_banner' => $request->file('bottom_banner'),
                'offer_banner' => $request->file('offer_banner'),
            ],
            tinCertificate: $request->file('tin_certificate'),
        );

        $tinCertificates = $this->tinCertificates(request: $request);

        $this->shopTinDocumentService->syncForShop(
            shopId: (int) $shop->id,
            documents: $this->tinDocumentColumns(data: $data, certificates: $tinCertificates),
            certificates: $tinCertificates,
        );

        return $this->responseFormatter(config: config('response.shop_information_updated_successfully'));
    }

    #[ApiDoc(
        summary: 'Open or close the signed-in vendor shop',
        description: 'Sends the business modules the shop stays open for; every other module the shop offers is closed. Only this shop cache entry is invalidated.',
        tag: 'Shop (Vendor)',
        status: [401, 403, 404, 422],
        guard: 'seller',
    )]
    public function updateTemporaryClose(AvailabilityUpdateRequest $request): JsonResponse
    {
        $seller = $request->attributes->get('seller');

        $shop = $this->shopService->updateBy(
            params: ['seller_id' => $seller->id, 'author_type' => ShopService::AUTHOR_TYPE_VENDOR],
            data: $request->temporaryCloseColumns(),
        );

        if ($shop === null) {
            return $this->shopNotFound();
        }

        cacheRemoveByType(type: 'shops');

        return $this->responseFormatter(
            config: config('response.shop_status_updated_successfully'),
            content: ['data' => (new ShopResource($shop))->resolve()],
        );
    }

    #[ApiDoc(
        summary: 'Set vacation mode on the signed-in vendor shop',
        description: 'A duration type of until_change clears the dates; one_day runs 24 hours from the moment it is saved; custom requires a start date that is not in the past (unless unchanged) and an end date after both the start and now.',
        tag: 'Shop (Vendor)',
        status: [401, 403, 404, 422],
        guard: 'seller',
    )]
    public function updateVacation(VacationUpdateRequest $request): JsonResponse
    {
        $seller = $request->attributes->get('seller');

        $shop = $this->shopService->updateBy(
            params: ['seller_id' => $seller->id],
            data: $request->vacationColumns(),
        );

        if ($shop === null) {
            return $this->shopNotFound();
        }

        cacheRemoveByType(type: 'shops');

        return $this->responseFormatter(
            config: config('response.vacation_mode_updated_successfully'),
            content: ['data' => (new ShopResource($shop))->resolve()],
        );
    }

    /** @return array<string, mixed> */
    private function vendorColumns(array $data): array
    {
        $columns = array_intersect_key($data, array_flip(self::VENDOR_FIELDS));

        if (! businessModuleManager()->isAvailableInPanel(BusinessModule::WHOLESALE)) {
            $columns = array_diff_key($columns, array_flip(self::WHOLESALE_VENDOR_FIELDS));
        }

        if (! $this->vendorService->canSetWholesaleMinimumOrderAmount()) {
            unset($columns['wholesale_minimum_order_amount']);
        }

        foreach (self::AMOUNT_FIELDS as $field) {
            if (array_key_exists($field, $columns)) {
                $columns[$field] = currencyConverter($columns[$field], 'usd');
            }
        }

        foreach (self::STATUS_FIELDS as $field) {
            if (array_key_exists($field, $columns)) {
                $columns[$field] = (int) $columns[$field];
            }
        }

        return $columns;
    }

    /** @return array<string, mixed> */
    /** @return array<string, array{total_orders: int, total_products: int, total_reviews: int}> */
    private function moduleTotals(int $sellerId): array
    {
        $orderCounts = $this->orderService->getSellerOrderCountsByBusinessModule(sellerId: $sellerId);
        $productCounts = $this->productService->getSellerProductCountsByBusinessModule(sellerId: $sellerId);
        $reviewCounts = $this->reviewService->getSellerReviewCountsByBusinessModule(sellerId: $sellerId);
        $totals = [];

        foreach (self::TOTALS_MODULES as $module) {
            if (! businessModuleManager()->isAvailableInPanel($module)) {
                continue;
            }

            $totals[$module] = [
                'total_orders' => $orderCounts[$module] ?? 0,
                'total_products' => $productCounts[$module] ?? 0,
                'total_reviews' => $reviewCounts[$module] ?? 0,
            ];
        }

        if (businessModuleManager()->isAvailableInPanel(BusinessModule::AUCTION)) {
            $totals[BusinessModule::AUCTION] = $this->auctionTotals(sellerId: $sellerId);
        }

        return $totals;
    }

    /** @return array{total_auction_created: int, total_delivered: int} */
    private function auctionTotals(int $sellerId): array
    {
        $auctionProductService = app(AuctionProductService::class);
        $ownerFilters = ['owner_type' => OwnerType::SELLER, 'owner_id' => $sellerId];

        return [
            'total_auction_created' => $auctionProductService->count(filters: $ownerFilters),
            'total_delivered' => $auctionProductService->count(filters: $ownerFilters + ['delivery_status' => DeliveryStatus::DELIVERED]),
        ];
    }

    private function moduleTinDocuments(int $shopId): array
    {
        $documents = $this->shopTinDocumentService->getByShop(shopId: $shopId);
        $fields = [];

        foreach (UpdateRequest::MODULE_TIN_PREFIXES as $module) {
            $document = $documents[$module] ?? null;
            $fields[$module.'_tax_identification_number'] = $document?->tax_identification_number;
            $fields[$module.'_tin_expire_date'] = $document?->tin_expire_date;
            $fields[$module.'_tin_certificate_full_url'] = $document?->tin_certificate_full_url;
        }

        return $fields;
    }

    /** @return array<string, array<string, string|null>> */
    private function tinDocumentColumns(array $data, array $certificates): array
    {
        $documents = array_map(static fn (): array => [], $certificates);

        foreach ($this->tinFieldPrefixes() as $module => $prefix) {
            foreach (self::TIN_FIELDS as $field) {
                if (array_key_exists($prefix.$field, $data)) {
                    $documents[$module][$field] = $data[$prefix.$field];
                }
            }
        }

        return $documents;
    }

    /** @return array<string, UploadedFile> */
    private function tinCertificates(Request $request): array
    {
        $certificates = [];

        foreach ($this->tinFieldPrefixes() as $module => $prefix) {
            if ($request->hasFile($prefix.'tin_certificate')) {
                $certificates[$module] = $request->file($prefix.'tin_certificate');
            }
        }

        return $certificates;
    }

    /** @return array<string, string> */
    private function tinFieldPrefixes(): array
    {
        $prefixes = [];

        foreach (array_keys(businessModuleManager()->availableInPanel()) as $module) {
            if ($module === BusinessModule::RETAIL) {
                $prefixes[$module] = '';
            } elseif (in_array($module, UpdateRequest::MODULE_TIN_PREFIXES, true)) {
                $prefixes[$module] = $module.'_';
            }
        }

        return $prefixes;
    }

    private function shopNotFound(): JsonResponse
    {
        return $this->authError(
            config: config('response.seller_shop_not_found'),
            errorCode: AuthErrorCode::ACCOUNT_NOT_FOUND,
        );
    }
}
