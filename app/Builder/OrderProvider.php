<?php

namespace App\Builder;

use App\Enums\BusinessModule;
use App\Models\Order;
use App\Models\OrderDetail;
use App\Models\Product;
use App\Models\Review;
use App\Models\Shop;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Modules\Builder\app\Contracts\OrderProvider as OrderProviderContract;
use Modules\Builder\app\ValueObjects\PaginatedResult;
use Modules\Builder\app\ValueObjects\Storefront\OrderDetailDTO;
use Modules\Builder\app\ValueObjects\Storefront\OrderSummaryDTO;
use Modules\Builder\app\ValueObjects\StorefrontScope;

class OrderProvider implements OrderProviderContract
{
    private const STATUS_LABEL = [
        'pending'          => 'Pending',
        'failed'           => 'Pending',
        'confirmed'        => 'Confirmed',
        'processing'       => 'Processing',
        'out_for_delivery' => 'Out For Delivery',
        'delivered'        => 'Delivered',
        'canceled'         => 'Cancelled',
        'returned'         => 'Returned',
    ];

    private const STATUS_VARIANT = [
        'pending'          => 'info',
        'failed'           => 'info',
        'confirmed'        => 'warning',
        'processing'       => 'warning',
        'out_for_delivery' => 'warning',
        'delivered'        => 'success',
        'canceled'         => 'danger',
        'returned'         => 'danger',
    ];

    private const TIMELINE = ['pending', 'confirmed', 'processing', 'out_for_delivery', 'delivered'];

    public function customerOrderListing(
        ?StorefrontScope $scope,
        ?int $customerId,
        string $bucket,
        array $statuses,
        int $perPage,
        int $page,
        string $pageName,
    ): PaginatedResult {
        if (!$customerId) {
            return PaginatedResult::fromPaginator(new LengthAwarePaginator([], 0, $perPage, $page, ['pageName' => $pageName]));
        }

        $predicate = $bucket === 'previous'
            ? fn (EloquentBuilder $q) => $q->whereIn('order_status', $statuses)
            : fn (EloquentBuilder $q) => $q->whereNotIn('order_status', $statuses);

        $paginator = $predicate($this->customerOrdersBaseQuery($customerId, $scope))
            ->withCount('details')
            ->orderByDesc('created_at')
            ->paginate(perPage: $perPage, columns: ['id', 'order_status', 'order_type', 'order_amount', 'payment_status', 'created_at'], pageName: $pageName, page: $page);

        $reviewedMap = $this->reviewedOrderMap($customerId, collect($paginator->items()));

        $paginator->through(fn (Order $order) => OrderSummaryDTO::fromArray([
            'id'            => $order->id,
            'status'        => $order->order_status,
            'statusLabel'   => $this->statusLabel($order->order_status),
            'statusVariant' => $this->statusVariant($order->order_status),
            'amount'        => $this->money($order->order_amount),
            'items'         => (int) $order->details_count,
            'paid'          => $order->payment_status === 'paid',
            'reorderable'   => $order->order_status === 'delivered',
            'reviewable'    => $order->order_status === 'delivered' && !($reviewedMap[$order->id] ?? false),
            'date'          => $order->created_at ? Carbon::parse($order->created_at)->format('h:iA, d M y') : null,
        ])->toArray());

        return PaginatedResult::fromPaginator($paginator);
    }

    public function customerOrdersCount(?StorefrontScope $scope, ?int $customerId): int
    {
        return $customerId ? $this->customerOrdersBaseQuery($customerId, $scope)->count() : 0;
    }

    public function latestUnpaidDigitalOrder(?StorefrontScope $scope, ?int $customerId): ?array
    {
        if (!$customerId) {
            return null;
        }

        $order = $this->customerOrdersBaseQuery($customerId, $scope)
            ->where('payment_status', 'unpaid')
            ->where('order_status', 'failed')
            ->orderByDesc('created_at')
            ->first(['id', 'order_amount', 'paid_amount']);

        if (!$order) {
            return null;
        }

        return [
            'orderId'   => (int) $order->id,
            'dueAmount' => (float) ($order->order_amount - ($order->paid_amount ?? 0)),
        ];
    }

