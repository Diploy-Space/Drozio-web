<?php

namespace App\Http\Controllers\Admin\POS;

use App\Http\Controllers\BaseController;
use App\Services\New\CustomerService;
use App\Services\New\Pos\PosHoldOrderService;
use App\Services\New\ProductRetailService;
use Illuminate\Support\Collection;

abstract class PosBaseController extends BaseController
{
    public function __construct(
        protected readonly ProductRetailService $retailProductService,
        protected readonly CustomerService $customerService,
        protected readonly PosHoldOrderService $posHoldOrderService,
    ) {}

    protected function getCurrentCustomerSummary(): array
    {
        $customer = $this->customerService->findBy(params: ['id' => $this->posHoldOrderService->getCurrentCustomerId()]);

        return $this->posHoldOrderService->getCurrentCustomerSummary(customer: $customer);
    }

    protected function getCartSummary(string $cartId): array
    {
        $customerId = $this->posHoldOrderService->getCartCustomerId(cartId: $cartId);
        $customer = $customerId === null ? null : $this->customerService->findBy(params: ['id' => $customerId]);

        return $this->posHoldOrderService->getCartSummary(
            cartId: $cartId,
            products: $this->getCartProducts(cartIds: [$cartId]),
            customer: $customer,
        );
    }

    protected function getHoldOrderList(): array
    {
        $cartIds = $this->posHoldOrderService->getCartNames();
        $customerIds = $this->posHoldOrderService->getCartCustomerIds(cartIds: $cartIds);
        $customers = $customerIds === [] ? collect() : $this->customerService->getList(dataLimit: 'all', filters: ['id' => $customerIds]);

        return $this->posHoldOrderService->getHoldOrderList(
            cartIds: $cartIds,
            products: $this->getCartProducts(cartIds: $cartIds),
            customers: $customers,
        );
    }

    private function getCartProducts(array $cartIds): Collection
    {
        $productIds = $this->posHoldOrderService->getCartProductIds(cartIds: $cartIds);

        return $productIds === [] ? collect() : $this->retailProductService->getList(
            dataLimit: 'all',
            relations: ['retailDetail', 'clearanceSale' => fn ($query) => $query->active()],
            filters: ['id' => $productIds],
        );
    }
}
