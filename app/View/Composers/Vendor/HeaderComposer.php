<?php

namespace App\View\Composers\Vendor;

use App\Models\Seller;
use App\Services\New\ChattingService;
use App\Services\New\NotificationService;
use App\Services\New\OrderService;
use App\Services\New\VendorService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class HeaderComposer
{
    private const RECENT_ORDER_LIMIT = 5;

    private const NOTIFICATION_LIMIT = 20;

    private const UNSEEN_BY_SELLER = 0;

    private const IMAGE_FOUND = 200;

    private const LOGO_TYPE = 'backend-logo';

    /** @var array<string, mixed>|null */
    private ?array $resolved = null;

    public function __construct(
        private readonly VendorService $vendorService,
        private readonly OrderService $orderService,
        private readonly ChattingService $chattingService,
        private readonly NotificationService $retailNotificationService,
    ) {}

    public function compose(View $view): void
    {
        $view->with($this->resolved ??= $this->build(seller: auth('seller')->user()));
    }

    /** @return array<string, mixed> */
    private function build(?Seller $seller): array
    {
        if ($seller === null) {
            return $this->emptyHeader();
        }

        $vendor = $this->vendorService->find(id: $seller->id, relations: ['shop']);

        $moduleKey = activeBusinessModule()->key;
        $surface = config("business_modules.vendor_pending_orders.{$moduleKey}");
        $showOrderIcon = $surface !== null && ! inCommonAdminArea();

        return [
            'headerVendor' => $vendor,
            'headerBrand' => $this->brand($vendor),
            'headerShowOrderIcon' => $showOrderIcon,
            'headerPendingOrderCount' => $showOrderIcon ? $this->orderService->countPendingByBusinessModule(businessModule: $moduleKey, sellerId: $seller->id) : 0,
            'headerRecentOrders' => $showOrderIcon ? $this->recentPendingOrders(moduleKey: $moduleKey, sellerId: $seller->id) : Collection::make(),
            'headerOrderListUrl' => $showOrderIcon ? route($surface['list_route'], ['status' => OrderService::STATUS_PENDING]) : null,
            'headerOrderDetailsRoute' => $showOrderIcon ? $surface['details_route'] : null,
            'headerUnseenMessageCounts' => $this->unseenMessageCounts($seller->id),
            'headerNotifications' => $this->notifications($seller),
            'headerUnseenNotificationCount' => $this->retailNotificationService->count([
                'sent_to' => NotificationService::AUDIENCE_SELLER,
                NotificationService::FILTER_SINCE => $seller->created_at,
                NotificationService::FILTER_UNSEEN => true,
            ]),
        ];
    }

    /** @return array<string, mixed> */
    private function emptyHeader(): array
    {
        return [
            'headerVendor' => null,
            'headerBrand' => $this->brand(null),
            'headerShowOrderIcon' => false,
            'headerPendingOrderCount' => 0,
            'headerRecentOrders' => Collection::make(),
            'headerOrderListUrl' => null,
            'headerOrderDetailsRoute' => null,
            'headerUnseenMessageCounts' => ['total' => 0, 'customer' => 0, 'delivery_man' => 0],
            'headerNotifications' => Collection::make(),
            'headerUnseenNotificationCount' => 0,
        ];
    }

    /** @return array<string, mixed> */
    private function brand(?Model $vendor): array
    {
        $shopImage = $vendor?->shop?->image_full_url;
        $hasShopLogo = is_array($shopImage) && ($shopImage['status'] ?? 0) === self::IMAGE_FOUND && ! empty($shopImage['path']);

        return [
            'logo' => $hasShopLogo
                ? $shopImage['path']
                : getStorageImages(path: getWebConfig(name: 'company_web_logo'), type: self::LOGO_TYPE),
            'logo_mini' => $hasShopLogo
                ? $shopImage['path']
                : getStorageImages(path: getWebConfig(name: 'company_fav_icon'), type: self::LOGO_TYPE),
            'name' => $vendor?->shop?->name ?: getWebConfig(name: 'company_name'),
            'alt' => $vendor?->shop?->name ?: translate('Logo'),
            'shop_url' => $vendor?->shop
                ? route('vendor-shop', ['slug' => $vendor->shop->slug ?? ''])
                : 'javascript:',
        ];
    }

    private function recentPendingOrders(string $moduleKey, int $sellerId): Collection
    {
        $orders = $this->orderService->getRecentPendingByBusinessModule(
            businessModule: $moduleKey,
            limit: self::RECENT_ORDER_LIMIT,
            relations: ['details.productAllStatus'],
            sellerId: $sellerId,
        );

        return $orders->map(static fn (Model $order): array => [
            'id' => $order->id,
            'amount' => $order->order_amount,
            'thumbnails' => $order->details
                ->map(static fn (Model $detail): mixed => $detail->productAllStatus?->thumbnail_full_url)
                ->filter(static fn (mixed $image): bool => is_array($image) && ($image['status'] ?? 0) === self::IMAGE_FOUND)
                ->values()
                ->all(),
        ]);
    }

    /** @return array<string, int> */
    private function unseenMessageCounts(int $sellerId): array
    {
        $unseen = [
            'seen_by_seller' => self::UNSEEN_BY_SELLER,
            'seller_id' => $sellerId,
        ];

        return [
            'total' => $this->chattingService->count($unseen),
            'customer' => $this->chattingService->count($unseen + [ChattingService::FILTER_FROM_CUSTOMER => true]),
            'delivery_man' => $this->chattingService->count($unseen + [ChattingService::FILTER_FROM_DELIVERY_MAN => true]),
        ];
    }

    private function notifications(Seller $seller): Collection
    {
        $notifications = $this->retailNotificationService->getList(
            dataLimit: self::NOTIFICATION_LIMIT,
            relations: ['notificationSeenBy'],
            filters: [
                'sent_to' => NotificationService::AUDIENCE_SELLER,
                NotificationService::FILTER_SINCE => $seller->created_at,
            ],
        );

        return Collection::make($notifications->items());
    }
}
