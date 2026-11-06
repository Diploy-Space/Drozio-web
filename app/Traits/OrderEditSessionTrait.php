<?php

namespace App\Traits;

use App\Services\New\OrderEditService;
use Illuminate\Support\Facades\Session;

trait OrderEditSessionTrait
{
    protected function orderEditSessionOwner(): string
    {
        if (auth('admin')->check()) {
            return OrderEditService::OWNER_ADMIN;
        }

        if (auth('seller')->check()) {
            return OrderEditService::OWNER_SELLER;
        }

        return OrderEditService::OWNER_DEFAULT;
    }

    /** @return array<string, mixed> */
    protected function orderEditProductList(OrderEditService $orderEditService, object|array $order): array
    {
        $sessionKey = $orderEditService->getOrderEditSessionKey(
            owner: $this->orderEditSessionOwner(),
            orderId: $order['id'],
        );

        $productList = Session::get($sessionKey, [])['product_list'] ?? null;

        if ($productList === null) {
            $productList = $orderEditService->addOrderDetailsInSession(order: $order);
            Session::put($sessionKey, ['product_list' => $productList]);
        }

        return $productList;
    }

    /** @param  array<string, mixed>  $productList */
    protected function storeOrderEditProductList(OrderEditService $orderEditService, object|array $order, array $productList): void
    {
        Session::put(
            $orderEditService->getOrderEditSessionKey(
                owner: $this->orderEditSessionOwner(),
                orderId: $order['id'],
            ),
            ['product_list' => $productList],
        );
    }
}
