<?php

namespace App\Builder;

use App\Enums\BusinessModule;
use App\Models\Order;
use Modules\Builder\app\Contracts\OrderTrackingProvider as OrderTrackingProviderContract;
use Modules\Builder\app\ValueObjects\StorefrontScope;

class OrderTrackingProvider implements OrderTrackingProviderContract
{
    public function __construct(private OrderProvider $orderFormatter)
    {
    }

    public function track(
        ?StorefrontScope $scope,
        int $orderId,
        ?string $contactNumber,
        ?int $customerId,
    ): ?array {
        $normalizedPhone = $contactNumber ? trim($contactNumber) : null;

        $vendorId = $scope?->tenantId;

        $order = Order::query()
            ->businessModule(BusinessModule::RETAIL)
            ->with(['details.product', 'details.productAllStatus', 'deliveryMan', 'seller.shop'])
            ->where('id', $orderId)
            ->when($vendorId, fn ($q) => $q->where('seller_id', $vendorId)->where('seller_is', 'seller'))
            ->when(
                $customerId,
                fn ($q) => $q->where('customer_id', $customerId)->where('is_guest', 0),
                fn ($q) => $q->where('is_guest', 1)->where(function ($inner) use ($normalizedPhone) {
                    $inner->whereJsonContains('shipping_address_data->phone', $normalizedPhone)
                        ->orWhereJsonContains('shipping_address_data->contact_person_number', $normalizedPhone);
                }),
            )
            ->first();

        return $order ? $this->orderFormatter->formatOrder($order) : null;
    }
}
