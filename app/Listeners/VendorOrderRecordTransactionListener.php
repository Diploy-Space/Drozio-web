<?php

namespace App\Listeners;

use App\Events\VendorOrderTransactionEvent;
use App\Services\New\AdminWalletService;
use App\Services\New\OrderTransactionService;
use App\Services\New\ShopService;

class VendorOrderRecordTransactionListener
{
    private const SHOP_AUTHOR_ADMIN = 'admin';

    private const SHOP_AUTHOR_VENDOR = 'vendor';

    private const SELLER_IS_ADMIN = 'admin';

    private const SELLER_IS_SELLER = 'seller';

    public function __construct(
        private readonly OrderTransactionService $orderTransactionService,
        private readonly AdminWalletService $adminWalletService,
        private readonly ShopService $shopService,
    ) {}

    public function handle(VendorOrderTransactionEvent $event): void
    {
        if (! $this->orderTransactionService->isRecordedAtPlacement(paymentMethod: $event->paymentMethod)) {
            return;
        }

        $this->orderTransactionService->createForPlacedOrder(
            order: $event->order,
            adminCommission: $event->adminCommission,
            paymentMethod: $event->paymentMethod,
            shop: $this->findOrderShop(sellerIs: (string) $event->order['seller_is'], sellerId: $event->order['seller_id']),
            transactionId: $event->transactionId,
        );

        $this->adminWalletService->createInHouseWalletIfMissing();
        $this->adminWalletService->incrementInHousePendingAmount(amount: $event->order['order_amount']);
    }

    private function findOrderShop(string $sellerIs, mixed $sellerId): ?object
    {
        return $this->shopService->findBy(params: match ($sellerIs) {
            self::SELLER_IS_ADMIN => ['author_type' => self::SHOP_AUTHOR_ADMIN],
            self::SELLER_IS_SELLER => ['author_type' => self::SHOP_AUTHOR_VENDOR, 'seller_id' => $sellerId],
            default => [],
        });
    }
}
