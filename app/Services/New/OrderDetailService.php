<?php

namespace App\Services\New;

use App\Enums\BusinessModule;
use App\Models\OrderDetail;
use App\Services\BaseService;
use Carbon\Carbon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use Modules\TaxModule\app\Traits\VatTaxManagement;

class OrderDetailService extends BaseService
{
    use VatTaxManagement;

    public const PRODUCT_TYPE_PHYSICAL = 'physical';

    public const PRODUCT_TYPE_DIGITAL = 'digital';

    public const DISCOUNT_TYPE_ON_PRODUCT = 'discount_on_product';

    public const DELIVERY_STATUS_DELIVERED = 'delivered';

    public const PAYMENT_STATUS_PAID = 'paid';

    public const DIGITAL_FILE_DIRECTORY = 'product/digital-product/';

    private const REFUND_STATUS_APPROVED = 'approved';

    private const SELLER_IS_SELLER = 'seller';

    private const FILE_TYPE_FILE = 'file';

    private const DEFAULT_TAX_MODEL = 'exclude';

    private const CACHE_TYPE = 'order_details';

    private const DELIVERY_STATUS_PENDING = 'pending';

    private const PAYMENT_STATUS_UNPAID = 'unpaid';

    private const DIGITAL_READY_PRODUCT = 'ready_product';

    private const DIGITAL_READY_AFTER_SELL = 'ready_after_sell';

    private const ADDED_BY_ADMIN = 'admin';

    private const IN_HOUSE_SELLER_ID = 1;

    private const DIGITAL_FILE_TYPES = [
        'jpg' => 'image',
        'jpeg' => 'image',
        'png' => 'image',
        'gif' => 'image',
        'pdf' => 'pdf',
        'zip' => 'zip',
    ];

    private const SELLER_SECRET_ATTRIBUTES = [
        'password', 'auth_token', 'remember_token', 'cm_firebase_token', 'cm_firebase_token_web',
        'bank_name', 'branch', 'account_no', 'holder_name',
    ];

    private const DEFAULT_DIGITAL_STORAGE = 'public';

    private const TAX_MODEL_INCLUDE = 'include';

    private const TAX_MODEL_EXCLUDE = 'exclude';

    private const PRODUCT_SNAPSHOT_EXCLUDED_KEYS = ['is_shop_temporary_close', 'meta_image_full_url', 'reviews', 'order_details', 'order_delivered', 'seo_info'];

    public function __construct(OrderDetail $orderDetail)
    {
        parent::__construct($orderDetail);
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
        $query = $this->query()->with($relations)->withCount($withCount);

        $this->applyFilters($query, $filters);

        return $dataLimit === 'all'
            ? $query->get()
            : $query->paginate(perPage: (int) $dataLimit, page: $page)
                ->appends($this->listQueryString($searchValue, $sortBy, $filters));
    }

    public function find(mixed $id, array $relations = []): ?Model
    {
        return $this->query()->with($relations)->find($id);
    }

    public function findBy(array $params, array $relations = []): ?Model
    {
        return $this->query()->with($relations)->where($params)->first();
    }

    public function create(array $data): ?Model
    {
        $record = $this->query()->create($data);
        cacheRemoveByType(type: self::CACHE_TYPE);

        return $record;
    }

    public function update(mixed $id, array $data): ?Model
    {
        $record = $this->query()->find($id);

        if ($record === null) {
            return null;
        }

        $record->update($data);
        cacheRemoveByType(type: self::CACHE_TYPE);

        return $record->refresh();
    }

    public function updateWhere(array $params, array $data): int
    {
        $updated = $this->query()->where($params)->update($data);
        cacheRemoveByType(type: self::CACHE_TYPE);

        return $updated;
    }

    public function exists(array $params): bool
    {
        return $this->query()->where($params)->exists();
    }

    public function count(array $filters = []): int
    {
        $query = $this->query();

        $this->applyFilters($query, $filters);

        return $query->count();
    }

    public function getLatestOrderId(): ?int
    {
        return $this->query()->orderBy('order_id', 'desc')->first()?->order_id;
    }

