<?php

namespace Tests\Feature\Api\Support;

use App\Enums\BusinessModule;
use App\Models\DeliveryMan;
use App\Models\DeliverymanWallet;
use App\Models\Order;
use App\Models\OrderDetail;
use App\Models\Product;
use App\Models\ProductStock;
use App\Services\BusinessModule\BusinessModuleManager;
use Illuminate\Support\Str;
use Modules\Wholesale\app\Services\OrderService;

trait InteractsWithWholesaleDeliveryman
{
    private int $wholesaleDeliverymanOrderId = 0;

    private const WHOLESALE_DELIVERYMAN_VARIANT = 'Blue-Small';

    private const WHOLESALE_DELIVERYMAN_VARIANT_STOCK = 30;

    private const WHOLESALE_DELIVERYMAN_CURRENT_STOCK = 50;

    private const WHOLESALE_DELIVERYMAN_LINE_QUANTITY = 4;

    protected function createDeliveryMan(array $overrides = []): DeliveryMan
    {
        $suffix = Str::lower(Str::random(10));
        $deliveryMan = new DeliveryMan;
        $deliveryMan->forceFill([
            'seller_id' => 0,
            'f_name' => 'Wholesale',
            'l_name' => 'Rider',
            'phone' => '01'.random_int(100000000, 999999999),
            'email' => 'wholesale-deliveryman-'.$suffix.'@example.test',
            'password' => bcrypt(Str::random(16)),
            'is_active' => 1,
            'auth_token' => Str::random(50),
            ...$overrides,
        ])->save();

        DeliverymanWallet::query()->create(['delivery_man_id' => $deliveryMan->id]);

        return $deliveryMan;
    }

    protected function createWholesaleOrder(DeliveryMan $deliveryMan, array $overrides = []): Order
    {
        return $this->createDeliverymanOrder(deliveryMan: $deliveryMan, businessModule: BusinessModule::WHOLESALE, overrides: $overrides);
    }

    protected function createRetailOrder(DeliveryMan $deliveryMan, array $overrides = []): Order
    {
        return $this->createDeliverymanOrder(deliveryMan: $deliveryMan, businessModule: BusinessModule::RETAIL, overrides: $overrides);
    }

    protected function disableWholesaleModule(): void
    {
        $this->app->instance(BusinessModuleManager::class, new class extends BusinessModuleManager
        {
            public function isInstalled(string $key): bool
            {
                return $key !== BusinessModule::WHOLESALE && parent::isInstalled($key);
            }
        });
    }

    private function createDeliverymanOrder(DeliveryMan $deliveryMan, string $businessModule, array $overrides): Order
    {
        $template = $this->requireFixture(Order::query()->orderBy('id')->first(), 'order');
        $order = $template->replicate();
        $order->forceFill([
            'id' => $this->nextWholesaleDeliverymanOrderId(),
            'business_module' => $businessModule,
            'seller_id' => $deliveryMan->seller_id ?: 1,
            'seller_is' => $deliveryMan->seller_id ? 'seller' : 'admin',
            'is_guest' => 0,
            'order_type' => 'default_type',
            'order_group_id' => 'wholesale-deliveryman-'.Str::lower(Str::random(16)),
            'order_status' => OrderService::STATUS_PENDING,
            'payment_status' => OrderService::PAYMENT_STATUS_UNPAID,
            'payment_method' => OrderService::PAYMENT_METHOD_CASH_ON_DELIVERY,
            'order_amount' => 100,
            'delivery_man_id' => $deliveryMan->id,
            'deliveryman_charge' => 0,
            'expected_delivery_date' => null,
            'delivery_type' => null,
            'is_pause' => 0,
            'created_at' => now(),
            ...$overrides,
        ])->save();

        $this->createWholesaleDeliverymanDetail(order: $order, businessModule: $businessModule);

        return $order;
    }

    private function createWholesaleDeliverymanDetail(Order $order, string $businessModule): OrderDetail
    {
        $product = $this->createWholesaleDeliverymanProduct(businessModule: $businessModule);

        $detail = new OrderDetail;
        $detail->forceFill([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'seller_id' => $order->seller_id,
            'product_details' => json_encode(['id' => $product->id, 'name' => 'Deliveryman fixture', 'product_type' => 'physical']),
            'qty' => self::WHOLESALE_DELIVERYMAN_LINE_QUANTITY,
            'price' => 25,
            'discount' => 0,
            'tax' => 0,
            'tax_model' => 'exclude',
            'variant' => self::WHOLESALE_DELIVERYMAN_VARIANT,
            'variation' => ['Color' => 'Blue', 'Size' => 'Small'],
            'delivery_status' => 'pending',
            'payment_status' => 'unpaid',
            'discount_type' => 'discount_on_product',
            'is_stock_decreased' => 1,
        ])->save();

        return $detail;
    }

    private function createWholesaleDeliverymanProduct(string $businessModule): Product
    {
        $template = $this->requireFixture(Product::query()->withoutGlobalScopes()->orderBy('id')->first(), 'product');
        $product = $template->replicate();
        $product->forceFill([
            'business_module' => $businessModule,
            'product_type' => 'physical',
            'current_stock' => self::WHOLESALE_DELIVERYMAN_CURRENT_STOCK,
            'slug' => 'wholesale-deliveryman-'.Str::lower(Str::random(12)),
            'code' => 'WSDM'.random_int(10000, 99999),
        ])->save();

        ProductStock::query()->create([
            'product_id' => $product->id,
            'variant' => self::WHOLESALE_DELIVERYMAN_VARIANT,
            'sku' => 'WSDM-'.$product->id,
            'price' => 25,
            'qty' => self::WHOLESALE_DELIVERYMAN_VARIANT_STOCK,
        ]);

        return $product;
    }

    private function nextWholesaleDeliverymanOrderId(): int
    {
        if ($this->wholesaleDeliverymanOrderId === 0) {
            $this->wholesaleDeliverymanOrderId = (int) Order::query()->max('id') + random_int(100000, 900000);
        }

        return ++$this->wholesaleDeliverymanOrderId;
    }
}
