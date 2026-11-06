<?php

namespace App\Services\New;

use App\Enums\BusinessModule;
use App\Models\RefundRequest;
use App\Services\BaseService;
use App\Traits\FileManagerTrait;
use Carbon\CarbonInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;

class RefundRequestService extends BaseService
{
    use FileManagerTrait {
        update as private updateFile;
        delete as private deleteFile;
    }

    public const STATUS_PENDING = 'pending';

    public const STATUS_APPROVED = 'approved';

    public const STATUS_REJECTED = 'rejected';

    public const STATUS_REFUNDED = 'refunded';

    public const CHANGED_BY_ADMIN = 'admin';

    public const CHANGED_BY_SELLER = 'seller';

    public const REASON_CHANGED_BY_ADMIN = 'changed_by_admin';

    public const REASON_ALREADY_REFUNDED = 'already_refunded';

    public const DATE_TYPE_THIS_YEAR = 'this_year';

    public const DATE_TYPE_THIS_MONTH = 'this_month';

    public const DATE_TYPE_THIS_WEEK = 'this_week';

    public const DATE_TYPE_TODAY = 'today';

    public const DATE_TYPE_CUSTOM = 'custom_date';

    public const SORT_OLDEST_ID = 'oldest-id';

    private const CUSTOM_DATE_DEFAULT_DAYS = 6;

    private const ORDER_DETAIL_REFUND_FLAGS = [
        self::STATUS_PENDING => 1,
        self::STATUS_APPROVED => 2,
        self::STATUS_REJECTED => 3,
    ];

    private const ORDER_DETAIL_REFUNDED_FLAG = 4;

    public const STATUS_CHANGE_LIMIT = 2;

    public const PAYMENT_METHOD_CUSTOMER_WALLET = 'customer_wallet';

    private const DIGITAL_PRODUCT_DIRECTORY = 'storage/app/public/product/digital-product/';

    public const IMAGE_DIRECTORY = 'refund/';

    public const IMAGE_FORMAT = 'webp';

    public const SELLER_IS_SELLER = 'seller';

    public const SELLER_IS_ADMIN = 'admin';

    public const FILTER_ORDER_SELLER_IS = 'order_seller_is';

    public const FILTER_ORDER_SELLER_ID = 'order_seller_id';

    public const FILTER_DATE_FROM = 'date_from';

    public const FILTER_DATE_TO = 'date_to';

    public const SORT_LATEST = 'latest';

    public const SORT_NEWEST_ID = 'newest-id';

    public function __construct(RefundRequest $refundRequest)
    {
        parent::__construct($refundRequest);
    }

    protected function businessModule(): string
    {
        return BusinessModule::RETAIL;
    }

    protected function query(): Builder
    {
        $module = $this->businessModule();

        return parent::query()->where(function (Builder $refund) use ($module): void {
            $refund->whereHas('order', fn (Builder $order) => $order->businessModule($module));

            if ($module === BusinessModule::RETAIL) {
                $refund->orWhereDoesntHave('order');
            }
        });
    }

    public function getList(
        ?string $searchValue = null,
        int|string $dataLimit = DEFAULT_DATA_LIMIT,
        ?int $page = null,
        array $relations = [],
        array $withCount = [],
        array $filters = [],
        ?string $sortBy = null,
    ): Collection|LengthAwarePaginator {
        $query = $this->query()
            ->with($relations)
            ->withCount($withCount)
            ->when($searchValue, fn (Builder $builder) => $this->applySearch($builder, $searchValue));

        $filters = $this->applyKeyedFilters($query, $filters);

        match ($sortBy) {
            self::SORT_NEWEST_ID => $query->orderByDesc('id'),
            self::SORT_OLDEST_ID => $query->orderBy('id'),
            default => $query->latest(),
        };

        $this->applyFilters($query, $filters);

        return $dataLimit === 'all'
            ? $query->get()
            : $query->paginate(perPage: (int) $dataLimit, page: $page)
                ->appends($this->listQueryString($searchValue, $sortBy, $filters));
    }

