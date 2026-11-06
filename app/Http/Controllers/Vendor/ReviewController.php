<?php

namespace App\Http\Controllers\Vendor;

use App\Exports\CustomerReviewListExport;
use App\Http\Controllers\BaseController;
use App\Services\New\CustomerService;
use App\Services\New\ProductRetailService;
use App\Services\New\ReviewReplyService;
use App\Services\New\ReviewService;
use App\Services\New\VendorService;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ReviewController extends BaseController
{
    private const LIST_RELATIONS = ['product.seller.shop', 'customer', 'reply'];

    private const EXPORT_RELATIONS = ['product', 'customer'];

    private const PRODUCT_PICKER_RELATIONS = [];

    private const CUSTOMER_OPTION_ALL = 'all';

    private const WALKING_CUSTOMER_ID = 0;

    private const WALKING_CUSTOMER_TEXT = 'Walk-In Customer';

    private const EXPORT_ALL_PRODUCTS = 'all_products';

    private const EXPORT_ALL_CUSTOMERS = 'all_customers';

    private const EXPORT_FILE_NAME = 'Product-Review-List.xlsx';

    public function __construct(
        private readonly ReviewService $reviewService,
        private readonly ProductRetailService $productService,
        private readonly CustomerService $customerService,
        private readonly VendorService $vendorService,
        private readonly ReviewReplyService $reviewReplyService,
    ) {}

    public function index(?Request $request, ?string $type = null): View|Collection|LengthAwarePaginator|null|callable|RedirectResponse
    {
        $vendorId = auth('seller')->id();
        $customerId = $request['customer_id'];

        return view('vendor-views.reviews.index', [
            'reviews' => $this->reviewService->getList(
                searchValue: $request['searchValue'],
                dataLimit: getWebConfig('pagination_limit'),
                page: $request['page'],
                relations: self::LIST_RELATIONS,
                filters: $this->listFilters(request: $request, vendorId: $vendorId),
                sortBy: ReviewService::SORT_NEWEST_ID,
            ),
            'products' => $this->productService->getList(
                searchValue: $request['searchValue'],
                dataLimit: getWebConfig('pagination_limit'),
                page: $request['page'],
                relations: self::PRODUCT_PICKER_RELATIONS,
                filters: [
                    'added_by' => ProductRetailService::ADDED_BY_SELLER,
                    'seller_id' => $vendorId,
                    'has_reviews' => true,
                ],
            ),
            'product' => $this->productService->find(id: $request['product_id']),
            'customer' => $this->selectedCustomer($customerId),
            'from' => $request['from'],
            'to' => $request['to'],
            'customer_id' => $customerId,
            'product_id' => $request['product_id'],
            'status' => $request['status'],
            'searchValue' => $request['searchValue'],
            'customers' => $this->customerOptions(searchValue: $request['searchValue']),
        ]);
    }

    public function updateStatus(string|int $id, string|int $status): JsonResponse
    {
        $this->reviewService->updateStatus(id: $id, status: (bool) $status);

        return response()->json([
            'status' => 1,
            'message' => translate('Review status updated successfully'),
        ]);
    }

    public function exportList(Request $request): BinaryFileResponse|RedirectResponse
    {
        $vendorId = auth('seller')->id();

        $data = [
            'data-from' => 'vendor',
            'vendor' => $this->vendorService->find(id: $vendorId),
            'reviews' => $this->reviewService->getList(
                searchValue: $request['searchValue'],
                dataLimit: getWebConfig('pagination_limit'),
                page: $request['page'],
                relations: self::EXPORT_RELATIONS,
                filters: $this->listFilters(request: $request, vendorId: $vendorId),
                sortBy: ReviewService::SORT_NEWEST_ID,
            ),
            'product_name' => $request->has('product_id')
                ? $this->productService->find(id: $request['product_id'])['name']
                : self::EXPORT_ALL_PRODUCTS,
            'customer_name' => $request->has('customer_id')
                ? $this->customerService->find(id: $request['customer_id'])
                : self::EXPORT_ALL_CUSTOMERS,
            'from' => $request['from'],
            'to' => $request['to'],
            'status' => $request['status'],
            'key' => $request['searchValue'],
        ];

        return Excel::download(new CustomerReviewListExport($data), self::EXPORT_FILE_NAME);
    }

    public function addReviewReply(Request $request): RedirectResponse
    {
        $this->reviewReplyService->updateBy(
            params: [
                'review_id' => $request['review_id'],
                'added_by' => ReviewReplyService::ADDED_BY_SELLER,
                'added_by_id' => auth('seller')->id(),
            ],
            data: ['reply_text' => $request['reply_text']],
        );

        return back();
    }

    /** @return array<string, mixed> */
    private function listFilters(?Request $request, string|int $vendorId): array
    {
        $searchValue = $request['searchValue'];
        $customerId = $request['customer_id'];

        $filters = [
            'delivery_man_id' => null,
            ReviewService::FILTER_INCLUDE_INACTIVE => true,
            ReviewService::FILTER_PRODUCT_SELLER_ID => $vendorId,
        ];

        if ($request['product_id']) {
            $filters['product_id'] = $request['product_id'];
        }

        if ($customerId && $customerId !== self::CUSTOMER_OPTION_ALL) {
            $filters['customer_id'] = $customerId;
        }

        if ($request['status'] !== null && $request['status'] !== '') {
            $filters['status'] = $request['status'];
        }

        if ($request['from']) {
            $filters[ReviewService::FILTER_DATE_FROM] = $request['from'];
        }

        if ($request['to']) {
            $filters[ReviewService::FILTER_DATE_TO] = $request['to'];
        }

        if ($searchValue) {
            $filters[ReviewService::FILTER_MATCHING_PRODUCT_IDS] = $this->productService->getIds(
                filters: ['added_by' => ProductRetailService::ADDED_BY_SELLER, 'seller_id' => $vendorId],
                searchValue: $searchValue,
            );
            $filters[ReviewService::FILTER_MATCHING_CUSTOMER_IDS] = $this->customerService->getIdsMatchingAnyNameWord(
                searchValue: $searchValue,
            );
        }

        return $filters;
    }

    private function selectedCustomer(mixed $customerId): mixed
    {
        return $customerId && $customerId !== self::CUSTOMER_OPTION_ALL
            ? $this->customerService->find(id: $customerId)
            : self::CUSTOMER_OPTION_ALL;
    }

    /** @return array<int, array<string, mixed>> */
    private function customerOptions(?string $searchValue): array
    {
        return $this->customerService->getNameOptions(searchValue: $searchValue)
            ->filter(fn ($customer): bool => $customer['id'] != self::WALKING_CUSTOMER_ID
                && $customer['text'] !== self::WALKING_CUSTOMER_TEXT)
            ->values()
            ->toArray();
    }
}
