<?php

namespace App\View\Composers\Admin;

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
        $view->with($this->resolved ??= $this->build());
    }

    /** @return array<string, mixed> */
    private function build(): array
    {
        $orderCounts = $this->retailOrderService->getStatusCounts();

        return [
            'sidebarOrderCounts' => $orderCounts,
            'sidebarTotalOrders' => array_sum($orderCounts),
            'sidebarRefundCounts' => $this->retailRefundRequestService->getStatusCounts(),
            'sidebarRetailProductCounts' => $this->productCounts(),
        ];
    }

    /** @return array<string, int> */
    private function productCounts(): array
    {
        $vendorCounts = $this->retailProductService->getRequestStatusCounts([
            'added_by' => ProductRetailService::ADDED_BY_SELLER,
        ]);

        return $vendorCounts + [
            'in_house' => $this->retailProductService->count([
                'added_by' => ProductRetailService::ADDED_BY_ADMIN,
            ]),
        ];
    }
}
