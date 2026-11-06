<?php

namespace App\Listeners;

use App\Events\VendorOrderPlacedEvent;
use App\Exceptions\InsufficientStockException;
use App\Services\New\DigitalProductVariationService;
use App\Services\New\OrderDetailService;
use App\Services\New\OrderDetailsRewardsService;
use App\Services\New\OrderTaxService;
use App\Services\New\ProductRetailService;
use App\Services\New\StorageService;

class VendorOrderCreateDetailsListener
{
    public function __construct(
        private readonly OrderDetailService $orderDetailService,
        private readonly OrderDetailsRewardsService $orderDetailsRewardsService,
        private readonly OrderTaxService $orderTaxService,
        private readonly ProductRetailService $productService,
        private readonly DigitalProductVariationService $digitalProductVariationService,
        private readonly StorageService $storageService,
    ) {}

    public function handle(VendorOrderPlacedEvent $event): void
    {
        $placedOrderDetails = [];
        $totalPrice = 0;

        foreach ($event->vendorCart['cart_list'] as $cartItem) {
            $productSnapshot = $this->buildProductSnapshot(productId: $cartItem['product_id'], variant: $cartItem['variant']);

            $appliedTax = $this->orderTaxService->getAppliedProductTax(
                vendorCart: $event->vendorCart,
                cartId: $cartItem['id'],
                sellerId: $cartItem['seller_id'],
                sellerIs: $cartItem['seller_is'],
            );

            if (! $this->productService->deductStockForOrderItem(product: $productSnapshot, variant: $cartItem['variant'], quantity: $cartItem['quantity'])) {
                throw new InsufficientStockException;
            }

            $orderDetail = $this->orderDetailService->createPlacedOrderDetail(
                orderId: $event->orderId,
                cartItem: $cartItem,
                productSnapshot: $productSnapshot,
                appliedTaxAmount: $appliedTax['amount'],
            );
            $totalPrice += $orderDetail['final_amount'];

            $this->orderTaxService->createProductTaxes(
                orderId: $event->orderId,
                vendorCart: $event->vendorCart,
                cartId: $cartItem['id'],
                appliedTax: $appliedTax,
                beforeTaxAmount: $orderDetail['final_amount'],
                quantity: $cartItem['quantity'],
                sellerId: $cartItem['seller_id'],
                sellerIs: $cartItem['seller_is'],
            );

            $placedOrderDetails[] = $orderDetail;
        }

        foreach ($placedOrderDetails as $placedOrderDetail) {
            $this->orderDetailsRewardsService->createForOrderDetail(
                orderId: $event->orderId,
                orderDetailsId: $placedOrderDetail['id'],
                price: $placedOrderDetail['final_amount'],
                totalPrice: $totalPrice,
                couponCode: $event->vendorCart['coupon_code'],
                couponDiscount: $event->vendorCart['coupon_discount'],
                coupon: $event->coupon,
                loyaltyPointSettings: $event->loyaltyPointSettings,
            );
        }
    }

    /** @return array<string, mixed> */
    private function buildProductSnapshot(mixed $productId, mixed $variant): array
    {
        $productAttributes = $this->productService->getOrderSnapshotAttributes(id: $productId);
        $orderedVariation = $this->digitalProductVariationService->findBy(
            params: ['product_id' => $productId, 'variant_key' => $variant],
            relations: ['storage'],
        );

        return $this->orderDetailService->buildProductSnapshot(
            product: $productAttributes,
            productDigitalVariations: isset($productAttributes['digital_variation'])
                ? null
                : $this->digitalProductVariationService->getList(dataLimit: 'all', relations: ['storage'], filters: ['product_id' => $productId])->toArray(),
            orderedDigitalVariation: $orderedVariation,
            orderedVariationStorage: $orderedVariation === null ? null : $this->storageService->findBy(params: [
                'data_id' => $orderedVariation['id'],
                'data_type' => StorageService::DATA_TYPE_DIGITAL_PRODUCT_VARIATION,
            ]),
        );
    }
}
