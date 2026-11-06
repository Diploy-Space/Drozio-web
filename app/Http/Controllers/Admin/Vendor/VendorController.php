<?php

namespace App\Http\Controllers\Admin\Vendor;

use App\Enums\BusinessModule;
use App\Enums\GlobalConstant;
use App\Enums\WebConfigKey;
use App\Events\VendorRegistrationEvent;
use App\Events\WithdrawStatusUpdateEvent;
use App\Exports\VendorListExport;
use App\Exports\VendorOrderListExport;
use App\Exports\VendorWithdrawRequest;
use App\Http\Controllers\BaseController;
use App\Http\Requests\Admin\Vendor\AddRequest;
use App\Http\Requests\Admin\Vendor\SettingUpdateRequest;
use App\Services\BusinessModule\BusinessModule as BusinessModuleDefinition;
use App\Services\New\AdminService;
use App\Services\New\Auth\VendorAuthService;
use App\Services\New\BusinessSettingService;
use App\Services\New\Clearance\StockClearanceProductService;
use App\Services\New\Clearance\StockClearanceSetupService;
use App\Services\New\CustomerService;
use App\Services\New\DeliveryCountryCodeService;
use App\Services\New\DeliveryManService;
use App\Services\New\DeliveryZipCodeService;
use App\Services\New\OrderDetailService;
use App\Services\New\OrderEditHistoryService;
use App\Services\New\OrderEditService;
use App\Services\New\OrderService;
use App\Services\New\OrderStatusHistoryService;
use App\Services\New\OrderTransactionService;
use App\Services\New\ProductRetailService;
use App\Services\New\ReviewService;
use App\Services\New\ShopService;
use App\Services\New\ShopTinDocumentService;
use App\Services\New\VendorService;
use App\Services\New\VendorWalletService;
use App\Services\New\WithdrawRequestService;
use App\Traits\CommonTrait;
use App\Traits\EmailTemplateTrait;
use App\Traits\OrderEditSessionTrait;
use App\Traits\OrderEditSummaryTrait;
use App\Traits\PushNotificationTrait;
use App\Utils\OrderManager;
use Devrabiul\ToastMagic\Facades\ToastMagic;
use Exception;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Session;
use Maatwebsite\Excel\Facades\Excel;
use Modules\TaxModule\app\Traits\VatTaxManagement;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class VendorController extends BaseController
{
    private const DEFAULT_DECIMAL_POINTS = 2;

    use CommonTrait;
    use EmailTemplateTrait;
    use OrderEditSessionTrait;
    use OrderEditSummaryTrait;
    use PushNotificationTrait;
    use VatTaxManagement;

    private const SETUP_BY_VENDOR = 'vendor';

    private const STORE_PAGE_LIMIT = 8;

    private const BUILDER_ADDON = 'Builder';

    private const BUILDER_CONFIG_TABLE = 'tenant_domain_configs';

    private const TIN_CERTIFICATE_PATH = 'storage/app/public/shop/documents/';

    private const STOREFRONT_SHOP_ROUTES = [
        BusinessModule::RETAIL => 'vendor-shop',
        BusinessModule::AUCTION => 'auction.shop',
        BusinessModule::WHOLESALE => 'wholesale.shop',
    ];

    private const RETAIL_COMMISSION_KEY = 'sales_commission';

    private const RETAIL_VENDOR_WISE_COMMISSION_KEY = 'retail_vendor_wise_commission_status';

    private const TAB_OVERVIEW = 'overview';

    private const TAB_ORDER = 'order';

    private const TAB_PRODUCT = 'product';

    private const TAB_CLEARANCE_SALE = 'clearance_sale';

    private const TAB_REVIEW = 'review';

    private const TAB_SETTING = 'setting';

    private const TAB_TRANSACTION = 'transaction';

    public function __construct(
        private readonly VendorService $vendorService,
        private readonly VendorWalletService $vendorWalletService,
        private readonly ShopService $shopService,
        private readonly AdminService $adminService,
        private readonly CustomerService $customerService,
        private readonly OrderService $orderService,
        private readonly OrderTransactionService $orderTransactionService,
        private readonly OrderEditHistoryService $orderEditHistoryService,
        private readonly OrderDetailService $orderDetailService,
        private readonly OrderStatusHistoryService $orderStatusHistoryService,
        private readonly ProductRetailService $productService,
        private readonly ReviewService $reviewService,
        private readonly DeliveryManService $deliveryManService,
        private readonly DeliveryCountryCodeService $deliveryCountryCodeService,
        private readonly DeliveryZipCodeService $deliveryZipCodeService,
        private readonly WithdrawRequestService $withdrawRequestService,
        private readonly StockClearanceProductService $stockClearanceProductService,
        private readonly StockClearanceSetupService $stockClearanceSetupService,
        private readonly OrderEditService $orderEditService,
        private readonly ShopTinDocumentService $shopTinDocumentService,
        private readonly VendorAuthService $vendorAuthService,
        private readonly BusinessSettingService $businessSettingService,
    ) {}

    public function index(?Request $request, ?string $type = null): View
    {
        $vendors = $this->vendorService->getList(
            searchValue: $request['searchValue'],
            dataLimit: getWebConfig(name: WebConfigKey::PAGINATION_LIMIT),
            relations: ['shop', 'wallet'],
            withCount: $this->vendorListCounts(),
            filters: $this->vendorListFilters(productBusinessModule: $request['product_business_module']),
            sortBy: $this->vendorListSort(sortBy: $request['sortBy']),
        )->appends($request->only(['product_business_module']));

        $isVerifiedBadgeEnabled = $this->isVerifiedBadgeEnabled();
        $sellerIds = $vendors->pluck('id')->all();

        return view('admin-views.vendor.index', [
            'vendors' => $vendors,
            'current_date' => date('Y-m-d'),
            'isVerifiedBadgeEnabled' => $isVerifiedBadgeEnabled,
            'vacationSchedules' => $this->runningVacationSchedules(sellers: $vendors->getCollection()),
            'temporaryClosures' => $this->temporaryClosures(sellers: $vendors->getCollection()),
            'productBusinessModules' => $this->vendorBusinessModuleOptions(),
            'verificationCandidateCount' => $isVerifiedBadgeEnabled ? $this->vendorService->countVerificationCandidates() : 0,
            'productCountModules' => [[
                'key' => BusinessModule::RETAIL,
                'label' => translate('Retail'),
                'counts' => $this->productService->getCountsBySeller(sellerIds: $sellerIds),
                'route' => 'admin.people.vendors.view.retail',
                'tab' => self::TAB_PRODUCT,
            ]],
            'orderCountModules' => [[
                'key' => BusinessModule::RETAIL,
                'label' => translate('Retail'),
                'counts' => $this->orderService->getCountsBySeller(sellerIds: $sellerIds),
                'route' => 'admin.people.vendors.view.retail',
                'tab' => self::TAB_ORDER,
            ]],
        ]);
    }

    public function getAddView(Request $request): View
    {
        return view('admin-views.vendor.add-new-vendor', [
            'tinBusinessModules' => $this->tinBusinessModules(),
        ]);
    }

    public function add(AddRequest $request): JsonResponse
    {
        $superAdmin = $this->adminService->findBy(params: ['admin_role_id' => AdminService::ROLE_SUPER_ADMIN]);

        if ($superAdmin && $request['email'] === $superAdmin->email) {
            return response()->json([
                'error' => 1,
                'message' => translate('Email has already been taken'),
            ]);
        }

        $vendor = $this->vendorService->create(
            data: $this->vendorColumns($request->all()),
            image: $request->file('image'),
        );

        $shop = $this->shopService->create(
            data: $this->registrationShopColumns(data: $request->all(), vendorId: $vendor->id),
            images: [
                'image' => $request->file('logo'),
                'banner' => $request->file('banner'),
                'bottom_banner' => $request->file('bottom_banner'),
            ],
        );

        $this->shopTinDocumentService->syncForShop(
            shopId: $shop->id,
            documents: $this->tinDocumentColumns(data: $request['tin'] ?? []),
            certificates: $this->tinCertificates(request: $request),
        );

        $this->vendorWalletService->create(data: $this->initialWalletColumns(vendorId: $vendor->id));

        try {
            event(new VendorRegistrationEvent(
                email: $request['email'],
                data: $this->registrationEventData(vendorName: $request['f_name'], status: VendorService::STATUS_PENDING),
            ));
        } catch (Exception $exception) {
        }

        return response()->json(['message' => translate(':item added successfully', ['item' => translate('Vendor')])]);
    }

    public function updateStatus(Request $request): RedirectResponse
    {
        $vendor = $this->vendorService->find(id: $request['id']);

        if ($vendor === null) {
            ToastMagic::error(translate('Vendor not found It may be deleted'));

            return back();
        }

        $status = $request['status'];
        $this->vendorService->updateBy(params: ['id' => $vendor->id], data: ['status' => $status]);

        if ($status === VendorService::STATUS_SUSPENDED) {
            $this->vendorAuthService->revokeAccessToken(account: $vendor);
        }

        $this->statusUpdateToast(status: $status);

        event(new VendorRegistrationEvent(
            email: $vendor->email,
            data: $this->registrationEventData(
                vendorName: $vendor->f_name,
                status: $status,
                wasPending: $vendor->status === VendorService::STATUS_PENDING,
            ),
        ));

        return back();
    }

    public function exportList(Request $request): BinaryFileResponse
    {
        $vendors = $this->vendorService->getList(
            searchValue: $request['searchValue'],
            dataLimit: 'all',
            relations: ['shop'],
            withCount: [
                ...$this->vendorListCounts(),
                'product',
                'orders' => fn (Builder $query) => $query->where('seller_is', OrderService::SELLER_IS_SELLER),
            ],
            filters: $this->vendorListFilters(productBusinessModule: $request['product_business_module']),
            sortBy: $this->vendorListSort(sortBy: $request['sortBy']),
        );

        return Excel::download(new VendorListExport([
            'vendors' => $vendors,
            'search' => $request['searchValue'],
            'active' => $vendors->where('status', VendorService::STATUS_APPROVED)->count(),
            'inactive' => $vendors->where('status', '!=', VendorService::STATUS_APPROVED)->count(),
        ]), 'Seller-list.xlsx');
    }

    public function exportOrderList(Request $request, $vendorId): BinaryFileResponse
    {
        $orders = $this->orderService->getList(
            searchValue: $request['searchValue'],
            dataLimit: 'all',
            filters: $this->presentFilters([
                'seller_id' => $vendorId,
                'seller_is' => OrderService::SELLER_IS_SELLER,
                'order_type' => OrderService::ORDER_TYPE_DEFAULT,
                'order_status' => $request['order_status'],
            ]),
            sortBy: OrderService::SORT_NEWEST,
        );

        return Excel::download(new VendorOrderListExport([
            'shop' => $this->shopService->findBy(params: ['seller_id' => $vendorId]),
            'statusArray' => $this->orderStatusTally(orders: $orders),
            'orders' => $orders,
        ]), 'Order-List.xlsx');
    }

    public function updateSalesCommission(Request $request, $id): RedirectResponse
    {
        if ($request['status'] == 1 && $request['commission'] === null) {
            ToastMagic::error(translate('You did not set commission percentage field'));

            return back();
        }

        $this->vendorService->updateBy(
            params: ['id' => $id],
            data: ['sales_commission_percentage' => $request['commission_status'] == 1 ? $request['commission'] : null],
        );
        ToastMagic::success(translate('Commission percentage for this seller has been updated'));

        return back();
    }

    public function getOrderDetailsView(string|int $id): View|RedirectResponse
    {
        $countryRestrictStatus = getWebConfig(name: 'delivery_country_restriction');
        $zipRestrictStatus = getWebConfig(name: 'delivery_zip_code_area_restriction');

        $order = $this->orderService->find(id: $id, relations: [
            'details.productAllStatus',
            'latestEditHistory',
            'verificationImages',
            'shipping',
            'seller.shop',
            'offlinePayments',
            'deliveryMan',
            'orderEditHistory' => fn ($query) => $query->orderBy('id', 'desc'),
        ]);

        if (! $order) {
            ToastMagic::error(translate('Not found'));

            return redirect()->route('admin.retail.orders.list', ['status' => 'all']);
        }

        if ($order['init_order_amount'] <= 0) {
            $this->orderService->updateWhere(
                params: ['id' => $id],
                data: ['init_order_amount' => $order['order_amount']],
            );
        }

        $isOrderEditable = $this->orderEditService->checkIsOrderEditable(order: $order, type: OrderEditService::TYPE_ADMIN);
        Session::forget($this->orderEditService->getOrderEditSessionKey(owner: $this->orderEditSessionOwner(), orderId: $order['id']));
        $orderProductsSession = ['product_list' => $this->orderEditProductList(orderEditService: $this->orderEditService, order: $order)];

        $editOrderSummary = $this->getEditOrderSummary(order: $order, editedProducts: $orderProductsSession['product_list'], editor: [
            'edit_by' => OrderService::EDITOR_ADMIN,
            'edited_user_id' => auth()->guard('admin')->id(),
            'edited_user_name' => auth()->guard('admin')->user()->name,
        ]);

        $isDefaultOrder = $order['order_type'] === OrderService::ORDER_TYPE_DEFAULT;
        $view = $isDefaultOrder
            ? 'admin-views.order.order-details'
            : 'admin-views.pos.order.order-details';
        $orderEditPaymentHistory = $this->orderEditHistoryService->getList(
            dataLimit: 'all',
            filters: ['order_id' => $order['id']],
        );
        $shippingAddress = $order['shipping_address_data'] ?? null;
        $isInHouseOrder = $order['seller_is'] === OrderService::SELLER_IS_ADMIN;
        $detailsDisplayData = $isDefaultOrder ? [
            'shippingAddress' => $shippingAddress,
            'billing' => $this->orderService->getBillingAddressData(order: $order),
            'isBillingSameAsShipping' => $this->orderService->isBillingSameAsShipping(order: $order),
            'orderVerificationStatus' => getWebConfig(name: 'order_verification'),
            'mapApiStatus' => getWebConfig(name: 'map_api_status'),
            'mapApiKey' => getWebConfig(name: 'map_api_key'),
            'inHouseShopName' => $isInHouseOrder ? getInHouseShopConfig(key: 'name') : null,
            'inHouseShopImage' => $isInHouseOrder ? getInHouseShopConfig(key: 'image_full_url') : null,
            'orderTotalPriceSummary' => OrderManager::getOrderTotalPriceSummary(order: $order),
            'detailDisplayRows' => $this->orderDetailService->getDisplayRows(details: $order->details),
            'editSettlement' => $this->orderEditHistoryService->getSettlementSummary(order: $order, histories: $orderEditPaymentHistory),
            'deliveryPartner' => $this->orderService->getDeliveryPartnerState(order: $order, panel: OrderService::DELIVERY_PARTNER_PANEL_ADMIN),
            'disableDeliveryType' => ! $this->hasPhysicalProduct(order: $order) && $shippingAddress,
            'statusHistoryDates' => $this->orderStatusHistoryService->getLatestStatusDates(histories: $order->orderStatusHistory),
            'digitalProductProcessComplete' => $this->orderService->isDigitalFileAfterSellUploaded(order: $order),
        ] : [];

        return view($view, [
            ...$detailsDisplayData,
            'order' => $order,
            'linkedOrders' => $this->linkedOrders(order: $order),
            'deliveryMen' => $this->deliveryManService->getList(
                dataLimit: 'all',
                withCount: ['orders'],
                filters: ['is_active' => DeliveryManService::STATUS_ACTIVE, 'seller_id' => $this->orderDeliveryManOwner(order: $order)],
            ),
            'totalDelivered' => $this->orderService->count(filters: [
                'seller_id' => $order['seller_id'],
                'order_status' => OrderService::STATUS_DELIVERED,
                'order_type' => OrderService::ORDER_TYPE_DEFAULT,
            ]),
            'companyName' => getWebConfig(name: 'company_name'),
            'companyWebLogo' => getWebConfig(name: 'company_web_logo'),
            'physicalProduct' => $this->hasPhysicalProduct(order: $order),
            'countryRestrictStatus' => $countryRestrictStatus,
            'zipRestrictStatus' => $zipRestrictStatus,
            'countries' => $countryRestrictStatus ? $this->deliveryCountryOptions() : GlobalConstant::COUNTRIES,
            'zipCodes' => $zipRestrictStatus ? $this->deliveryZipCodeService->getList(dataLimit: 'all') : 0,
            'orderCount' => $this->customerOrderCount(order: $order),
            'isOrderOnlyDigital' => $this->isOrderOnlyDigital(order: $order),
            'previousOrder' => $this->adjacentOrder(id: $id, isPrevious: true),
            'nextOrder' => $this->adjacentOrder(id: $id, isPrevious: false),
            'allProductsList' => $this->productService->getList(
                dataLimit: 'all',
                relations: ['brand', 'category', 'seller.shop'],
                filters: $this->orderEditService->getEditableProductFilters(order: $order) + [ProductRetailService::FILTER_ACTIVE => true],
            ),
            'isOrderEditable' => $isOrderEditable,
            'orderProductsSession' => $orderProductsSession,
            'editProductQuantities' => $this->orderEditService->getProductListQuantityBounds(productList: $orderProductsSession['product_list']),
            'editOrderSummary' => $editOrderSummary,
            'orderEditPaymentHistory' => $orderEditPaymentHistory,
        ]);
    }

    public function getView(Request $request, $id): View|RedirectResponse
    {
        $seller = $this->vendorDetailSeller(id: $id);

        if (! $seller) {
            return redirect()->route('admin.people.vendors.vendor-list');
        }

        $taxData = $this->getTaxSystemType();
        $vacationSchedule = $this->shopVacationSchedule(shop: $seller->shop);

        return view('admin-views.vendor.view', [
            'seller' => $seller,
            'vacationSchedule' => $vacationSchedule,
            'isShopOnVacation' => $this->isVacationRunning(vacationSchedule: $vacationSchedule),
            'closedBusinesses' => $this->shopClosedBusinesses(shop: $seller->shop),
            'productWiseTax' => $taxData['productWiseTax'] && ! $taxData['is_included'],
            'current_date' => date('Y-m-d'),
            'isVerifiedBadgeEnabled' => $this->isVerifiedBadgeEnabled(),
            'vendorTabScope' => $this->vendorTabScope(module: BusinessModule::COMMON, tab: self::TAB_OVERVIEW),
            'retailOverview' => $this->retailOverview(seller: $seller),
            'tinDocuments' => $this->tinDocuments(seller: $seller),
            'storefrontLinks' => $this->storefrontLinks(seller: $seller),
        ]);
    }

    public function getCommonTabView(Request $request, $id, string $tab): View|RedirectResponse
    {
        $seller = $this->vendorDetailSeller(id: $id);

        if (! $seller) {
            return redirect()->route('admin.people.vendors.vendor-list');
        }

        return match ($tab) {
            self::TAB_TRANSACTION => $this->getTransactionListTabView(request: $request, seller: $seller),
            default => redirect()->route('admin.people.vendors.view', ['id' => $id]),
        };
    }

    public function getRetailTabView(Request $request, $id, string $tab): View|RedirectResponse
    {
        $seller = $this->vendorDetailSeller(id: $id);

        if (! $seller) {
            return redirect()->route('admin.people.vendors.vendor-list');
        }

        return match ($tab) {
            self::TAB_ORDER => $this->getOrderListTabView(request: $request, seller: $seller),
            self::TAB_PRODUCT => $this->getProductListTabView(request: $request, seller: $seller),
            self::TAB_REVIEW => $this->getReviewListTabView(request: $request, seller: $seller),
            self::TAB_CLEARANCE_SALE => $this->getClearanceSaleTabView(request: $request, seller: $seller),
            self::TAB_SETTING => $this->getSettingListTabView(request: $request, seller: $seller, id: $id),
            default => redirect()->route('admin.people.vendors.view', ['id' => $id]),
        };
    }

    /** @return array<string, mixed> */
    private function retailOverview(Model $seller): array
    {
        $rating = $this->reviewService->getRatingSummary(
            filters: [ReviewService::FILTER_PRODUCT_SELLER_ID => $seller->id],
            precision: 1,
        );

        return [
            'statLabel' => translate('Retail'),
            'statIsActive' => ! $seller->shop?->temporary_close,
            'statMetrics' => [
                translate('Products') => $this->productService->getList(
                    dataLimit: 'all',
                    filters: ['seller_id' => $seller['id'], 'added_by' => ProductRetailService::ADDED_BY_SELLER],
                )->count(),
                translate('Orders') => $this->orderService->getList(
                    dataLimit: 'all',
                    filters: ['seller_id' => $seller['id'], 'seller_is' => OrderService::SELLER_IS_SELLER],
                )->count(),
            ],
            'statRating' => [
                'average' => $rating['avg_rating'],
                'ratings' => $rating['total_review'],
                'reviews' => $rating['total_comment'],
            ],
        ];
    }

    /** @return array<int, array<string, mixed>> */
    private function tinDocuments(Model $seller): array
    {
        if ($seller->shop === null) {
            return [];
        }

        $documents = $this->shopTinDocumentService->getList(
            dataLimit: 'all',
            filters: ['shop_id' => $seller->shop->id],
        );

        $labels = $this->tinBusinessModules();
        $rows = [];

        foreach ($labels as $moduleKey => $moduleLabel) {
            $document = $documents->firstWhere('business_module', $moduleKey);

            $rows[] = [
                'module' => $moduleKey,
                'label' => translate($moduleLabel),
                'number' => $document?->tax_identification_number,
                'expireDate' => $document?->tin_expire_date,
                'certificate' => $document?->tin_certificate,
                'certificateUrl' => $this->tinCertificateUrl(fileName: $document?->tin_certificate),
            ];
        }

        return $rows;
    }

    private function tinCertificateUrl(?string $fileName): ?string
    {
        if ($fileName === null || $fileName === '') {
            return null;
        }

        return file_exists(base_path(self::TIN_CERTIFICATE_PATH.$fileName))
            ? dynamicStorage(path: self::TIN_CERTIFICATE_PATH.$fileName)
            : null;
    }

    /** @return array<int, array{label: string, url: string}> */
    private function storefrontLinks(Model $seller): array
    {
        $slug = $seller->shop?->slug;
        $isLive = $slug !== null
            && ! in_array($seller['status'], ['pending', 'suspended', 'rejected'], true);

        if (! $isLive) {
            return [];
        }

        $links = [];

        foreach (self::STOREFRONT_SHOP_ROUTES as $moduleKey => $routeName) {
            if (! businessModuleManager()->isAvailableInPanel($moduleKey) || ! Route::has($routeName)) {
                continue;
            }

            $links[] = [
                'label' => translate(businessModuleManager()->get($moduleKey)?->label ?? ucfirst($moduleKey)),
                'url' => route($routeName, ['slug' => $slug]),
            ];
        }

        return $links;
    }

    /** @return array{businesses: string, covers_all_businesses: bool, start_date: ?string, end_date: ?string, is_open_ended: bool, is_upcoming: bool}|null */
    private function shopVacationSchedule(?Model $shop): ?array
    {
        if ($shop === null) {
            return null;
        }

        return $this->shopService->getVacationSchedule(
            isOnVacation: (bool) $shop['vacation_status'],
            durationType: $shop['vacation_duration_type'],
            startDate: $shop['vacation_start_date'],
            endDate: $shop['vacation_end_date'],
            vacationModules: $this->shopService->getVacationModules(
                moduleLabels: $this->vendorBusinessModuleOptions(),
                selectedModuleKeys: $shop['vacation_modules'],
            ),
        );
    }

    private function shopClosedBusinesses(?Model $shop): ?string
    {
        return $shop === null ? null : $this->shopService->getClosedBusinessesLabel(
            availabilityModules: $this->shopAvailabilityModules(shop: $shop),
        );
    }

    /** @return array<int, array{businesses: string, covers_all_businesses: bool}> */
    private function temporaryClosures(Collection $sellers): array
    {
        return $sellers
            ->filter(fn (Model $seller): bool => $seller->shop !== null)
            ->mapWithKeys(function (Model $seller): array {
                $availabilityModules = $this->shopAvailabilityModules(shop: $seller->shop);
                $closedBusinesses = $this->shopService->getClosedBusinessesLabel(availabilityModules: $availabilityModules);

                return [$seller->id => $closedBusinesses === null ? null : [
                    'businesses' => $closedBusinesses,
                    'covers_all_businesses' => ! in_array(true, array_column($availabilityModules, 'is_open'), true),
                ]];
            })
            ->filter()
            ->all();
    }

    /** @return array<int, array{key: string, label: string, description_on: string, description_off: string, is_open: bool}> */
    private function shopAvailabilityModules(Model $shop): array
    {
        $closeColumns = array_intersect_key(ShopService::TEMPORARY_CLOSE_COLUMNS, businessModuleManager()->availableInPanel());

        return $this->shopService->getAvailabilityModules(
            moduleLabels: $this->vendorBusinessModuleOptions(),
            availability: array_map(fn (string $column): bool => ! (bool) $shop[$column], $closeColumns),
        );
    }

    /** @return array<int, array{businesses: string, covers_all_businesses: bool, start_date: ?string, end_date: ?string, is_open_ended: bool, is_upcoming: bool}> */
    private function runningVacationSchedules(Collection $sellers): array
    {
        return $sellers
            ->mapWithKeys(fn (Model $seller): array => [$seller->id => $this->shopVacationSchedule(shop: $seller->shop)])
            ->filter(fn (?array $vacationSchedule): bool => $this->isVacationRunning(vacationSchedule: $vacationSchedule))
            ->all();
    }

    private function isVacationRunning(?array $vacationSchedule): bool
    {
        return $vacationSchedule !== null && ! $vacationSchedule['is_upcoming'];
    }

    private function vendorDetailSeller(string|int $id): ?Model
    {
        $seller = $this->vendorService->getList(
            dataLimit: 'all',
            relations: [
                'orders',
                'product' => fn ($query) => $query->with([
                    'taxVats' => fn ($taxVats) => $taxVats->with(['tax'])->whereHas('tax', fn ($tax) => $tax->where('is_active', 1)),
                ]),
            ],
            withCount: [
                'product',
                'orders' => fn (Builder $query) => $query->where([
                    'seller_id' => $id,
                    'seller_is' => $id == 0 ? OrderService::SELLER_IS_ADMIN : OrderService::SELLER_IS_SELLER,
                ]),
            ],
            filters: ['id' => $id],
        )->first();

        if ($seller) {
            $this->applyProductRatings(seller: $seller);
        }

        return $seller;
    }

    /** @return array{module: string, tab: string} */
    private function vendorTabScope(string $module, string $tab): array
    {
        return ['module' => $module, 'tab' => $tab];
    }

    public function getOrderListTabView(Request $request, $seller): View
    {
        $orders = $this->orderService->getList(
            searchValue: $request['searchValue'],
            dataLimit: getWebConfig(name: WebConfigKey::PAGINATION_LIMIT),
            relations: [
                'customer',
                'latestEditHistory' => fn ($query) => $query->select([
                    'id', 'order_id', 'order_due_payment_method', 'order_due_amount',
                    'order_due_payment_status', 'order_return_amount', 'order_return_payment_status',
                ]),
                'details' => fn ($query) => $query->select(['id', 'order_id', 'product_details', 'qty', 'price', 'discount', 'variant']),
            ],
            filters: $this->presentFilters([
                'seller_id' => $seller['id'],
                'seller_is' => OrderService::SELLER_IS_SELLER,
                'order_type' => OrderService::ORDER_TYPE_DEFAULT,
                'order_status' => $request['order_status'],
            ]),
            sortBy: OrderService::SORT_NEWEST,
        );

        $statusCounts = $this->orderService->getStatusCounts(filters: $this->orderTabCountFilters(request: $request, seller: $seller));

        return view('admin-views.vendor.view.order', [
            'seller' => $seller,
            'vendorTabScope' => $this->vendorTabScope(module: BusinessModule::RETAIL, tab: self::TAB_ORDER),
            'orders' => $orders,
            'itemPreviews' => $this->orderService->getItemPreviews(orders: $orders),
            'allOrdersInfo' => [
                'pending_order' => $statusCounts['pending'] ?? 0,
                'confirmed_order' => $statusCounts['confirmed'] ?? 0,
                'processing_order' => $statusCounts['processing'] ?? 0,
                'out_for_delivery_order' => $statusCounts['out_for_delivery'] ?? 0,
                'delivered_order' => $statusCounts['delivered'] ?? 0,
                'canceled_order' => $statusCounts['canceled'] ?? 0,
                'returned_order' => $statusCounts['returned'] ?? 0,
                'failed_order' => $statusCounts['failed'] ?? 0,
            ],
        ]);
    }

    public function getProductListTabView(Request $request, $seller): View
    {
        $taxData = $this->getTaxSystemType();

        return view('admin-views.vendor.view.product', [
            'seller' => $seller,
            'vendorTabScope' => $this->vendorTabScope(module: BusinessModule::RETAIL, tab: self::TAB_PRODUCT),
            'products' => $this->productService->getList(
                searchValue: $request['searchValue'],
                dataLimit: getWebConfig(name: WebConfigKey::PAGINATION_LIMIT),
                relations: ['translations', 'clearanceSale'],
                filters: ['seller_id' => $seller['id'], 'added_by' => ProductRetailService::ADDED_BY_SELLER],
                sortBy: ProductRetailService::SORT_NEWEST_ID,
            ),
            'productWiseTax' => $taxData['productWiseTax'] && ! $taxData['is_included'],
        ]);
    }

    public function getSettingListTabView(Request $request, $seller, $id): View
    {
        $builderPublished = getCheckAddonPublishedStatus(self::BUILDER_ADDON) === 1;
        $builderConfig = null;

        if ($builderPublished && Schema::hasTable(self::BUILDER_CONFIG_TABLE)) {
            $builderConfig = DB::table(self::BUILDER_CONFIG_TABLE)->where('tenant_id', $seller->id)->first();
        }

        return view('admin-views.vendor.view.setting', [
            'seller' => $seller,
            'vendorTabScope' => $this->vendorTabScope(module: BusinessModule::RETAIL, tab: self::TAB_SETTING),
            'builderPublished' => $builderPublished,
            'builderConfig' => $builderConfig,
            'isVendorWiseCommission' => (bool) $this->businessSettingService->getValueByType(
                type: self::RETAIL_VENDOR_WISE_COMMISSION_KEY,
                default: 0,
            ),
            'defaultCommission' => $this->businessSettingService->getValueByType(
                type: self::RETAIL_COMMISSION_KEY,
                default: 0,
            ),
            'commissionDecimalPoints' => (int) $this->businessSettingService->getValueByType(
                type: WebConfigKey::DECIMAL_POINT_SETTINGS,
                default: self::DEFAULT_DECIMAL_POINTS,
            ),
        ]);
    }

    public function updateSetting(SettingUpdateRequest $request, $id): RedirectResponse
    {
        $data = [
            'gst' => $request->boolean('gst_status') ? $request['gst'] : null,
            'pos_status' => (int) $request->boolean('seller_pos'),
        ];

        if ($this->businessSettingService->getValueByType(type: self::RETAIL_VENDOR_WISE_COMMISSION_KEY, default: 0)) {
            $data['sales_commission_percentage'] = $request['commission'] === null || $request['commission'] === ''
                ? null
                : $request['commission'];
        }

        $this->vendorService->updateBy(params: ['id' => $id], data: $data);

        if (Schema::hasTable(self::BUILDER_CONFIG_TABLE) && getCheckAddonPublishedStatus(self::BUILDER_ADDON) === 1) {
            $this->updateBuilderStorefrontAccess(vendorId: $id, isVisible: (int) $request->boolean('builder_status'));
        }

        ToastMagic::success(translate(':item updated successfully', ['item' => translate('Vendor setting')]));

        return redirect()->back();
    }

    public function getTransactionListTabView(Request $request, $seller): View
    {
        $transactions = $this->orderTransactionService->getList(
            searchValue: $request['searchValue'],
            dataLimit: getWebConfig(name: WebConfigKey::PAGINATION_LIMIT),
            relations: ['order.customer', 'seller'],
            filters: $this->presentFilters([
                'seller_is' => OrderTransactionService::SELLER_IS_SELLER,
                'seller_id' => $seller['id'],
                'status' => $request['status'] === 'all' ? null : $request['status'],
                'customer_id' => $request['customer_id'] === 'all' ? null : $request['customer_id'],
            ]),
            sortBy: OrderTransactionService::SORT_NEWEST,
        );

        $customers = $this->customerService->getNameOptions(
            searchValue: $request['searchValue'],
            excludeIds: [0],
            dataLimit: 'all',
        )->toArray();
        array_unshift($customers, ['id' => 'all', 'text' => translate('All Customer')]);

        return view('admin-views.vendor.view.transaction', [
            'seller' => $seller,
            'vendorTabScope' => $this->vendorTabScope(module: BusinessModule::COMMON, tab: self::TAB_TRANSACTION),
            'transactions' => $transactions,
            'customers' => $customers,
        ]);
    }

    public function getReviewListTabView(Request $request, $seller): View
    {
        $filters = $request->has('searchValue')
            ? [
                'product_id' => $this->sellerProductIds(searchValue: $request['searchValue'], sellerId: $seller['id']),
                'delivery_man_id' => null,
            ]
            : [ReviewService::FILTER_PRODUCT_SELLER_ID => $seller['id']];

        return view('admin-views.vendor.view.review', [
            'seller' => $seller,
            'vendorTabScope' => $this->vendorTabScope(module: BusinessModule::RETAIL, tab: self::TAB_REVIEW),
            'reviews' => $this->reviewService->getList(
                dataLimit: getWebConfig(name: WebConfigKey::PAGINATION_LIMIT),
                relations: ['product.seller.shop', 'customer', 'reply'],
                filters: $filters,
                sortBy: ReviewService::SORT_NEWEST_ID,
            ),
        ]);
    }

    public function getClearanceSaleTabView(Request $request, $seller): View
    {
        return view('admin-views.vendor.view.clearance_sale', [
            'seller' => $seller,
            'vendorTabScope' => $this->vendorTabScope(module: BusinessModule::RETAIL, tab: self::TAB_CLEARANCE_SALE),
            'stockClearanceProduct' => $this->stockClearanceProductService->getList(
                searchValue: $request['searchValue'],
                relations: ['product', 'product.category', 'product.brand'],
                filters: ['added_by' => self::SETUP_BY_VENDOR, 'user_id' => $seller->id],
            ),
            'clearanceConfig' => $this->stockClearanceSetupService->findBy(
                params: ['setup_by' => StockClearanceSetupService::SETUP_BY_VENDOR, 'user_id' => $seller->id],
                relations: [],
            ),
        ]);
    }

    public function getWithdrawView($withdrawId, $vendorId): View|RedirectResponse
    {
        $withdrawRequest = $this->withdrawRequestService->find(id: $withdrawId, relations: ['seller']);

        if (! $withdrawRequest) {
            ToastMagic::error(translate('Not found'));

            return back();
        }

        $withdrawalMethodFields = $this->withdrawalMethodFields(fields: $withdrawRequest['withdrawal_method_fields']);

        return view('admin-views.vendor.withdraw-view', [
            'withdrawRequest' => $withdrawRequest,
            'withdrawalMethodName' => $withdrawalMethodFields['method_name'] ?? null,
            'withdrawalMethodFields' => Arr::except($withdrawalMethodFields, ['method_name']),
            'isPending' => (int) $withdrawRequest['approved'] === WithdrawRequestService::APPROVAL_PENDING,
            'statusVariant' => $this->withdrawStatusVariant(approved: (int) $withdrawRequest['approved']),
            'statusLabel' => $this->withdrawStatusLabel(approved: (int) $withdrawRequest['approved']),
        ]);
    }

    private function withdrawalMethodFields(mixed $fields): array
    {
        if (blank($fields)) {
            return [];
        }

        if (is_object($fields)) {
            return (array) $fields;
        }

        return is_array($fields) ? $fields : (json_decode($fields, true) ?? []);
    }

    private function withdrawStatusVariant(int $approved): string
    {
        return match ($approved) {
            WithdrawRequestService::APPROVAL_APPROVED => 'success',
            WithdrawRequestService::APPROVAL_PENDING => 'info',
            default => 'danger',
        };
    }

    private function withdrawStatusLabel(int $approved): string
    {
        return match ($approved) {
            WithdrawRequestService::APPROVAL_APPROVED => translate('Approved'),
            WithdrawRequestService::APPROVAL_PENDING => translate('Pending'),
            default => translate('Denied'),
        };
    }

    public function getWithdrawListView(Request $request): View
    {
        return view('admin-views.vendor.withdraw', [
            'withdrawRequests' => $this->withdrawRequestService->getList(
                searchValue: $request['searchValue'],
                dataLimit: getWebConfig(name: 'pagination_limit'),
                relations: ['seller', 'seller.shop'],
                filters: $this->withdrawFilters(status: $request['approved']),
                sortBy: WithdrawRequestService::SORT_NEWEST_ID,
            ),
        ]);
    }

    public function exportWithdrawList(Request $request): BinaryFileResponse
    {
        $status = $request['approved'] ?: WithdrawRequestService::STATUS_ALL;
        $withdrawRequests = $this->withdrawRequestService->getList(
            searchValue: $request['searchValue'],
            dataLimit: 'all',
            relations: ['seller'],
            filters: $this->withdrawFilters(status: $status),
            sortBy: WithdrawRequestService::SORT_NEWEST_ID,
        );

        $this->appendWithdrawExportColumns(withdrawRequests: $withdrawRequests);

        return Excel::download(new VendorWithdrawRequest([
            'data-from' => 'admin',
            'withdraw_request' => $withdrawRequests,
            'filter' => $status,
            'searchValue' => $request['searchValue'],
            'pending' => $withdrawRequests->where('approved', WithdrawRequestService::APPROVAL_PENDING)->count(),
            'approved' => $withdrawRequests->where('approved', WithdrawRequestService::APPROVAL_APPROVED)->count(),
            'denied' => $withdrawRequests->where('approved', WithdrawRequestService::APPROVAL_DENIED)->count(),
        ]), 'Vendor-Withdraw-Request.xlsx');
    }

    public function withdrawStatus(Request $request, $id): RedirectResponse
    {
        $withdraw = $this->withdrawRequestService->find(id: $id, relations: ['seller']);
        $isApproved = $request['approved'] == WithdrawRequestService::APPROVAL_APPROVED;

        if (isset($withdraw->seller->cm_firebase_token) && $withdraw->seller->cm_firebase_token) {
            event(new WithdrawStatusUpdateEvent(
                key: 'withdraw_request_status_message',
                type: 'seller',
                lang: $withdraw->seller?->app_language ?? getDefaultLanguage(),
                status: $request['approved'],
                fcmToken: $withdraw->seller?->cm_firebase_token,
            ));
        }

        $this->settleVendorWallet(vendorId: $withdraw['seller_id'], amount: $withdraw['amount'], isApproved: $isApproved);
        $this->withdrawRequestService->update(id: $id, data: [
            'approved' => $request['approved'],
            'transaction_note' => $request['note'],
        ]);

        $isApproved
            ? ToastMagic::success(translate('Vendor payment has been approved successfully'))
            : ToastMagic::info(translate('Vendor payment request has been denied successfully'));

        return redirect()->route('admin.people.vendors.withdraw-list');
    }

    public function loadMoreStores(Request $request): JsonResponse
    {
        $page = $request->input('page', 1);
        $filterShops = $this->shopService->getList(
            dataLimit: self::STORE_PAGE_LIMIT,
            page: $page,
            sortBy: ShopService::SORT_AUTHOR_TYPE_ASC,
        );

        $visibleLimit = $filterShops->perPage();
        $totalShops = $filterShops->total();

        return response()->json([
            'html' => view('admin-views.partials.product-filters._filter-shops', [
                'filterShops' => $filterShops,
                'oldShops' => $request['filter_shop_ids'] ? json_decode($request['filter_shop_ids']) : [],
            ])->render(),
            'visibleLimit' => $visibleLimit,
            'hiddenCount' => max(0, $totalShops - ($page * $visibleLimit)),
            'totalShops' => $totalShops,
        ]);
    }

    /** @return array<int|string, mixed> */
    private function isVerifiedBadgeEnabled(): bool
    {
        return (bool) $this->businessSettingService->getValueByType(type: WebConfigKey::VENDOR_VERIFIED_BADGE_STATUS, default: 0);
    }

    private function vendorListCounts(): array
    {
        return [
            'product as wishlist_count' => fn (Builder $query) => $query->whereHas('wishList'),
        ];
    }

    /** @return array<string, string> */
    private function vendorListFilters(?string $productBusinessModule): array
    {
        return array_key_exists((string) $productBusinessModule, $this->vendorBusinessModuleOptions())
            ? [VendorService::FILTER_HAS_APPROVED_PRODUCTS_IN_BUSINESS_MODULE => $productBusinessModule]
            : [];
    }

    /** @return array<string, string> */
    private function vendorBusinessModuleOptions(): array
    {
        return array_map(
            static fn (BusinessModuleDefinition $module): string => $module->label,
            businessModuleManager()->availableInPanel(),
        );
    }

    private function vendorListSort(?string $sortBy): string
    {
        return in_array($sortBy, [
            VendorService::SORT_OLDEST,
            VendorService::SORT_WISHLIST_COUNT,
            VendorService::SORT_TOTAL_EARNING,
        ], true) ? $sortBy : VendorService::SORT_LATEST;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function vendorColumns(array $data): array
    {
        return [
            'f_name' => $data['f_name'],
            'l_name' => $data['l_name'],
            'phone' => $data['phone'],
            'email' => $data['email'],
            'password' => $data['password'],
            'status' => ($data['status'] ?? null) === VendorService::STATUS_APPROVED
                ? VendorService::STATUS_APPROVED
                : VendorService::STATUS_PENDING,
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function registrationShopColumns(array $data, int $vendorId): array
    {
        return [
            'seller_id' => $vendorId,
            'name' => $data['shop_name'],
            'address' => $data['shop_address'],
            'contact' => $data['phone'],
            'tax_identification_number' => $data['tin'][BusinessModule::RETAIL]['tax_identification_number'] ?? null,
            'tin_expire_date' => $data['tin'][BusinessModule::RETAIL]['tin_expire_date'] ?? null,
        ];
    }

    /** @return array<string, string> */
    private function tinBusinessModules(): array
    {
        return array_map(
            static fn (BusinessModuleDefinition $module): string => $module->label,
            businessModuleManager()->availableInPanel(),
        );
    }

    /**
     * @param  array<string, array<string, string|null>>  $data
     * @return array<string, array<string, string|null>>
     */
    private function tinDocumentColumns(array $data): array
    {
        return array_map(static fn (array $document): array => [
            'tax_identification_number' => $document['tax_identification_number'] ?? null,
            'tin_expire_date' => $document['tin_expire_date'] ?? null,
        ], array_intersect_key($data, $this->tinBusinessModules()));
    }

    /** @return array<string, UploadedFile|null> */
    private function tinCertificates(Request $request): array
    {
        $certificates = [];

        foreach (array_keys($this->tinBusinessModules()) as $businessModule) {
            $certificates[$businessModule] = $request->file("tin.{$businessModule}.tin_certificate");
        }

        return $certificates;
    }

    /** @return array<string, int> */
    private function initialWalletColumns(int $vendorId): array
    {
        return [
            'seller_id' => $vendorId,
            'withdrawn' => 0,
            'commission_given' => 0,
            'total_earning' => 0,
            'pending_withdraw' => 0,
            'delivery_charge_earned' => 0,
            'collected_cash' => 0,
        ];
    }

    /** @return array<string, string> */
    private function registrationEventData(string $vendorName, string $status, bool $wasPending = true): array
    {
        $template = match (true) {
            $wasPending && $status === VendorService::STATUS_APPROVED => [
                'status' => 'approved',
                'subject' => translate('Vendor Registration Approved'),
                'templateName' => 'registration-approved',
            ],
            $wasPending && $status === VendorService::STATUS_REJECTED => [
                'status' => 'denied',
                'subject' => translate('Vendor Registration Denied'),
                'templateName' => 'registration-denied',
            ],
            $status === VendorService::STATUS_SUSPENDED => [
                'status' => 'suspended',
                'subject' => translate('Account Suspended'),
                'templateName' => 'account-suspended',
            ],
            $status === VendorService::STATUS_PENDING => [
                'status' => 'pending',
                'subject' => translate('Vendor registration completed successfully'),
                'templateName' => 'registration',
            ],
            default => [
                'status' => 'approved',
                'subject' => translate('Account Activate'),
                'templateName' => 'account-activation',
            ],
        };

        return [
            'vendorName' => $vendorName,
            'status' => $template['status'],
            'subject' => $template['subject'],
            'title' => $template['subject'],
            'userType' => 'vendor',
            'templateName' => $template['templateName'],
        ];
    }

    private function statusUpdateToast(string $status): void
    {
        match ($status) {
            VendorService::STATUS_APPROVED => ToastMagic::success(translate('Vendor has been approved successfully')),
            VendorService::STATUS_REJECTED => ToastMagic::info(translate('Vendor has been rejected successfully')),
            VendorService::STATUS_SUSPENDED => ToastMagic::info(translate('Vendor has been suspended successfully')),
            default => null,
        };
    }

    /** @return array<string, int> */
    private function orderStatusTally(iterable $orders): array
    {
        $statusArray = [
            'pending' => 0,
            'confirmed' => 0,
            'processing' => 0,
            'out_for_delivery' => 0,
            'delivered' => 0,
            'returned' => 0,
            'failed' => 0,
            'canceled' => 0,
        ];

        foreach ($orders as $order) {
            if (isset($statusArray[$order->order_status])) {
                $statusArray[$order->order_status]++;
            }
        }

        return $statusArray;
    }

    /** @return array<int|string, array<string, string>> */
    private function deliveryCountryOptions(): array
    {
        $deliveryCountryCodes = $this->deliveryCountryCodeService->getList(dataLimit: 'all');

        $countries = [];
        foreach ($deliveryCountryCodes as $deliveryCountryCode) {
            foreach (GlobalConstant::COUNTRIES as $key => $country) {
                if ($country['code'] === $deliveryCountryCode['country_code']) {
                    $countries[$key] = ['code' => $country['code'], 'name' => $country['name']];
                }
            }
        }

        return $countries;
    }

    private function linkedOrders(Model $order): iterable
    {
        if ($order['order_group_id'] === 'def-order-group') {
            return collect();
        }

        return $this->orderService->getList(
            dataLimit: 'all',
            filters: [
                'order_group_id' => $order['order_group_id'],
                OrderService::FILTER_ID_EXCEPT => [$order['id']],
            ],
        );
    }

    private function orderDeliveryManOwner(Model $order): int
    {
        return $order['seller_is'] === OrderService::SELLER_IS_SELLER
            && $order['shipping_responsibility'] === 'sellerwise_shipping'
            ? (int) $order['seller_id']
            : DeliveryManService::SELLER_ADMIN;
    }

    private function hasPhysicalProduct(Model $order): bool
    {
        foreach ($order->details ?? [] as $orderDetail) {
            $orderDetailProduct = json_decode($orderDetail?->product_details, true);

            if (
                (isset($orderDetail?->product?->product_type) && $orderDetail->product->product_type === 'physical')
                || (isset($orderDetailProduct['product_type']) && $orderDetailProduct['product_type'] === 'physical')
            ) {
                return true;
            }
        }

        return false;
    }

    private function isOrderOnlyDigital(Model $order): bool
    {
        foreach ($order->orderDetails ?? [] as $orderDetail) {
            $product = json_decode($orderDetail->product_details);

            if (isset($product->product_type) && $product->product_type === 'physical') {
                return false;
            }
        }

        return true;
    }

    private function adjacentOrder(string|int $id, bool $isPrevious): ?Model
    {
        return $this->orderService->getList(
            dataLimit: 1,
            filters: [$isPrevious ? OrderService::FILTER_ID_BEFORE : OrderService::FILTER_ID_AFTER => $id],
            sortBy: $isPrevious ? OrderService::SORT_NEWEST : OrderService::SORT_OLDEST,
        )->first();
    }

    private function customerOrderCount(Model $order): int
    {
        return $this->orderService->count(filters: $order['order_type'] === OrderService::ORDER_TYPE_DEFAULT
            ? ['customer_id' => $order['customer_id']]
            : ['customer_id' => $order['customer_id'], 'order_type' => 'POS']);
    }

    private function applyProductRatings(Model $seller): void
    {
        $seller?->product?->map(function ($product) {
            $product['rating'] = $product?->reviews->pluck('rating')->sum();
            $product['rating_count'] = $product->reviews->count();
            $product['single_rating_5'] = 0;
            $product['single_rating_4'] = 0;
            $product['single_rating_3'] = 0;
            $product['single_rating_2'] = 0;
            $product['single_rating_1'] = 0;

            foreach ($product->reviews as $review) {
                if ($review->rating > 0) {
                    match ($review->rating) {
                        5 => $product->single_rating_5++,
                        4 => $product->single_rating_4++,
                        3 => $product->single_rating_3++,
                        2 => $product->single_rating_2++,
                        1 => $product->single_rating_1++,
                    };
                }
            }
        });

        $seller['single_rating_5'] = $seller?->product->pluck('single_rating_5')->sum();
        $seller['single_rating_4'] = $seller?->product->pluck('single_rating_4')->sum();
        $seller['single_rating_3'] = $seller?->product->pluck('single_rating_3')->sum();
        $seller['single_rating_2'] = $seller?->product->pluck('single_rating_2')->sum();
        $seller['single_rating_1'] = $seller?->product->pluck('single_rating_1')->sum();
        $seller['total_rating'] = $seller?->product->pluck('rating')->sum();
        $seller['rating_count'] = $seller->product->pluck('rating_count')->sum();
        $seller['average_rating'] = $seller['total_rating'] / ($seller['rating_count'] == 0 ? 1 : $seller['rating_count']);
    }

    /** @return array<string, mixed> */
    private function orderTabCountFilters(Request $request, $seller): array
    {
        $filters = $this->presentFilters([
            'seller_id' => $seller['id'],
            'seller_is' => OrderService::SELLER_IS_SELLER,
            'delivery_man_id' => $request['delivery_man_id'],
            'customer_id' => $request['customer_id'] === 'all' ? null : $request['customer_id'],
            OrderService::FILTER_DATE_TYPE => $request['date_type'],
            'payment_status' => $request['payment_status'] ?? [],
            'order_type' => $request['order_types'] ?? [OrderService::ORDER_TYPE_DEFAULT],
        ]);

        if ($request['date_type'] === OrderService::DATE_TYPE_CUSTOM && $request['from'] && $request['to']) {
            $filters[OrderService::FILTER_DATE_RANGE] = [$request['from'], $request['to']];
        }

        if (! empty($request->input('order_amount_settlement', []))) {
            $filters[OrderService::FILTER_SETTLEMENT] = $request->input('order_amount_settlement');
        }

        return $filters;
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    private function presentFilters(array $filters): array
    {
        return array_filter($filters, static fn ($value) => $value !== null && $value !== '' && $value !== []);
    }

    /** @return array<int, int> */
    private function sellerProductIds(?string $searchValue, int|string $sellerId): array
    {
        $productIds = $this->productService->getList(
            searchValue: $searchValue,
            dataLimit: 'all',
            filters: ['added_by' => ProductRetailService::ADDED_BY_SELLER, 'seller_id' => $sellerId],
        )->pluck('id')->all();

        return $productIds === [] ? [0] : $productIds;
    }

    /** @return array<string, mixed> */
    private function withdrawFilters(?string $status): array
    {
        $approval = match ($status) {
            WithdrawRequestService::STATUS_PENDING => WithdrawRequestService::APPROVAL_PENDING,
            WithdrawRequestService::STATUS_APPROVED => WithdrawRequestService::APPROVAL_APPROVED,
            WithdrawRequestService::STATUS_DENIED => WithdrawRequestService::APPROVAL_DENIED,
            default => null,
        };

        return ['delivery_man_id' => null] + ($approval === null ? [] : ['approved' => $approval]);
    }

    private function appendWithdrawExportColumns(iterable $withdrawRequests): void
    {
        foreach ($withdrawRequests as $withdrawRequest) {
            $withdrawRequest->shop_name = $withdrawRequest->seller?->shop?->name ?? '';
            $withdrawRequest->shop_phone = $withdrawRequest->seller?->shop?->contact ?? '';
            $withdrawRequest->shop_address = $withdrawRequest->seller?->shop?->address ?? '';
            $withdrawRequest->shop_email = $withdrawRequest->seller?->email ?? '';
            $withdrawRequest->withdrawal_amount = setCurrencySymbol(
                amount: usdToDefaultCurrency(amount: $withdrawRequest->amount),
                currencyCode: getCurrencyCode(type: 'default'),
            );
            $withdrawRequest->status = match ((int) $withdrawRequest->approved) {
                WithdrawRequestService::APPROVAL_PENDING => 'Pending',
                WithdrawRequestService::APPROVAL_APPROVED => 'Approved',
                default => 'Denied',
            };
            $withdrawRequest->note = $withdrawRequest->transaction_note;
            $withdrawRequest->withdraw_method_name = $withdrawRequest->withdraw_method?->method_name ?? '';

            if (! empty($withdrawRequest->withdrawal_method_fields)) {
                $methodFields = is_array($withdrawRequest->withdrawal_method_fields)
                    ? $withdrawRequest->withdrawal_method_fields
                    : json_decode($withdrawRequest->withdrawal_method_fields);

                foreach ($methodFields as $key => $field) {
                    $withdrawRequest[$key] = $field;
                }
            }
        }
    }

    private function settleVendorWallet(int|string $vendorId, float|string $amount, bool $isApproved): void
    {
        $wallet = $this->vendorWalletService->findBy(params: ['seller_id' => $vendorId]);

        if ($wallet === null) {
            return;
        }

        $settledColumn = $isApproved ? 'withdrawn' : 'total_earning';

        $this->vendorWalletService->updateWhere(params: ['seller_id' => $vendorId], data: [
            $settledColumn => $wallet->{$settledColumn} + $amount,
            'pending_withdraw' => $wallet->pending_withdraw - $amount,
        ]);
    }

    private function updateBuilderStorefrontAccess(int|string $vendorId, int|string $isVisible): void
    {
        $config = DB::table(self::BUILDER_CONFIG_TABLE)->where('tenant_id', $vendorId);

        if ($config->exists()) {
            $config->update(['website_visibility' => $isVisible, 'updated_at' => now()]);

            return;
        }

        DB::table(self::BUILDER_CONFIG_TABLE)->insert([
            'tenant_id' => $vendorId,
            'sub_tenant_id' => $this->vendorService->find(id: $vendorId, relations: ['shop'])?->shop?->id,
            'website_visibility' => $isVisible,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