    /** @return array<string, mixed> */
    public function buildProductSnapshot(array $product, ?array $productDigitalVariations, ?Model $orderedDigitalVariation, ?Model $orderedVariationStorage): array
    {
        foreach (self::PRODUCT_SNAPSHOT_EXCLUDED_KEYS as $key) {
            unset($product[$key]);
        }

        if (! isset($product['digital_variation'])) {
            $product['digital_variation'] = $productDigitalVariations ?? [];
        }

        if ($product['digital_product_type'] == self::DIGITAL_READY_PRODUCT && $orderedDigitalVariation) {
            $product['digital_file_ready'] = $orderedDigitalVariation['file'];
            $product['storage_path'] = $orderedVariationStorage ? $orderedVariationStorage['value'] : self::DEFAULT_DIGITAL_STORAGE;
        } elseif ($product['digital_product_type'] == self::DIGITAL_READY_PRODUCT && ! empty($product['digital_file_ready'])) {
            $product['storage_path'] = $product['digital_file_ready_storage_type'] ?? self::DEFAULT_DIGITAL_STORAGE;
        }

        return $product;
    }

    /** @return array{id: int, final_amount: float|int} */
    public function createPlacedOrderDetail(int $orderId, object|array $cartItem, array $productSnapshot, float|int $appliedTaxAmount): array
    {
        $taxConfig = self::getTaxSystemType();
        $productDiscount = getProductPriceByType(product: $productSnapshot, type: 'discounted_amount', result: 'value', price: $cartItem['price']);

        $orderDetailId = $this->query()->insertGetId([
            'order_id' => $orderId,
            'product_id' => $cartItem['product_id'],
            'seller_id' => $cartItem['seller_id'],
            'product_details' => json_encode($this->removeSellerSecrets(productSnapshot: $productSnapshot)),
            'qty' => $cartItem['quantity'],
            'price' => $cartItem['price'],
            'discount' => $productDiscount * $cartItem['quantity'],
            'discount_type' => self::DISCOUNT_TYPE_ON_PRODUCT,
            'variant' => $cartItem['variant'],
            'variation' => $cartItem['variations'],
            'delivery_status' => self::DELIVERY_STATUS_PENDING,
            'shipping_method_id' => null,
            'payment_status' => self::PAYMENT_STATUS_UNPAID,
            'created_at' => now(),
            'updated_at' => now(),
            'tax' => $appliedTaxAmount,
            'tax_model' => $taxConfig['is_included'] ? self::TAX_MODEL_INCLUDE : self::TAX_MODEL_EXCLUDE,
        ]);

        return [
            'id' => $orderDetailId,
            'final_amount' => ($cartItem['price'] - $productDiscount) * $cartItem['quantity'],
        ];
    }

    /** @return array<int|string, array{serial: int, product: mixed, is_product_unavailable: bool, digital_product_type: mixed, total_price: mixed, digital_file_url: string, digital_file_name: string, digital_file_type: string, is_in_house_upload: bool}> */
    public function getDisplayRows(iterable $details): array
    {
        $rows = [];
        $serial = 0;

        foreach ($details as $detail) {
            $productSnapshot = json_decode($detail->product_details, true);
            $product = $detail->productAllStatus ?? $productSnapshot;
            $digitalProductType = is_array($product)
                ? ($product['digital_product_type'] ?? null)
                : $product?->retailDetail?->digital_product_type;
            $digitalFile = $detail->digital_file_after_sell_full_url;
            $digitalFileName = '';
            $digitalFileType = self::FILE_TYPE_FILE;

            if ($digitalProductType == self::DIGITAL_READY_AFTER_SELL && isset($digitalFile['key'])) {
                $digitalFileName = $digitalFile['key'];
                $digitalFileType = self::DIGITAL_FILE_TYPES[strtolower(pathinfo($digitalFileName, PATHINFO_EXTENSION))] ?? self::FILE_TYPE_FILE;
            }

            $rows[$detail->id] = [
                'serial' => $product ? ++$serial : $serial,
                'product' => $product,
                'is_product_unavailable' => $detail->productAllStatus === null,
                'digital_product_type' => $digitalProductType,
                'total_price' => ($detail['price'] * $detail['qty']) - $detail['discount'],
                'digital_file_url' => $digitalFile && isset($digitalFile['path']) ? $digitalFile['path'] : '',
                'digital_file_name' => $digitalFileName,
                'digital_file_type' => $digitalFileType,
                'is_in_house_upload' => ($productSnapshot['added_by'] ?? null) == self::ADDED_BY_ADMIN
                    && $detail->seller_id == self::IN_HOUSE_SELLER_ID,
            ];
        }

        return $rows;
    }

