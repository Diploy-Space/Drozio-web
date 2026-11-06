<?php

namespace App\Traits;

use App\Enums\BusinessModule;
use App\Services\New\BusinessSettingService;
use App\Services\New\CouponService;
use App\Services\New\OrderEditService;
use App\Services\New\OrderService;
use App\Services\New\ShippingMethodService;
use App\Services\New\ShippingTypeService;
use App\Utils\CartManager;
use Illuminate\Database\Eloquent\Model;

trait OrderEditSummaryTrait
{
    /**
     * @param  array<string, array<string, mixed>>  $editedProducts
     * @param  array{edit_by: string, edited_user_id: mixed, edited_user_name: string}  $editor
     * @return array{status: string, order_data: array<string, mixed>, order_amount: float|int, edit_due_amount: float|int, order_return_amount: float|int, tax_summary: array<string, mixed>, coupon_summary: array<string, mixed>}
     */
    protected function getEditOrderSummary(Model $order, array $editedProducts, array $editor): array
    {
        $orderEditService = app(OrderEditService::class);
        $seller = $order['seller_is'] === OrderService::SELLER_IS_SELLER ? $order->seller : null;
        $shippingCost = $this->getEditOrderShippingCost(order: $order, editedProducts: $editedProducts);

        $couponSummary = $orderEditService->calculateEditCouponSummary(
            order: $order,
            editedProducts: $editedProducts,
            coupon: app(CouponService::class)->findApplicableForEditedOrder(order: $order),
            shippingCost: $shippingCost,
        );
        $freeDelivery = $orderEditService->calculateEditFreeDelivery(
            order: $order,
            editedProducts: $editedProducts,
            shippingCost: $shippingCost,
            seller: $seller,
            inHouseFreeDeliveryStatus: app(BusinessSettingService::class)
                ->findBy(params: ['type' => BusinessSettingService::IN_HOUSE_FREE_DELIVERY_SETTINGS[BusinessModule::RETAIL]['status']])?->value,
        );
        $amountData = $orderEditService->calculateEditAmountData(order: $order, couponSummary: $couponSummary, freeDelivery: $freeDelivery, shippingCost: $shippingCost);
        $taxLineBases = $orderEditService->getEditTaxLineBases(editedProducts: $editedProducts, amountData: $amountData);

        return $orderEditService->buildEditOrderSummary(
            order: $order,
            editedProducts: $editedProducts,
            couponSummary: $couponSummary,
            freeDelivery: $freeDelivery,
            taxSummary: $orderEditService->buildEditTaxSummary(
                editedProducts: $editedProducts,
                summaryData: $editor + $amountData,
                taxLineBases: $taxLineBases,
                lineTaxes: $this->getEditOrderLineTaxes(editedProducts: $editedProducts, taxLineBases: $taxLineBases),
            ),
            adminCommission: $orderEditService->calculateEditAdminCommission(order: $order, seller: $seller, totalCartAmount: $couponSummary['total_cart_amount']),
        );
    }

    /** @param  array<string, array<string, mixed>>  $editedProducts */
    private function getEditOrderShippingCost(Model $order, array $editedProducts): float|int
    {
        $orderEditService = app(OrderEditService::class);
        $shippingTypeService = app(ShippingTypeService::class);
        $shippingSellerId = $orderEditService->getEditShippingTypeSellerId(order: $order);
        $shippingType = $shippingSellerId === null ? null : $shippingTypeService->resolveTypeForSeller(sellerId: $shippingSellerId);

        if ($shippingType === ShippingTypeService::TYPE_ORDER_WISE) {
            return $orderEditService->calculateEditShippingCost(
                shippingType: $shippingType,
                orderWiseMethodCost: app(ShippingMethodService::class)->find(id: $order['shipping_method_id'])?->cost,
                order: $order,
                productShippingCosts: [],
            );
        }

        $productShippingCosts = [];
        foreach ($editedProducts as $index => $editedProduct) {
            $productShippingCosts[$index] = $editedProduct['product_type'] == OrderService::PRODUCT_TYPE_PHYSICAL
                ? $shippingTypeService->resolveProductShippingCost(product: $editedProduct['active_product'], quantity: $editedProduct['qty'])
                : 0;
        }

        return $orderEditService->calculateEditShippingCost(shippingType: $shippingType, orderWiseMethodCost: null, order: $order, productShippingCosts: $productShippingCosts);
    }

    /**
     * @param  array<string, array<string, mixed>>  $editedProducts
     * @param  array<string, array<string, mixed>>  $taxLineBases
     * @return array<string, array<string, mixed>>
     */
    private function getEditOrderLineTaxes(array $editedProducts, array $taxLineBases): array
    {
        $taxConfig = OrderEditService::getTaxSystemType();
        $lineTaxes = [];

        foreach ($taxLineBases as $index => $taxLineBase) {
            $product = $editedProducts[$index]['active_product'];

            $lineTaxes[$index] = [
                'applied_tax_amount' => CartManager::getAppliedTaxAmount(
                    product: $product,
                    taxConfig: $taxConfig,
                    totalDiscountedPrice: $taxLineBase['total_discounted_price'],
                    appliedDiscountedAmount: $taxLineBase['applied_discounted_amount'],
                ),
                'applied_tax_ids' => CartManager::getAppliedTaxIds(product: $product, taxConfig: $taxConfig),
                'applied_shipping_tax_amount' => CartManager::getAppliedShippingTaxAmount(
                    taxConfig: $taxConfig,
                    totalShippingCost: $taxLineBase['total_shipping_cost'],
                    totalDiscountedPrice: $taxLineBase['total_discounted_price'],
                    applicableShippingAmount: $taxLineBase['applicable_shipping_amount'],
                ),
                'applied_shipping_tax_ids' => CartManager::getAppliedShippingTaxIds(taxConfig: $taxConfig),
            ];
        }

        return $lineTaxes;
    }
}
