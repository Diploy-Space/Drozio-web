<?php

namespace App\View\Composers\Vendor;

use App\Services\New\OrderService;
use App\Services\New\ProductRetailService;
use App\Services\New\RefundRequestService;
use Illuminate\View\View;

class RetailSidebarComposer
{
    /** @var array<string, mixed>|null */
    private ?array $resolved = null;

    public function __construct(
        private readonly OrderService $retailOrderService,
        private readonly RefundRequestService $retailRefundRequestService,
        private readonly ProductRetailService $retailProductService,
    ) {}

    public function compose(View $view): void
    {
        $view->with($this->resolved ??= $this->build(sellerId: (int) (auth('seller')->id() ?? 0)));
    }

    /** @return array<string, mixed> */
    private function build(int $sellerId): array
    {
        $orderCounts = $this->retailOrderService->getStatusCounts(filters: [
            'seller_is' => OrderService::SELLER_IS_SELLER,
            'seller_id' => $sellerId,
        ]);

        return [
            'sidebarVendorOrderCounts' => $orderCounts,
            'sidebarVendorTotalOrders' => array_sum($orderCounts),
            'sidebarVendorRefundCounts' => $this->retailRefundRequestService->getStatusCounts(sellerId: $sellerId),
            'sidebarVendorProductCounts' => $this->retailProductService->getRequestStatusCounts([
                'added_by' => ProductRetailService::ADDED_BY_SELLER,
                'seller_id' => $sellerId,
            ]),
        ];
    }
}