    private function applySearch(Builder $query, string $searchValue): Builder
    {
        $keywords = array_filter(explode(' ', $searchValue));

        foreach ($keywords as $keyword) {
            $query->where(fn (Builder $builder) => $builder
                ->where('order_id', 'like', "%{$keyword}%")
                ->orWhere('id', 'like', "%{$keyword}%")
                ->orWhereHas('customer', fn (Builder $customer) => $customer
                    ->where('f_name', 'like', "%{$keyword}%")
                    ->orWhere('l_name', 'like', "%{$keyword}%"))
                ->orWhereHas('product', fn (Builder $product) => $product->where('name', 'like', "%{$keyword}%")));
        }

        return $query;
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    private function applyKeyedFilters(Builder $query, array $filters): array
    {
        $sellerIs = $filters[self::FILTER_ORDER_SELLER_IS] ?? null;
        $sellerId = $filters[self::FILTER_ORDER_SELLER_ID] ?? null;

        if ($sellerIs !== null) {
            $query->whereHas('order', fn (Builder $order) => $order
                ->where('seller_is', $sellerIs)
                ->when($sellerIs === self::SELLER_IS_SELLER && $sellerId !== null, fn (Builder $scoped) => $scoped->where('seller_id', $sellerId)));
        }

        $from = $filters[self::FILTER_DATE_FROM] ?? null;
        $to = $filters[self::FILTER_DATE_TO] ?? null;

        if ($from !== null && $to !== null) {
            $query->whereBetween('created_at', [$from.' 00:00:00', $to.' 23:59:59']);
        } elseif ($from !== null) {
            $query->whereDate('created_at', '>=', $from);
        } elseif ($to !== null) {
            $query->whereDate('created_at', '<=', $to);
        }

        unset(
            $filters[self::FILTER_ORDER_SELLER_IS],
            $filters[self::FILTER_ORDER_SELLER_ID],
            $filters[self::FILTER_DATE_FROM],
            $filters[self::FILTER_DATE_TO],
        );

        return $filters;
    }

    public function getListByOrderDetail(
        int|string $orderDetailId,
        ?int $customerId = null,
        ?int $sellerId = null,
        array $relations = [],
    ): Collection {
        $query = $this->query()
            ->with($relations)
            ->where('order_details_id', $orderDetailId)
            ->when($customerId !== null, fn (Builder $builder) => $builder->where('customer_id', $customerId))
            ->orderBy('id');

        if ($sellerId !== null) {
            $this->applyKeyedFilters($query, [
                self::FILTER_ORDER_SELLER_IS => self::SELLER_IS_SELLER,
                self::FILTER_ORDER_SELLER_ID => $sellerId,
            ]);
        }

        return $query->get();
    }

    public function getListForSeller(
        int $sellerId,
        ?string $searchValue = null,
        ?string $dateType = null,
        ?string $startDate = null,
        ?string $endDate = null,
        array $relations = [],
        ?string $status = null,
        int|string $dataLimit = 'all',
        ?int $page = null,
    ): Collection|LengthAwarePaginator {
        $query = $this->query()
            ->with($relations)
            ->when($searchValue, function (Builder $builder) use ($searchValue): void {
                foreach (explode(' ', $searchValue) as $keyword) {
                    $builder->where('order_id', 'like', "%{$keyword}%");
                }
            })
            ->when($status !== null, fn (Builder $builder) => $builder->where('status', $status));

        $this->applyKeyedFilters($query, [
            self::FILTER_ORDER_SELLER_IS => self::SELLER_IS_SELLER,
            self::FILTER_ORDER_SELLER_ID => $sellerId,
        ]);

        $this->applyDateType(query: $query, dateType: $dateType, startDate: $startDate, endDate: $endDate);

        $query->latest();

        return $dataLimit === 'all'
            ? $query->get()
            : $query->paginate(perPage: (int) $dataLimit, page: $page);
    }

    private function applyDateType(Builder $query, ?string $dateType, ?string $startDate, ?string $endDate): void
    {
        match ($dateType) {
            self::DATE_TYPE_THIS_YEAR => $query->whereYear('created_at', date('Y')),
            self::DATE_TYPE_THIS_MONTH => $query->whereMonth('created_at', date('m'))->whereYear('created_at', date('Y')),
            self::DATE_TYPE_THIS_WEEK => $query->whereBetween('created_at', [Carbon::now()->startOfWeek(), Carbon::now()->endOfWeek()]),
            self::DATE_TYPE_TODAY => $query->whereBetween('created_at', [Carbon::now()->startOfDay(), Carbon::now()->endOfDay()]),
            self::DATE_TYPE_CUSTOM => $query->whereBetween('created_at', $this->resolveCustomDateRange(startDate: $startDate, endDate: $endDate)),
            default => null,
        };
    }

    /** @return array{0: Carbon, 1: Carbon} */
    private function resolveCustomDateRange(?string $startDate, ?string $endDate): array
    {
        if ($startDate === null || $endDate === null) {
            return [now()->subDays(self::CUSTOM_DATE_DEFAULT_DAYS)->startOfDay(), now()->endOfDay()];
        }

        return [Carbon::parse($startDate)->startOfDay(), Carbon::parse($endDate)->endOfDay()];
    }

    public function hasRefundWindowExpired(?CarbonInterface $refundStartedAt): bool
    {
        return $refundStartedAt !== null
            && $refundStartedAt->diffInDays(Carbon::now()) > getWebConfig(name: 'refund_day_limit');
    }

    public function getSellerStatusChangeBlocker(Model $refund): ?string
    {
        return match (true) {
            $refund->change_by === self::CHANGED_BY_ADMIN => self::REASON_CHANGED_BY_ADMIN,
            $refund->status === self::STATUS_REFUNDED => self::REASON_ALREADY_REFUNDED,
            default => null,
        };
    }

    public function getOrderDetailRefundFlag(string $status): ?int
    {
        return self::ORDER_DETAIL_REFUND_FLAGS[$status] ?? null;
    }

    public function updateStatusBySeller(mixed $id, string $status, ?string $note): ?Model
    {
        $data = ['status' => $status, 'change_by' => self::CHANGED_BY_SELLER];

        if ($status === self::STATUS_APPROVED) {
            $data['approved_note'] = $note;
        } elseif ($status === self::STATUS_REJECTED) {
            $data['rejected_note'] = $note;
        }

        return $this->update(id: $id, data: $data);
    }

    public function updateCountedStatusBySeller(Model $refund, string $status, ?string $note): ?Model
    {
        $data = ['status' => $status, 'change_by' => self::CHANGED_BY_SELLER];

        if ($status === self::STATUS_APPROVED) {
            $data['approved_note'] = $note;
            $data['approved_count'] = $refund->approved_count + 1;
        } elseif ($status === self::STATUS_REJECTED) {
            $data['rejected_note'] = $note;
            $data['denied_count'] = $refund->denied_count + 1;
        }

        return $this->update(id: $refund->id, data: $data);
    }

    public function updateStatusByAdmin(Model $refund, string $status, ?string $approvedNote, ?string $rejectedNote, ?string $paymentInfo): ?Model
    {
        $data = ['status' => $status, 'change_by' => self::CHANGED_BY_ADMIN];

        if ($status === self::STATUS_APPROVED) {
            $data['approved_note'] = $approvedNote;
        } elseif ($status === self::STATUS_REJECTED) {
            $data['rejected_note'] = $rejectedNote;
        } elseif ($status === self::STATUS_REFUNDED) {
            $data['payment_info'] = $paymentInfo;

            if (empty($refund->approved_note)) {
                $data['approved_note'] = $approvedNote;
            }
        }

        return $this->update(id: $refund->id, data: $data);
    }

    public function getStatusChangeNote(string $status, ?string $approvedNote, ?string $rejectedNote, ?string $paymentInfo): ?string
    {
        return match ($status) {
            self::STATUS_APPROVED => $approvedNote,
            self::STATUS_REJECTED => $rejectedNote,
            self::STATUS_REFUNDED => $paymentInfo,
            default => null,
        };
    }

    public function getOrderDetailRefundFlagIncludingRefunded(string $status): ?int
    {
        return $status === self::STATUS_REFUNDED
            ? self::ORDER_DETAIL_REFUNDED_FLAG
            : $this->getOrderDetailRefundFlag(status: $status);
    }

    public function hasEmptyApprovedNote(mixed $id): bool
    {
        return empty($this->query()->where('id', $id)->value('approved_note'));
    }

    public function isRefunded(Model $refund): bool
    {
        return $refund->status === self::STATUS_REFUNDED;
    }

    public function needsApprovalBeforeRefund(Model $refund): bool
    {
        return $refund->status !== self::STATUS_APPROVED;
    }

    public function hasReachedStatusChangeLimit(Model $refund, string $status): bool
    {
        return match ($status) {
            self::STATUS_APPROVED => $refund->approved_count >= self::STATUS_CHANGE_LIMIT,
            self::STATUS_REJECTED => $refund->denied_count >= self::STATUS_CHANGE_LIMIT,
            default => false,
        };
    }

    public function hasInsufficientLoyaltyPoint(float|int|string|null $customerLoyaltyPoint, int $refundLoyaltyPoint, string $status): bool
    {
        return $refundLoyaltyPoint > 0
            && (float) $customerLoyaltyPoint < $refundLoyaltyPoint
            && in_array($status, [self::STATUS_APPROVED, self::STATUS_REFUNDED], true);
    }

    public function shouldCreditCustomerWallet(?string $paymentMethod): bool
    {
        return getWebConfig(name: 'wallet_status') == 1
            && getWebConfig(name: 'wallet_add_refund') == 1
            && $paymentMethod === self::PAYMENT_METHOD_CUSTOMER_WALLET;
    }

    public function getOrderedProductDetails(Model $refund): ?array
    {
        $productDetails = $refund->orderDetails?->product_details;

        if (is_array($productDetails)) {
            return $productDetails;
        }

        return is_string($productDetails) ? json_decode($productDetails, true) : null;
    }

    public function getExportRows(iterable $refunds): array
    {
        $rows = [];

        foreach ($refunds as $refund) {
            $productDetails = $this->getOrderedProductDetails(refund: $refund);
            $unitPrice = $productDetails['unit_price'] ?? null;

            $rows[$refund->id] = [
                'product_name' => $productDetails['name'] ?? null,
                'product_amount' => $unitPrice === null ? null : $unitPrice - getProductDiscount(product: $productDetails, price: $unitPrice),
                'order_date' => $refund->order?->created_at,
            ];
        }

        return $rows;
    }

    public function getOrderedProductNames(iterable $refunds): array
    {
        $names = [];

        foreach ($refunds as $refund) {
            $names[$refund->id] = $this->getOrderedProductDetails(refund: $refund)['name'] ?? null;
        }

        return $names;
    }

    public function getDigitalFileDownloadPath(Model $refund): ?string
    {
        $fileName = $refund->orderDetails?->digital_file_after_sell;

        if (empty($fileName)) {
            return null;
        }

        $downloadPath = dynamicStorage(path: self::DIGITAL_PRODUCT_DIRECTORY.$fileName);

        return file_exists($downloadPath) ? $downloadPath : null;
    }

    public function find(mixed $id, array $relations = []): ?Model
    {
        return $this->query()->with($relations)->find($id);
    }

    public function findBy(array $params, array $relations = []): ?Model
    {
        $query = $this->query()->with($relations);

        $params = $this->applyKeyedFilters($query, $params);

        $this->applyFilters($query, $params);

        return $query->first();
    }

    public function create(array $data): ?Model
    {
        return $this->query()->create([
            'order_details_id' => $data['order_details_id'],
            'customer_id' => $data['customer_id'],
            'status' => $data['status'],
            'amount' => $data['amount'],
            'product_id' => $data['product_id'],
            'order_id' => $data['order_id'],
            'refund_reason' => $data['refund_reason'],
            'images' => empty($data['images']) ? null : $data['images'],
        ]);
    }

    /**
     * @param  array<int, UploadedFile>  $images
     * @return array<int, array{image_name: string, storage: string}>
     */
    public function uploadImages(array $images): array
    {
        $storedImages = [];

        foreach ($images as $image) {
            $storedImages[] = [
                'image_name' => $this->upload(dir: self::IMAGE_DIRECTORY, format: self::IMAGE_FORMAT, image: $image),
                'storage' => getWebConfig(name: 'storage_connection_type') ?? 'public',
            ];
        }

        return $storedImages;
    }

    public function update(mixed $id, array $data): ?Model
    {
        $refundRequest = $this->query()->find($id);

        if ($refundRequest === null) {
            return null;
        }

        $refundRequest->update($data);

        return $refundRequest->refresh();
    }

    public function updateStatus(mixed $id, bool $status): bool
    {
        return (bool) $this->query()->where('id', $id)->update(['status' => $status]);
    }

    public function updateWhere(array $params, array $data): int
    {
        return $this->query()->where($params)->update($data);
    }

    /** @return array<string, int> */
    public function getStatusCounts(?int $sellerId = null): array
    {
        return $this->query()
            ->when(
                $sellerId !== null,
                fn (Builder $builder) => $builder->whereHas('order', fn (Builder $order) => $order
                    ->where('seller_is', self::SELLER_IS_SELLER)
                    ->where('seller_id', $sellerId)),
            )
            ->select('status')
            ->selectRaw('count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status')
            ->map(fn ($total) => (int) $total)
            ->toArray();
    }

    public function count(array $filters = []): int
    {
        $query = $this->query();

        $this->applyFilters($query, $filters);

        return $query->count();
    }
}