    public function hasPhysicalProduct(int|string $orderId): bool
    {
        return $this->query()
            ->where('order_id', $orderId)
            ->whereHas('product', fn (Builder $product) => $product->where('product_type', self::PRODUCT_TYPE_PHYSICAL))
            ->exists();
    }

    /** @return array{product_price: mixed, product_discount: mixed, tax: mixed, sub_total: int|float, coupon_discount: int|float, referral_discount: int|float, total_refundable_amount: int|float} */
    public function getRefundDetails(Model $orderDetail, Model $order): array
    {
        $totalProductPrice = 0;
        foreach ($this->getOrderLines(orderId: $order->id) as $line) {
            $totalProductPrice += ($line->qty * $line->price) + $line->tax - $line->discount;
        }
        $subtotal = ($orderDetail->price * $orderDetail->qty) - $orderDetail->discount + $orderDetail->tax;

        $couponDiscount = $totalProductPrice > 0 ? (($order->discount_amount * $subtotal) / $totalProductPrice) : 0;
        $referralDiscount = $this->getReferralDiscountAmount(orderDetail: $orderDetail, order: $order);

        return [
            'product_price' => $orderDetail->price,
            'product_total_price' => $orderDetail->price * $orderDetail->qty,
            'product_discount' => $orderDetail->discount,
            'tax' => $orderDetail->tax,
            'sub_total' => $subtotal,
            'coupon_discount' => $couponDiscount,
            'referral_discount' => $referralDiscount,
            'total_refundable_amount' => $subtotal - $couponDiscount - $referralDiscount,
        ];
    }

    public function getReferralDiscountAmount(Model $orderDetail, Model $order): int|float
    {
        $orderLinesTotal = 0;
        foreach ($this->getOrderLines(orderId: $order->id) as $line) {
            $orderLinesTotal += $this->getDiscountedLineTotal(line: $line);
        }

        $rate = 0;
        if ($orderLinesTotal > 0) {
            $rate = ($this->getDiscountedLineTotal(line: $orderDetail) * 100) / $orderLinesTotal;
        }
        $orderReferralDiscount = $order->refer_and_earn_discount ?? 0;

        return $orderReferralDiscount > 0 ? ($rate * $orderReferralDiscount) / 100 : 0;
    }

    private function getDiscountedLineTotal(Model $line): int|float
    {
        return max(0, ($line['price'] * $line['qty']) - $line['discount']);
    }

    public function getOwnerOrderLines(int|string $orderId, int|string $customerId, bool $isGuest, array $relations = []): Collection
    {
        return $this->query()
            ->with($relations)
            ->whereHas('order', fn (Builder $order) => $order->businessModule($this->businessModule())->where([
                'customer_id' => $customerId,
                'is_guest' => $isGuest ? 1 : '0',
            ]))
            ->where(['order_id' => $orderId])
            ->get();
    }

    public function findForCustomerOwner(int|string $id, int|string $customerId, bool $isGuest, array $relations = []): ?Model
    {
        return $this->query()
            ->with($relations)
            ->whereHas('order', fn (Builder $order) => $order->businessModule($this->businessModule())->where([
                'customer_id' => $customerId,
                'is_guest' => $isGuest ? 1 : '0',
            ]))
            ->where('id', $id)
            ->first();
    }

    public function findForSeller(int|string $id, int $sellerId, array $relations = []): ?Model
    {
        return $this->query()
            ->with($relations)
            ->whereHas('order', fn (Builder $order) => $order->businessModule($this->businessModule())->where([
                'seller_is' => self::SELLER_IS_SELLER,
                'seller_id' => $sellerId,
            ]))
            ->where('id', $id)
            ->first();
    }

    protected function businessModule(): string
    {
        return BusinessModule::RETAIL;
    }

