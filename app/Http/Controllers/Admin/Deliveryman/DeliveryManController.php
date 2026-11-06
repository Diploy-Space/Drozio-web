<?php

namespace App\Http\Controllers\Admin\Deliveryman;

use App\Enums\BusinessModule;
use App\Enums\ExportFileNames\Admin\DeliveryMan as DeliveryManExport;
use App\Enums\WebConfigKey;
use App\Exports\DeliveryManListExport;
use App\Exports\DeliveryManOrderHistory;
use App\Http\Controllers\BaseController;
use App\Http\Requests\Admin\DeliveryMan\AddRequest;
use App\Http\Requests\Admin\DeliveryMan\UpdateRequest;
use App\Services\New\BusinessSettingService;
use App\Services\New\DeliveryManService;
use App\Services\New\DeliverymanWalletService;
use App\Services\New\OrderService;
use App\Services\New\OrderStatusHistoryService;
use App\Services\New\ReviewService;
use Devrabiul\ToastMagic\Facades\ToastMagic;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class DeliveryManController extends BaseController
{
    private const LIST_FILE_NAME = 'Delivery-Man-List.xlsx';

    private const EARNING_EXPORT_TYPE = 'earn';

    /** @var array<int, string> */
    private const LIST_RELATIONS = ['rating'];

    /** @var array<int, string> */
    private const LIST_WITH_COUNT = ['orders'];

    /** @var array<string, string> */
    private const SORT_KEYS = [
        'latest' => DeliveryManService::SORT_LATEST,
        'oldest' => DeliveryManService::SORT_OLDEST,
        'rating' => DeliveryManService::SORT_TOP_RATED,
    ];

    public function __construct(
        private readonly DeliveryManService $deliveryManService,
        private readonly DeliverymanWalletService $deliverymanWalletService,
        private readonly OrderService $orderService,
        private readonly OrderStatusHistoryService $orderStatusHistoryService,
        private readonly ReviewService $reviewService,
        private readonly BusinessSettingService $businessSettingService,
    ) {}

    public function index(?Request $request, ?string $type = null): View
    {
        $deliveryMens = $this->deliveryManService->getList(
            searchValue: $request['searchValue'],
            dataLimit: $this->paginationLimit(),
            relations: self::LIST_RELATIONS,
            withCount: self::LIST_WITH_COUNT,
            filters: $this->listFilters(sortBy: $request['sort_by']),
            sortBy: $this->sortKey(sortBy: $request['sort_by']),
        );

        return view('admin-views.delivery-man.list', compact('deliveryMens'));
    }

    public function getAddView(Request $request): View
    {
        $telephoneCodes = TELEPHONE_CODES;

        return view('admin-views.delivery-man.index', compact('telephoneCodes'));
    }

    public function updateStatus(Request $request): JsonResponse
    {
        $this->deliveryManService->updateStatus(id: $request['id'], status: (bool) $request->get('status', 0));

        return response()->json(['message' => translate(':item updated successfully', ['item' => translate('Status')])], 200);
    }

    public function exportList(Request $request): BinaryFileResponse
    {
        $deliveryMens = $this->deliveryManService->getList(
            searchValue: $request['searchValue'],
            dataLimit: $this->paginationLimit(),
            relations: self::LIST_RELATIONS,
            withCount: self::LIST_WITH_COUNT,
            filters: $this->listFilters(sortBy: $request['sort_by']),
            sortBy: $this->sortKey(sortBy: $request['sort_by']),
        );

        return Excel::download(new DeliveryManListExport([
            'delivery_men' => $deliveryMens,
            'search' => $request['searchValue'],
            'active' => $deliveryMens->where('is_active', DeliveryManService::STATUS_ACTIVE)->count(),
            'inactive' => $deliveryMens->where('is_active', DeliveryManService::STATUS_INACTIVE)->count(),
        ]), self::LIST_FILE_NAME);
    }

    public function getUpdateView($id): View
    {
        $deliveryMan = $this->deliveryManService->find(id: $id);
        $telephoneCodes = TELEPHONE_CODES;

        return view('admin-views.delivery-man.edit', compact('deliveryMan', 'telephoneCodes'));
    }

    public function add(AddRequest $request): JsonResponse
    {
        $this->deliveryManService->create(
            data: $this->deliveryManData(request: $request) + ['seller_id' => DeliveryManService::SELLER_ADMIN],
            image: $request->file('image'),
            identityImages: $request->file('identity_image') ?? [],
        );

        return response()->json(['message' => translate(':item added successfully', ['item' => translate('Delivery Man')])]);
    }

    public function update(UpdateRequest $request, $id): JsonResponse
    {
        $deliveryMan = $this->deliveryManService->findBy(params: ['id' => $id, 'seller_id' => DeliveryManService::SELLER_ADMIN]);

        if ($deliveryMan === null) {
            return response()->json(['errors' => translate('Delivery man not found')]);
        }

        $data = $this->deliveryManData(request: $request);
        $phoneOwner = $this->deliveryManService->findBy(params: [
            'phone' => $data['phone'],
            'country_code' => $data['country_code'],
        ]);

        if ($phoneOwner !== null && $phoneOwner->id !== $deliveryMan->id) {
            return response()->json(['errors' => translate('Phone number has already been taken')]);
        }

        $this->deliveryManService->update(
            id: $deliveryMan->id,
            data: $data,
            image: $request->file('image'),
            identityImages: $request->file('identity_image') ?? [],
        );

        return response()->json(['message' => translate(':item updated successfully', ['item' => translate('Delivery Man')])]);
    }

    public function delete(Request $request): RedirectResponse
    {
        $this->deliveryManService->delete(id: $request['id'])
            ? ToastMagic::success(translate('Delivery man removed'))
            : ToastMagic::error(translate('Delivery man not found'));

        return back();
    }

    public function getEarningOverview(Request $request, $id): View
    {
        $deliveryMan = $this->deliveryManService->find(id: $id, relations: ['wallet']);
        $withdrawalableBalance = $deliveryMan?->wallet === null
            ? null
            : $this->deliverymanWalletService->getBalanceSummary(deliverymanId: (int) $id)['withdrawable_balance'];

        return view('admin-views.delivery-man.earning-statement.overview', compact('deliveryMan', 'withdrawalableBalance'));
    }

    public function getOrderHistoryList(Request $request, $id): View
    {
        $deliveryMan = $this->deliveryManService->find(id: $id, relations: ['wallet']);
        $businessModule = $this->resolveOrderBusinessModule($request['business_module']);
        $orders = $this->orderService->getDeliverymanOrderHistory(
            deliverymanId: (int) $id,
            searchValue: $request['searchValue'],
            filters: $this->deliverymanOrderFilters(deliverymanId: $id),
            businessModule: $businessModule,
            dataLimit: $this->paginationLimit(),
        );
        $this->decorateOrdersWithModule($orders);

        return view('admin-views.delivery-man.earning-statement.active-log', [
            'deliveryMan' => $deliveryMan,
            'orders' => $orders,
            'businessModule' => $businessModule,
            'wholesaleAvailable' => $this->wholesaleModuleAvailable(),
        ]);
    }

    public function getOrderHistoryListExport(Request $request, $id): BinaryFileResponse
    {
        $deliveryMan = $this->deliveryManService->find(id: $id, relations: ['wallet']);
        $businessModule = $this->resolveOrderBusinessModule($request['business_module']);
        $orders = $this->orderService->getDeliverymanOrderHistory(
            deliverymanId: (int) $id,
            searchValue: $request['searchValue'],
            filters: $this->deliverymanOrderFilters(
                deliverymanId: $id,
                orderStatuses: $this->splitStatuses($request['order_status']),
                paymentStatuses: $this->splitStatuses($request['payment_status']),
            ),
            businessModule: $businessModule,
            dataLimit: 'all',
            relations: ['details'],
        );
        $this->decorateOrdersWithModule($orders);

        $fileName = $request['type'] === self::EARNING_EXPORT_TYPE
            ? DeliveryManExport::EXPORT_EARNING_LIST_XLSX
            : DeliveryManExport::EXPORT_ORDER_LIST_XLSX;

        return Excel::download(
            new DeliveryManOrderHistory($this->orderHistoryExportData(
                request: $request,
                deliveryMan: $deliveryMan,
                orders: $orders,
                businessModule: $businessModule,
            )),
            $fileName,
        );
    }

    public function getOrderWiseEarningView(Request $request, $id): View
    {
        $businessModule = $this->resolveOrderBusinessModule($request['business_module']);
        $orders = $this->orderService->getDeliverymanOrderHistory(
            deliverymanId: (int) $id,
            searchValue: $request['searchValue'],
            filters: $this->deliverymanOrderFilters(deliverymanId: $id),
            businessModule: $businessModule,
            dataLimit: $this->paginationLimit(),
        );
        $this->decorateOrdersWithModule($orders);
        $balances = $this->deliverymanWalletService->getBalanceSummary(deliverymanId: (int) $id);

        return view('admin-views.delivery-man.earning-statement.earning', [
            'deliveryMan' => $this->deliveryManService->find(id: $id, relations: ['wallet']),
            'orders' => $orders,
            'totalEarn' => $balances['total_earn'],
            'withdrawalableBalance' => $balances['withdrawable_balance'],
            'businessModule' => $businessModule,
            'wholesaleAvailable' => $this->wholesaleModuleAvailable(),
        ]);
    }

    public function getOrderWiseEarningListByFilter(Request $request, $id): JsonResponse
    {
        $businessModule = $this->resolveOrderBusinessModule($request['business_module']);
        $orders = $this->orderService->getDeliverymanOrderHistory(
            deliverymanId: (int) $id,
            searchValue: $request['searchValue'],
            filters: $this->deliverymanOrderFilters(
                deliverymanId: $id,
                orderStatuses: (array) ($request['order_status'] ?? []),
                paymentStatuses: (array) ($request['payment_status'] ?? []),
            ),
            businessModule: $businessModule,
            dataLimit: $this->paginationLimit(),
        );
        $this->decorateOrdersWithModule($orders);
        $currentFilters = [
            'order_status' => $request['order_status'] ?? 'all',
            'payment_status' => $request['payment_status'] ?? 'all',
            'business_module' => $request['business_module'] ?? 'all',
        ];

        return response()->json([
            'view' => view('admin-views.delivery-man.earning-statement._table', [
                'orders' => $orders,
                'currentFilters' => $currentFilters,
                'wholesaleAvailable' => $this->wholesaleModuleAvailable(),
            ])->render(),
            'count' => count($orders),
        ]);
    }

    public function getOrderStatusHistory($order): View
    {
        $histories = $this->orderStatusHistoryService->getList(dataLimit: 'all', filters: ['order_id' => $order]);

        return view('admin-views.delivery-man.earning-statement._order-status-history', compact('histories'));
    }

    public function getRatingView(Request $request, $id): View|RedirectResponse
    {
        $deliveryMan = $this->deliveryManService->findBy(params: ['id' => $id, 'seller_id' => DeliveryManService::SELLER_ADMIN]);

        if ($deliveryMan === null) {
            ToastMagic::warning(translate('Invalid review'));

            return redirect()->route('admin.people.delivery-man.list');
        }

        $reviews = $this->reviewService->getList(
            searchValue: $request['searchValue'],
            dataLimit: 'all',
            relations: ['customer'],
            filters: $this->reviewFilters(deliverymanId: $id, request: $request),
            sortBy: ReviewService::SORT_RECENTLY_UPDATED,
        );

        return view('admin-views.delivery-man.rating', [
            'deliveryMan' => $deliveryMan,
            'reviews' => $reviews->paginate($this->paginationLimit()),
            ...$this->ratingBreakdown(reviews: $reviews),
        ]);
    }

    private function paginationLimit(): int
    {
        return (int) ($this->businessSettingService->getValueByType(type: WebConfigKey::PAGINATION_LIMIT) ?? DEFAULT_DATA_LIMIT);
    }

    private function sortKey(?string $sortBy): string
    {
        return self::SORT_KEYS[$sortBy] ?? DeliveryManService::SORT_LATEST;
    }

    /** @return array<string, mixed> */
    private function listFilters(?string $sortBy): array
    {
        $filters = ['seller_id' => DeliveryManService::SELLER_ADMIN];

        if ($this->sortKey(sortBy: $sortBy) === DeliveryManService::SORT_TOP_RATED) {
            $filters[DeliveryManService::FILTER_HAS_DELIVERED_ORDERS] = true;
        }

        return $filters;
    }

    /** @return array<string, mixed> */
    private function deliveryManData(Request $request): array
    {
        $countryCode = '+'.$request['country_code'];

        $data = [
            'f_name' => $request['f_name'],
            'l_name' => $request['l_name'],
            'address' => $request['address'],
            'email' => $request['email'],
            'country_code' => $countryCode,
            'phone' => str_replace($countryCode, '', $request['phone']),
            'full_phone' => $request['phone'],
            'identity_number' => $request['identity_number'],
            'identity_type' => $request['identity_type'],
        ];

        return empty($request['password']) ? $data : $data + ['password' => $request['password']];
    }

    /**
     * @param  array<int, string>  $orderStatuses
     * @param  array<int, string>  $paymentStatuses
     * @return array<string, mixed>
     */
    private function deliverymanOrderFilters(mixed $deliverymanId, array $orderStatuses = [], array $paymentStatuses = []): array
    {
        return array_filter([
            'delivery_man_id' => $deliverymanId,
            OrderService::FILTER_DELIVERYMAN_SELLER_ID => DeliveryManService::SELLER_ADMIN,
            'order_status' => $orderStatuses,
            'payment_status' => $paymentStatuses,
        ], static fn ($value) => $value !== []);
    }

    /** @return array<int, string> */
    private function splitStatuses(?string $statuses): array
    {
        return empty($statuses) ? [] : explode(',', $statuses);
    }

    /** @return array<string, mixed> */
    private function orderHistoryExportData(Request $request, ?Model $deliveryMan, Collection $orders, ?string $businessModule): array
    {
        $orders->each(static fn (Model $order) => $order->total_qty = $order->details->sum('qty'));
        $balances = $this->deliverymanWalletService->getBalanceSummary(deliverymanId: (int) $deliveryMan?->id);

        return [
            'delivery_man' => $deliveryMan,
            'orders' => $orders,
            'total_earn' => $balances['total_earn'],
            'withdrawable_balance' => $balances['withdrawable_balance'],
            'search' => $request['searchValue'],
            'type' => $request['type'],
            'totalOrders' => $this->orderService->countDeliverymanOrderHistory(
                deliverymanId: (int) $deliveryMan?->id,
                filters: $this->deliverymanOrderFilters(deliverymanId: $deliveryMan?->id),
                businessModule: $businessModule,
            ),
        ];
    }

    private function resolveOrderBusinessModule(mixed $requested): ?string
    {
        if (! $this->wholesaleModuleAvailable()) {
            return BusinessModule::RETAIL;
        }

        return in_array($requested, [BusinessModule::RETAIL, BusinessModule::WHOLESALE], true) ? $requested : null;
    }

    private function wholesaleModuleAvailable(): bool
    {
        return businessModuleManager()->isAvailableInPanel(key: BusinessModule::WHOLESALE);
    }

    private function decorateOrdersWithModule(iterable $orders): void
    {
        foreach ($orders as $order) {
            $order->module_label = $this->orderModuleLabel(module: $order->business_module);
            $order->details_route_name = $this->orderDetailsRouteName(module: $order->business_module);
        }
    }

    private function orderModuleLabel(string $module): string
    {
        return match ($module) {
            BusinessModule::WHOLESALE => translate('Wholesale'),
            BusinessModule::AUCTION => translate('Auction'),
            default => translate('Retail'),
        };
    }

    private function orderDetailsRouteName(string $module): string
    {
        return $module === BusinessModule::WHOLESALE ? 'admin.wholesale.orders.details' : 'admin.retail.orders.details';
    }

    /** @return array<string, mixed> */
    private function reviewFilters(mixed $deliverymanId, Request $request): array
    {
        return array_filter([
            'delivery_man_id' => $deliverymanId,
            'rating' => $request['rating'],
            ReviewService::FILTER_DATE_FROM => $request['from_date'],
            ReviewService::FILTER_DATE_TO => $request['to_date'],
        ], static fn ($value) => $value !== null && $value !== '');
    }

    /** @return array<string, mixed> */
    private function ratingBreakdown(Collection $reviews): array
    {
        $countsByRating = $reviews->countBy('rating');

        return [
            'total' => $reviews->count(),
            'averageRating' => (float) $reviews->avg('rating'),
            'one' => $countsByRating[1] ?? 0,
            'two' => $countsByRating[2] ?? 0,
            'three' => $countsByRating[3] ?? 0,
            'four' => $countsByRating[4] ?? 0,
            'five' => $countsByRating[5] ?? 0,
        ];
    }
}