    public function paymentReturnInfo(int $orderId): ?array
    {
        $order = Order::query()
            ->businessModule(BusinessModule::RETAIL)
            ->select(['id', 'is_guest', 'customer_id', 'shipping_address_data', 'payment_status'])
            ->where('id', $orderId)
            ->first();

        if (!$order) {
            return null;
        }

        $stored = $this->decode($order->shipping_address_data);

        return [
            'isGuest'       => (int) $order->is_guest === 1,
            'storedPhone'   => $stored['phone'] ?? $stored['contact_person_number'] ?? null,
            'paymentStatus' => $order->payment_status !== null ? (string) $order->payment_status : null,
        ];
    }

    public function customerOrderDetails(?StorefrontScope $scope, ?int $customerId, int $orderId): ?array
    {
        if (!$customerId) {
            return null;
        }

        $order = $this->customerOrdersBaseQuery($customerId, $scope)
            ->with(['details.product', 'details.productAllStatus', 'deliveryMan', 'seller.shop'])
            ->where('id', $orderId)
            ->first();

        return $order ? $this->formatOrder($order) : null;
    }

    public function formatOrder(Order $order): array
    {
        $rawStatus = (string) $order->order_status;
        $paid = $order->payment_status === 'paid';

        return OrderDetailDTO::fromArray([
            'id'            => (string) $order->id,
            'date'          => $order->created_at ? Carbon::parse($order->created_at)->format('h:iA, d M y') : null,
            'scheduled'     => false,
            'scheduleAt'    => null,
            'status'        => $this->statusLabel($rawStatus),
            'statusRaw'     => $rawStatus,
            'statusVariant' => $this->statusVariant($rawStatus),
            'paid'          => $paid,
            'cancellable'   => (int) $order->is_guest !== 1
                && $rawStatus === 'pending'
                && $order->payment_method === 'cash_on_delivery',
            'refundable'    => $rawStatus === 'delivered' && $paid && (bool) config('builder.wallet_features_enabled', true),
            'reorderable'   => $rawStatus === 'delivered',
            'reviewable'    => $rawStatus === 'delivered' && !$this->allItemsReviewed($order),
            'paymentMethod' => $this->paymentLabel($order->payment_method),
            'paymentIcon'   => null,
            'orderType'     => (string) ($order->order_type ?? 'delivery'),
            'moduleType'    => null,
            'items'         => $this->mapItems($order),
            'pricing'       => $this->mapPricing($order),
            'delivery'      => $this->mapDelivery($order),
            'seller'        => $this->mapSeller($order),
            'deliveryMan'   => $this->mapDeliveryMan($order),
            'tracking'      => $this->mapTracking($rawStatus),
            'cancellationNote' => $rawStatus === 'canceled' && !empty($order->cause) ? (string) $order->cause : null,
            'verificationCode' => $this->resolveVerificationCode($order, $rawStatus),
        ])->toArray();
    }

    private function resolveVerificationCode(Order $order, string $rawStatus): ?string
    {
        if ((int) getWebConfig(name: 'order_verification') !== 1) {
            return null;
        }
        if (($order->order_type ?? 'default_type') !== 'default_type' || $rawStatus === 'delivered') {
            return null;
        }

        $code = $this->cleanScalar($order->verification_code);

        return $code !== null && $code !== '' ? (string) $code : null;
    }

    private function customerOrdersBaseQuery(int $customerId, ?StorefrontScope $scope = null): EloquentBuilder
    {
        $vendorId = $scope?->tenantId;

        return Order::query()
            ->businessModule(BusinessModule::RETAIL)
            ->where('customer_id', $customerId)
            ->where('is_guest', 0)
            ->where('order_type', '!=', 'POS')
            ->when($vendorId, fn (EloquentBuilder $query) => $query
                ->where('seller_id', $vendorId)
                ->where('seller_is', 'seller'));
    }

    private function allItemsReviewed(Order $order): bool
    {
        $productIds = $order->details
            ->pluck('product_id')
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->unique();

        if ($productIds->isEmpty()) {
            return false;
        }

        $reviewedCount = Review::query()
            ->where('order_id', $order->id)
            ->where('customer_id', $order->customer_id)
            ->whereIn('product_id', $productIds->all())
            ->distinct()
            ->count('product_id');

        return $reviewedCount >= $productIds->count();
    }