    public function getSellerOrderLines(int|string $orderId, int $sellerId, array $relations = []): Collection
    {
        return $this->query()
            ->with($relations)
            ->whereHas('order', fn (Builder $order) => $order->businessModule($this->businessModule())->where([
                'seller_is' => self::SELLER_IS_SELLER,
                'seller_id' => $sellerId,
            ]))
            ->where(['seller_id' => $sellerId, 'order_id' => $orderId])
            ->get();
    }

    public function markDeliveredForOrder(int|string $orderId): int
    {
        return $this->updateWhere(
            params: ['order_id' => $orderId],
            data: ['delivery_status' => self::DELIVERY_STATUS_DELIVERED, 'payment_status' => self::PAYMENT_STATUS_PAID],
        );
    }

    public function startRefundWindowForOrder(int|string $orderId): int
    {
        $updated = $this->query()
            ->where('order_id', $orderId)
            ->whereNull('refund_started_at')
            ->update(['refund_started_at' => now()]);
        cacheRemoveByType(type: self::CACHE_TYPE);

        return $updated;
    }

    public function isRefundWindowExpired(Model $orderDetail, mixed $refundDayLimit): bool
    {
        return ! is_null($orderDetail->refund_started_at)
            && $orderDetail->refund_started_at->diffInDays(Carbon::now()) > $refundDayLimit;
    }

    /** @return array{current_stock: mixed, current_price: mixed} */
    public function getCurrentStockAndPrice(object|array $orderDetail, mixed $activeProduct): array
    {
        $unitPrice = $activeProduct ? $activeProduct['unit_price'] : $orderDetail['price'];
        $currentStock = max(0, $activeProduct['current_stock']);
        $variations = is_array($activeProduct['variation']) ? $activeProduct['variation'] : json_decode($activeProduct['variation'] ?? '', true);
        $matchedVariation = collect($variations)->first(
            static fn ($variation) => $variation['type'] == $orderDetail['variant'],
        );

        if ($orderDetail['variant'] && $matchedVariation) {
            $currentStock = $matchedVariation['qty'] ?? 0;
            $unitPrice = $matchedVariation['price'] ?? 0;
        }

        if ($orderDetail['is_stock_decreased'] == 1) {
            $currentStock += 1;
        }

        return [
            'current_stock' => $currentStock,
            'current_price' => $unitPrice,
        ];
    }

    /** @return array<int, int> */
    public function getOrderedProductIdsForCustomer(int|string $customerId): array
    {
        return $this->query()
            ->whereHas('order', fn (Builder $order) => $order->where('customer_id', $customerId))
            ->whereNotNull('product_id')
            ->distinct()
            ->pluck('product_id')
            ->map(static fn ($productId): int => (int) $productId)
            ->all();
    }

    /** @return array{order_count: int, delivery_count: int} */
    public function getOrderCountsForProduct(int|string $productId): array
    {
        $counts = $this->query()
            ->toBase()
            ->where('product_id', $productId)
            ->selectRaw('count(*) as order_count')
            ->selectRaw('sum(case when delivery_status = ? then 1 else 0 end) as delivery_count', [self::DELIVERY_STATUS_DELIVERED])
            ->first();

        return [
            'order_count' => (int) ($counts->order_count ?? 0),
            'delivery_count' => (int) ($counts->delivery_count ?? 0),
        ];
    }

    /** @return array<int, array{product_id: int, total_qty_sold: int, total_sold_amount: float}> */
    public function getTopSellingProductSales(array $productIds): array
    {
        if ($productIds === []) {
            return [];
        }

        $eligibleProductIds = $this->query()
            ->whereIn('product_id', $productIds)
            ->where('delivery_status', self::DELIVERY_STATUS_DELIVERED)
            ->whereDoesntHave('refundStatus', fn (Builder $refundStatus) => $refundStatus->where('status', self::REFUND_STATUS_APPROVED))
            ->distinct()
            ->pluck('product_id')
            ->all();

        if ($eligibleProductIds === []) {
            return [];
        }

        return $this->query()
            ->toBase()
            ->whereIn('product_id', $eligibleProductIds)
            ->where('delivery_status', self::DELIVERY_STATUS_DELIVERED)
            ->groupBy('product_id')
            ->orderBy('product_id')
            ->selectRaw('product_id, SUM(qty) as total_qty_sold, SUM(price * qty) - SUM(discount) as total_sold_amount')
            ->get()
            ->sortByDesc('total_qty_sold')
            ->values()
            ->map(static fn ($sale): array => [
                'product_id' => (int) $sale->product_id,
                'total_qty_sold' => (int) $sale->total_qty_sold,
                'total_sold_amount' => (float) $sale->total_sold_amount,
            ])
            ->all();
    }

