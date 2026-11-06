<?php

namespace App\Http\Controllers\Vendor;

use App\Enums\ExportFileNames\Admin\RefundRequest as RefundRequestExportFile;
use App\Events\RefundEvent;
use App\Exports\RefundRequestExport;
use App\Http\Controllers\BaseController;
use App\Http\Requests\Vendor\Order\RefundStatusUpdateRequest;
use App\Services\New\CustomerService;
use App\Services\New\OrderDetailService;
use App\Services\New\OrderDetailsRewardsService;
use App\Services\New\OrderService;
use App\Services\New\RefundRequestService;
use App\Services\New\RefundStatusService;
use App\Services\New\VendorService;
use Devrabiul\ToastMagic\Facades\ToastMagic;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class RefundController extends BaseController
{
    public function __construct(
        private readonly RefundRequestService $refundRequestService,
        private readonly CustomerService $customerService,
        private readonly OrderDetailService $orderDetailService,
        private readonly RefundStatusService $refundStatusService,
        private readonly OrderService $orderService,
        private readonly VendorService $vendorService,
        private readonly OrderDetailsRewardsService $orderDetailsRewardsService,
    ) {}

    public function index(?Request $request, ?string $type = null): View
    {
        $refundList = $this->refundRequestService->getList(
            searchValue: $request['search'],
            dataLimit: getWebConfig(name: 'pagination_limit'),
            relations: ['customer', 'product', 'orderDetails'],
            filters: $this->getListFilters(request: $request, status: $type, vendorId: auth('seller')->id()),
            sortBy: RefundRequestService::SORT_NEWEST_ID,
        );

        return view('vendor-views.refund.index', [
            'refundList' => $refundList,
            'searchValue' => $request['search'],
            'orderedProductNames' => $this->refundRequestService->getOrderedProductNames(refunds: $refundList->items()),
        ]);
    }

    public function getDetailsView(string|int $id): View|RedirectResponse
    {
        $refund = $this->findVendorRefund(
            id: $id,
            vendorId: auth('seller')->id(),
            relations: ['order.deliveryMan', 'product', 'orderDetails', 'refundStatus'],
        );

        if ($refund === null || $refund->orderDetails === null || $refund->order === null) {
            ToastMagic::error(translate('Not found'));

            return back();
        }

        $order = $refund->order;
        $refundDetails = $this->orderDetailService->getRefundDetails(orderDetail: $refund->orderDetails, order: $order);
        $productSource = $refund->product ?? ($this->refundRequestService->getOrderedProductDetails(refund: $refund) ?? []);

        return view('vendor-views.refund.details', [
            'refund' => $refund,
            'order' => $order,
            'subtotal' => $refundDetails['sub_total'],
            'couponDiscount' => $refundDetails['coupon_discount'],
            'referralDiscount' => $refundDetails['referral_discount'],
            'refundAmount' => $refund->amount,
            'isProductUnavailable' => $refund->product === null,
            'productName' => $productSource['name'] ?? null,
            'productThumbnail' => checkImageStatus(path: $productSource['thumbnail'] ?? null, storagePath: $productSource['thumbnail_storage_type'] ?? 'public'),
            'digitalFileDownloadPath' => $this->refundRequestService->getDigitalFileDownloadPath(refund: $refund),
            'statusChangeLimit' => RefundRequestService::STATUS_CHANGE_LIMIT,
        ]);
    }

    public function updateStatus(RefundStatusUpdateRequest $request): JsonResponse
    {
        $vendorId = auth('seller')->id();
        $refund = $this->findVendorRefund(id: $request['id'], vendorId: $vendorId);

        if ($refund === null) {
            return response()->json(['error' => translate('Not found')]);
        }

        $status = $request['refund_status'];

        if ($this->refundRequestService->hasReachedStatusChangeLimit(refund: $refund, status: $status)) {
            return response()->json(['error' => translate('You already changed').($status == RefundRequestService::STATUS_APPROVED ? 'approve' : 'reject').translate('Status two times').'!!']);
        }

        $customer = $this->customerService->find(id: $refund->customer_id);

        if ($customer === null) {
            return response()->json(['error' => translate('This account has been deleted').','.translate('You cannot modify the status').'!!']);
        }

        $loyaltyPoint = $this->orderDetailsRewardsService->getLoyaltyPointForOrderDetail(orderDetailId: $refund->order_details_id);

        if ($this->refundRequestService->hasInsufficientLoyaltyPoint(customerLoyaltyPoint: $customer->loyalty_point, refundLoyaltyPoint: $loyaltyPoint, status: $status)) {
            return response()->json(['error' => translate('Customer does not have sufficient loyalty points to take a refund for this order').'.']);
        }

        $blocker = $this->refundRequestService->getSellerStatusChangeBlocker(refund: $refund);

        if ($blocker === RefundRequestService::REASON_CHANGED_BY_ADMIN) {
            return response()->json(['error' => translate('Refunded status cannot be changed').'!! '.translate('Admin already changed the status').': '.$refund->status.'!!']);
        }

        if ($blocker === RefundRequestService::REASON_ALREADY_REFUNDED) {
            return response()->json(['message' => translate('Refunded status cannot be changed').'!!']);
        }

        $note = $status === RefundRequestService::STATUS_APPROVED ? $request['approved_note'] : $request['rejected_note'];

        $orderDetail = DB::transaction(function () use ($refund, $status, $note, $vendorId): ?Model {
            $orderDetail = $this->orderDetailService->update(
                id: $refund->order_details_id,
                data: ['refund_request' => $this->refundRequestService->getOrderDetailRefundFlag(status: $status)],
            );

            $this->refundStatusService->createBySeller(refundRequestId: $refund->id, sellerId: $vendorId, status: $status, note: $note);

            $this->refundRequestService->updateCountedStatusBySeller(refund: $refund, status: $status, note: $note);

            return $orderDetail;
        });

        $order = $this->orderService->findForSeller(id: $refund->order_id, sellerId: $vendorId);

        if ($order !== null) {
            event(new RefundEvent(status: $status, order: $order, refund: $refund, orderDetails: $orderDetail));
        }

        return response()->json(['message' => translate('Refund status updated').'!!']);
    }

    public function exportList(Request $request, string $status): BinaryFileResponse
    {
        $vendorId = auth('seller')->id();
        $refundList = $this->refundRequestService->getList(
            searchValue: $request['search'],
            dataLimit: 'all',
            relations: ['order.deliveryMan', 'orderDetails', 'customer'],
            filters: $this->getListFilters(request: $request, status: $status, vendorId: $vendorId),
            sortBy: RefundRequestService::SORT_NEWEST_ID,
        );

        return Excel::download(new RefundRequestExport([
            'data-from' => 'vendor',
            'vendor' => $this->vendorService->findBy(params: ['id' => $vendorId]),
            'refundList' => $refundList,
            'rows' => $this->refundRequestService->getExportRows(refunds: $refundList),
            'search' => $request['search'],
            'status' => $status,
            'from' => $request['from_date'],
            'to' => $request['to_date'],
            'filter_By' => $request->get('type', 'all'),
        ]), RefundRequestExportFile::EXPORT_XLSX);
    }

    private function getListFilters(Request $request, ?string $status, int $vendorId): array
    {
        return array_filter([
            'status' => $status,
            RefundRequestService::FILTER_DATE_FROM => $request['from_date'],
            RefundRequestService::FILTER_DATE_TO => $request['to_date'],
            RefundRequestService::FILTER_ORDER_SELLER_IS => RefundRequestService::SELLER_IS_SELLER,
            RefundRequestService::FILTER_ORDER_SELLER_ID => $vendorId,
        ], static fn ($value) => $value !== null && $value !== '');
    }

    private function findVendorRefund(mixed $id, int $vendorId, array $relations = []): ?Model
    {
        return $this->refundRequestService->findBy(
            params: [
                'id' => $id,
                RefundRequestService::FILTER_ORDER_SELLER_IS => RefundRequestService::SELLER_IS_SELLER,
                RefundRequestService::FILTER_ORDER_SELLER_ID => $vendorId,
            ],
            relations: $relations,
        );
    }
}