    /**
     * @param  \Illuminate\Support\Collection<int, Order>  $orders
     * @return array<int, bool>
     */
    private function reviewedOrderMap(int $customerId, $orders): array
    {
        $orderIds = $orders->pluck('id')->map(fn ($id) => (int) $id)->all();
        if ($orderIds === []) {
            return [];
        }

        $productCounts = DB::table('order_details')
            ->whereIn('order_id', $orderIds)
            ->whereNotNull('product_id')
            ->select('order_id', DB::raw('COUNT(DISTINCT product_id) as total'))
            ->groupBy('order_id')
            ->pluck('total', 'order_id');

        $reviewedCounts = Review::query()
            ->whereIn('order_id', $orderIds)
            ->where('customer_id', $customerId)
            ->whereNotNull('product_id')
            ->select('order_id', DB::raw('COUNT(DISTINCT product_id) as total'))
            ->groupBy('order_id')
            ->pluck('total', 'order_id');

        $map = [];
        foreach ($orderIds as $id) {
            $total = (int) ($productCounts[$id] ?? 0);
            $reviewed = (int) ($reviewedCounts[$id] ?? 0);
            $map[$id] = $total > 0 && $reviewed >= $total;
        }

        return $map;
    }

    private function money(float|int|null $amount): float
    {
        return (float) webCurrencyConverterOnlyDigit(amount: (float) ($amount ?? 0));
    }

    private function mapItems(Order $order): array
    {
        $paid = $order->payment_status === 'paid';
        $items = [];
        foreach ($order->details as $detail) {
            $snapshot = $this->decode($detail->product_details);
            $unitPrice = (float) ($detail->price ?? 0);
            $discount = (float) ($detail->discount ?? 0);
            $qty = (int) ($detail->qty ?? 0);

            $name = $detail->product->name ?? ($snapshot['name'] ?? translate('Product'));
            $image = $this->resolveItemImage($detail, $snapshot);
            [$digital, $downloadUrl, $digitalPending] = $this->resolveDigitalDownload($detail, $paid);

            $items[] = [
                'id'             => (int) $detail->id,
                'name'           => (string) $name,
                'variant'        => (string) ($this->cleanScalar($detail->variant) ?? ''),
                'addons'         => '',
                'unitPrice'      => $this->money($unitPrice),
                'qty'            => $qty,
                'total'          => $this->money(max(0.0, ($unitPrice - $discount) * $qty)),
                'image'          => $image,
                'digital'        => $digital,
                'downloadUrl'    => $downloadUrl,
                'digitalPending' => $digitalPending,
            ];
        }
        return $items;
    }

    /** @return array{0: bool, 1: ?string, 2: bool} */
    private function resolveDigitalDownload(OrderDetail $detail, bool $paid): array
    {
        $product = $detail->product ?? $detail->productAllStatus;
        $snapshot = $this->decode($detail->product_details);
        $productType = $product->product_type ?? ($snapshot['product_type'] ?? null);

        if ($productType !== 'digital') {
            return [false, null, false];
        }

        $digitalType = $product?->retailDetail?->digital_product_type ?? ($snapshot['digital_product_type'] ?? null);

        $downloadUrl = null;
        $pendingUpload = false;
        if ($paid) {
            if ($digitalType === 'ready_product') {
                $downloadUrl = route('storefront.order.digital_download', ['id' => $detail->id]);
            } elseif ($digitalType === 'ready_after_sell') {
                if (!empty($detail->digital_file_after_sell)) {
                    $downloadUrl = route('storefront.order.digital_download', ['id' => $detail->id]);
                } else {
                    $pendingUpload = true;
                }
            }
        }

        return [true, $downloadUrl, $pendingUpload];
    }

    private function resolveItemImage(OrderDetail $detail, array $snapshot): ?string
    {
        $product = $detail->product ?? $detail->productAllStatus;

        if ($product) {
            $url = $this->existingThumbnailUrl($product->thumbnail_full_url ?? null);
            if ($url) {
                return $url;
            }
        }

        $thumbnail = $snapshot['thumbnail'] ?? null;
        if (is_string($thumbnail) && $thumbnail !== '') {
            try {
                $snapshotProduct = new Product();
                $snapshotProduct->thumbnail = $thumbnail;
                $snapshotProduct->thumbnail_storage_type = $snapshot['thumbnail_storage_type'] ?? 'public';

                $url = $this->existingThumbnailUrl($snapshotProduct->thumbnail_full_url ?? null);
                if ($url) {
                    return $url;
                }
            } catch (\Throwable) {
            }
        }

        return null;
    }