    public function getDownloadableFileName(Model $orderDetail): ?string
    {
        $product = $orderDetail->product;

        if ($product?->digital_product_type == self::DIGITAL_READY_PRODUCT && $product?->digital_file_ready) {
            return $product->digital_file_ready;
        }

        return $orderDetail->digital_file_after_sell;
    }

    public function updateDigitalFileAfterSell(Model $orderDetail, UploadedFile $file): bool
    {
        $orderDetail->digital_file_after_sell = $this->replaceImage(
            dir: self::DIGITAL_FILE_DIRECTORY,
            oldImage: $orderDetail->digital_file_after_sell,
            format: $file->getClientOriginalExtension(),
            image: $file,
            fileType: self::FILE_TYPE_FILE,
        );

        return $orderDetail->save();
    }

    public function upsertEditedLine(int|string $orderId, array $item, float|int $appliedTaxAmount, mixed $editedAt): Model
    {
        return $this->query()->updateOrCreate(
            [
                'order_id' => $orderId,
                'product_id' => $item['product_id'],
                'variant' => $item['variant'] ?? null,
            ],
            [
                'seller_id' => $item['seller_id'] ?? null,
                'digital_file_after_sell' => $item['digital_file_after_sell'] ?? null,
                'product_details' => $item['product_details'] ?? null,
                'qty' => $item['qty'],
                'price' => $item['price'],
                'tax' => $appliedTaxAmount,
                'discount' => ($item['discount'] ?? 0) * ($item['qty'] ?? 0),
                'tax_model' => $item['tax_model'] ?? self::DEFAULT_TAX_MODEL,
                'delivery_status' => $item['delivery_status'] ?? self::DELIVERY_STATUS_PENDING,
                'payment_status' => $item['payment_status'] ?? self::PAYMENT_STATUS_UNPAID,
                'shipping_method_id' => $item['shipping_method_id'] ?? null,
                'variation' => $item['variation'] ?? null,
                'discount_type' => $item['discount_type'] ?? null,
                'is_stock_decreased' => $item['is_stock_decreased'] ?? 1,
                'refund_request' => $item['refund_request'] ?? 0,
                'refund_started_at' => $item['refund_started_at'] ?? $editedAt,
                'updated_at' => $editedAt,
            ],
        );
    }

    public function deleteLinesExcept(int|string $orderId, array $keepIds): int
    {
        if ($keepIds === []) {
            return 0;
        }

        $deleted = $this->query()->where('order_id', $orderId)->whereNotIn('id', $keepIds)->delete();
        cacheRemoveByType(type: self::CACHE_TYPE);

        return $deleted;
    }