    private function existingThumbnailUrl($thumbnailFullUrl): ?string
    {
        if (is_array($thumbnailFullUrl)) {
            return ((int) ($thumbnailFullUrl['status'] ?? 0) === 200 && !empty($thumbnailFullUrl['path']))
                ? (string) $thumbnailFullUrl['path']
                : null;
        }

        return is_string($thumbnailFullUrl) && $thumbnailFullUrl !== '' ? $thumbnailFullUrl : null;
    }

    private function mapPricing(Order $order): array
    {
        $subtotal = 0.0;
        $productDiscount = 0.0;
        foreach ($order->details as $detail) {
            $subtotal += (float) $detail->price * (int) $detail->qty;
            $productDiscount += (float) $detail->discount * (int) $detail->qty;
        }

        $shippingCost   = (float) ($order->shipping_cost ?? 0);
        $couponDiscount = (float) ($order->discount_amount ?? 0);

        $couponFreeDelivery = $order->coupon_code
            && $order->discount_type === 'coupon_discount'
            && optional($order->coupon)->coupon_type === 'free_delivery';
        if ($couponFreeDelivery) {
            $couponDiscount = max(0.0, $couponDiscount - $shippingCost);
            $shippingCost   = 0.0;
        }

        $isShippingFree = (bool) $order->is_shipping_free;
        if ($isShippingFree) {
            $shippingCost = 0.0;
        }

        $freeDelivery = $couponFreeDelivery || $isShippingFree;

        return [
            'currency'         => \App\Utils\currency_symbol() ?: '$',
            'itemPrice'        => $this->money($subtotal),
            'addonsPrice'      => 0.0,
            'subtotal'         => $this->money(max(0.0, $subtotal - $productDiscount)),
            'productDiscount'  => $this->money($productDiscount),
            'discount'         => $this->money($productDiscount),
            'couponDiscount'   => $this->money($couponDiscount),
            'couponCode'       => $order->coupon_code ?: null,
            'vatTax'           => $this->money($order->total_tax_amount ?? 0),
            'dmTips'           => $this->money($order->dm_tips ?? 0),
            'deliveryCharge'   => $this->money($shippingCost),
            'freeDelivery'     => $freeDelivery,
            'additionalCharge' => $this->money($order->additional_charge ?? 0),
            'extraPackaging'   => $this->money($order->extra_packaging_amount ?? 0),
            'total'            => $this->money($order->order_amount ?? 0),
            'bringChange'      => $order->payment_method === 'cash_on_delivery'
                ? (float) ($order->bring_change_amount ?? 0) : 0.0,
        ];
    }

    private function mapDelivery(Order $order): ?array
    {
        $address = $this->decode($order->shipping_address_data);

        $line  = $this->cleanScalar($address['address'] ?? null);
        $name  = $this->cleanScalar($address['contact_person_name'] ?? null);
        $phone = $this->cleanScalar($address['phone'] ?? $address['contact_person_number'] ?? null);

        if (!$line && !$name && !$phone) {
            return null;
        }

        return [
            'label'   => $this->cleanScalar($address['address_type'] ?? null) ?? translate('Delivery'),
            'address' => $line,
            'floor'   => $this->cleanScalar($address['floor'] ?? null),
            'house'   => $this->cleanScalar($address['house'] ?? null),
            'road'    => $this->cleanScalar($address['road'] ?? null),
            'name'    => $name,
            'phone'   => $phone,
            'email'   => $this->cleanScalar($address['email'] ?? null),
            'note'    => $order->order_note ?: null,
        ];
    }

    private function mapSeller(Order $order): ?array
    {
        if (($order->seller_is ?? null) === 'seller') {
            $shop = $order->seller?->shop ?? Shop::where('seller_id', $order->seller_id)->first();
            if (!$shop) {
                return null;
            }
            return [
                'name'  => $shop->name,
                'image' => getStorageImages(path: $shop->image_full_url, type: 'shop'),
                ...$this->shopRating(addedBy: 'seller', sellerId: (int) $order->seller_id),
            ];
        }

        return [
            'name'  => getWebConfig(name: 'company_name'),
            'image' => getStorageImages(path: getWebConfig(name: 'company_web_logo'), type: 'logo'),
            ...$this->shopRating(addedBy: 'admin', sellerId: null),
        ];
    }