    /** @return array{variant: mixed, product: object|array} */
    public function buildPosProductSnapshot(object|array $cartItem, object|array $product, ?Model $digitalVariation, ?Model $variationStorage): array
    {
        $variant = $cartItem['variant'];
        $unitPrice = $product['unit_price'];

        if ($cartItem['variant'] != null) {
            $storedVariation = is_array($product) ? ($product['variation'] ?? null) : ($product['variation'] ?? $product->retailDetail?->variation);
            foreach (json_decode($storedVariation ?? '', true) ?? [] as $variation) {
                if ($cartItem['variant'] == $variation['type']) {
                    $unitPrice = $variation['price'];
                }
            }
        }

        if ($product['product_type'] == self::PRODUCT_TYPE_DIGITAL
            && $product['digital_product_type'] == self::DIGITAL_READY_PRODUCT
            && ! empty($product['digital_file_ready'])
            && ! isset($cartItem['variant_key'])) {
            $product['storage_path'] = $product['digital_file_ready_storage_type'] ?? self::DEFAULT_DIGITAL_STORAGE;
        }

        if ($product['product_type'] == self::PRODUCT_TYPE_DIGITAL && ! empty($cartItem['variant_key'])) {
            foreach ($product['digitalVariation'] ?? [] as $productDigitalVariation) {
                if ($productDigitalVariation['variant_key'] == $cartItem['variant_key']) {
                    if ($product['digital_product_type'] == self::DIGITAL_READY_PRODUCT && $digitalVariation) {
                        $product['digital_file_ready'] = $digitalVariation['file'];
                        $product['storage_path'] = $variationStorage ? $variationStorage['value'] : self::DEFAULT_DIGITAL_STORAGE;
                    }

                    $variant = $productDigitalVariation['variant_key'];
                    $unitPrice = $productDigitalVariation['price'];
                }
            }
        }

        $product['unit_price_amount'] = $unitPrice;
        unset($product['order_details'], $product['order_delivered'], $product['seo_info'], $product['reviews']);

        return [
            'variant' => $variant,
            'product' => $product,
        ];
    }

    public function createPosOrderLine(int $orderId, object|array $cartItem, object|array $productSnapshot, mixed $variant, int|string $sellerId, bool $withoutSellerSecrets = false): bool
    {
        $taxConfig = self::getTaxSystemType();

        return $this->query()->insert([
            'order_id' => $orderId,
            'product_id' => $cartItem['id'],
            'product_details' => json_encode($withoutSellerSecrets ? $this->removeSellerSecrets(productSnapshot: $productSnapshot) : $productSnapshot),
            'qty' => $cartItem['quantity'],
            'price' => $cartItem['price'],
            'seller_id' => $sellerId,
            'tax' => $cartItem['applied_tax_amount'] * $cartItem['quantity'],
            'tax_model' => $taxConfig['is_included'] ? self::TAX_MODEL_INCLUDE : self::TAX_MODEL_EXCLUDE,
            'discount' => $cartItem['discount'] * $cartItem['quantity'],
            'discount_type' => self::DISCOUNT_TYPE_ON_PRODUCT,
            'delivery_status' => self::DELIVERY_STATUS_DELIVERED,
            'payment_status' => self::PAYMENT_STATUS_PAID,
            'variant' => $variant,
            'variation' => is_string($cartItem['variation']) ? $cartItem['variation'] : json_encode($cartItem['variation']),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /** @return array<int, int|string> */
    public function getFirstLineCategoryIdsForCustomer(int|string $customerId): array
    {
        $firstLineIds = $this->query()
            ->whereHas('order', fn (Builder $order) => $order->where('customer_id', $customerId))
            ->selectRaw('MIN(id)')
            ->groupBy('order_id');

        return $this->query()
            ->toBase()
            ->whereIn('id', $firstLineIds)
            ->orderBy('order_id')
            ->pluck('product_details')
            ->map(static function ($productDetails) {
                $snapshot = json_decode((string) $productDetails, true);

                return is_array($snapshot) ? ($snapshot['category_id'] ?? null) : null;
            })
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    public function acceptsDigitalFileAfterSell(object|array $orderDetail): bool
    {
        $productDetails = is_array($orderDetail['product_details'])
            ? $orderDetail['product_details']
            : json_decode((string) $orderDetail['product_details'], true);
        $productDetails = is_array($productDetails) ? $productDetails : [];

        return ($productDetails['product_type'] ?? null) == self::PRODUCT_TYPE_DIGITAL
            && ($productDetails['digital_product_type'] ?? null) == self::DIGITAL_READY_AFTER_SELL;
    }

    private function removeSellerSecrets(object|array $productSnapshot): array
    {
        $snapshot = $productSnapshot instanceof Arrayable ? $productSnapshot->toArray() : (array) $productSnapshot;

        if (is_array($snapshot['seller'] ?? null)) {
            $snapshot['seller'] = array_diff_key($snapshot['seller'], array_flip(self::SELLER_SECRET_ATTRIBUTES));
        }

        return $snapshot;
    }

    private function getOrderLines(int|string $orderId): Collection
    {
        return $this->query()->where('order_id', $orderId)->orderBy('seller_id', 'ASC')->get();
    }
}