    /** @return array{rating: float, reviews: int} */
    private function shopRating(string $addedBy, ?int $sellerId): array
    {
        $productIds = Product::query()
            ->businessModule(BusinessModule::RETAIL)
            ->where('added_by', $addedBy)
            ->when($addedBy === 'seller' && $sellerId, fn ($query) => $query->where('user_id', $sellerId))
            ->pluck('id');

        if ($productIds->isEmpty()) {
            return ['rating' => 0.0, 'reviews' => 0];
        }

        $reviews = Review::active()->whereIn('product_id', $productIds->all());
        $count = (clone $reviews)->count();

        return [
            'rating'  => $count ? round((float) $reviews->avg('rating'), 1) : 0.0,
            'reviews' => $count,
        ];
    }

    private function mapDeliveryMan(Order $order): ?array
    {
        if ($order->delivery_type === 'third_party_delivery') {
            return $this->mapThirdPartyDelivery($order);
        }

        $deliveryMan = $order->deliveryMan;
        if (!$deliveryMan) {
            return null;
        }

        $average = (float) Review::query()->where('delivery_man_id', $deliveryMan->id)->avg('rating');
        $count = (int) Review::query()->where('delivery_man_id', $deliveryMan->id)->count();
        $name = trim(((string) ($deliveryMan->f_name ?? '')) . ' ' . ((string) ($deliveryMan->l_name ?? '')));

        $chatEnabled = (bool) config('builder.capabilities.features.deliveryManChat', true);
        $callEnabled = (bool) config('builder.capabilities.features.deliveryManCall', true);

        return [
            'id'          => $chatEnabled ? (int) $deliveryMan->id : 0,
            'name'        => $name !== '' ? $name : translate('Delivery Partner'),
            'image'       => !empty($deliveryMan->image) ? getStorageImages(path: $deliveryMan->image_full_url, type: 'backend-profile') : null,
            'phone'       => $callEnabled ? ($deliveryMan->phone ?? null) : null,
            'avgRating'   => round($average, 1),
            'ratingCount' => $count,
            'trackingId'  => null,
            'thirdParty'  => false,
        ];
    }

    private function mapThirdPartyDelivery(Order $order): ?array
    {
        $serviceName = trim((string) ($order->delivery_service_name ?? ''));
        $trackingId = trim((string) ($order->third_party_delivery_tracking_id ?? ''));

        if ($serviceName === '' && $trackingId === '') {
            return null;
        }

        return [
            'id'          => 0,
            'name'        => $serviceName !== '' ? $serviceName : translate('Delivery Partner'),
            'image'       => null,
            'phone'       => null,
            'avgRating'   => 0,
            'ratingCount' => 0,
            'trackingId'  => $trackingId !== '' ? $trackingId : null,
            'thirdParty'  => true,
        ];
    }

    private function mapTracking(string $rawStatus): ?array
    {
        if (in_array($rawStatus, ['canceled', 'returned', 'failed'], true)) {
            return null;
        }

        $activeIndex = array_search($rawStatus, self::TIMELINE, true);
        $steps = [];
        foreach (self::TIMELINE as $index => $status) {
            $steps[] = [
                'key'   => $status,
                'label' => $this->statusLabel($status),
                'done'  => $activeIndex !== false && $index <= $activeIndex,
            ];
        }

        return [
            'activeStatus' => $rawStatus,
            'steps'        => $steps,
            'mapCoords'    => null,
        ];
    }

    private function statusLabel(string $status): string
    {
        return translate(self::STATUS_LABEL[$status] ?? ucwords(str_replace('_', ' ', $status)));
    }

    private function statusVariant(string $status): string
    {
        return self::STATUS_VARIANT[$status] ?? 'info';
    }

    private function paymentLabel(?string $method): ?string
    {
        if (!$method) {
            return null;
        }
        return ucwords(str_replace('_', ' ', $method));
    }

    private function cleanScalar(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }
        $trimmed = trim((string) $value, " \t\n\r\0\x0B\"'");
        return ($trimmed === '' || strcasecmp($trimmed, 'null') === 0) ? null : $trimmed;
    }

    private function decode($value): array
    {
        if (is_array($value)) {
            return $value;
        }
        if (is_string($value) && $value !== '') {
            $decoded = json_decode($value, true);
            return is_array($decoded) ? $decoded : [];
        }
        return [];
    }
}
