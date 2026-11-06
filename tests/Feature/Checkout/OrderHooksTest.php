<?php

namespace Tests\Feature\Checkout;

use App\Enums\BusinessModule;
use App\Models\BusinessSetting;
use App\Models\Cart;
use App\Models\Order;
use App\Models\Seller;
use App\Services\New\OrderService as RetailOrderService;
use App\Services\New\OrderTaxService as RetailOrderTaxService;
use App\Utils\Helpers;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Modules\TaxModule\app\Models\OrderTax;
use Modules\TaxModule\app\Models\SystemTaxSetup;
use Modules\TaxModule\app\Models\Tax;
use Modules\Wholesale\app\Services\BusinessSettingService;
use Modules\Wholesale\app\Services\OrderService;
use Modules\Wholesale\app\Services\OrderTaxService;
use Modules\Wholesale\app\Services\Shipping\ShippingMethodService;
use ReflectionFunction;
use ReflectionMethod;
use Tests\Feature\Api\Support\InteractsWithApiTestEnvironment;
use Tests\Feature\Wholesale\Concerns\InteractsWithWholesaleAddon;
use Tests\TestCase;

class OrderHooksTest extends TestCase
{
    use DatabaseTransactions;
    use InteractsWithApiTestEnvironment;
    use InteractsWithWholesaleAddon;

    private const SELLER_ID = 990031;

    private const SHIPPING_ADDRESS_ID = 990032;

    private const BILLING_ADDRESS_ID = 990033;

    private const CART_ID = 990101;

    private const SECOND_CART_ID = 990102;

    private const SHIPPING_TAX_GROUP = 'tax_on_delivery_charge';

    private const MISSING_TAX_ID = 999999999;

    protected function setUp(): void
    {
        $this->skipWhenApiDatabaseUnavailable();

        parent::setUp();

        Cache::flush();
        Helpers::resetMemo();
    }

    public function test_retail_commission_uses_sales_commission_and_seller_rate(): void
    {
        $service = app(RetailOrderService::class);

        $this->assertSame([
            'setting' => 'sales_commission',
            'seller_column' => 'sales_commission_percentage',
            'vendor_wise_status_setting' => null,
        ], $this->callHook(service: $service, method: 'commissionSettings'));

        $globalRate = (float) getWebConfig(name: 'sales_commission');

        $this->assertSame(round(180 * $globalRate / 100, 2), $this->commission(service: $service, seller: null));
        $this->assertSame(round(180 * $globalRate / 100, 2), $this->commission(service: $service, seller: $this->seller(retailRate: null, wholesaleRate: 3)));
        $this->assertSame(36.0, $this->commission(service: $service, seller: $this->seller(retailRate: 20, wholesaleRate: 3)));
        $this->assertSame(0.0, $this->commission(service: $service, seller: $this->seller(retailRate: 20, wholesaleRate: 3), sellerIs: 'admin'));
    }

    public function test_wholesale_commission_uses_wholesale_commission(): void
    {
        $this->skipUnlessWholesaleAddonPublished();

        $this->assertSame([
            'setting' => BusinessSettingService::COMMISSION_KEY,
            'seller_column' => 'wholesale_commission_percentage',
            'vendor_wise_status_setting' => 'wholesale_vendor_wise_commission_status',
        ], $this->callHook(service: app(OrderService::class), method: 'commissionSettings'));

        $service = $this->wholesaleOrderServiceReading(globalRate: 5, isVendorWiseRateOn: false);

        $this->assertSame(9.0, $this->commission(service: $service, seller: $this->seller(retailRate: 20, wholesaleRate: null)));
        $this->assertSame(9.0, $this->commission(service: $service, seller: null));
    }

    public function test_wholesale_vendor_wise_rate_applies_only_when_the_setting_is_on(): void
    {
        $this->skipUnlessWholesaleAddonPublished();

        $seller = $this->seller(retailRate: 20, wholesaleRate: 3);

        $this->assertSame(9.0, $this->commission(service: $this->wholesaleOrderServiceReading(globalRate: 5, isVendorWiseRateOn: false), seller: $seller));
        $this->assertSame(5.4, $this->commission(service: $this->wholesaleOrderServiceReading(globalRate: 5, isVendorWiseRateOn: true), seller: $seller));
        $this->assertSame(9.0, $this->commission(service: $this->wholesaleOrderServiceReading(globalRate: 5, isVendorWiseRateOn: true), seller: $this->seller(retailRate: 20, wholesaleRate: null)));
    }

    public function test_retail_placed_order_billing_reads_billing_input_by_customer(): void
    {
        $service = app(RetailOrderService::class);

        $this->assertSame('billing_input_by_customer', $this->callHook(service: $service, method: 'billingInputSetting'));
        $this->assertSame('shipping_method', $this->callHook(service: $service, method: 'shippingResponsibilitySetting'));

        $order = $this->placeOrder(service: $service);
        $isBillingInputByCustomer = (bool) getWebConfig(name: 'billing_input_by_customer');

        $this->assertSame(BusinessModule::RETAIL, $order->business_module);
        $this->assertSame($isBillingInputByCustomer ? self::BILLING_ADDRESS_ID : null, $order->billing_address === null ? null : (int) $order->billing_address);
        $this->assertSame(getWebConfig(name: 'shipping_method'), $order->shipping_responsibility);
        $this->assertSame(RetailOrderService::getTaxSystemType()['SystemTaxVatType'], $order->tax_type);
    }

    public function test_wholesale_placed_order_billing_reads_the_wholesale_setting(): void
    {
        $this->skipUnlessWholesaleAddonPublished();

        $service = app(OrderService::class);

        $this->assertSame('wholesale_billing_address_status', $this->callHook(service: $service, method: 'billingInputSetting'));

        if (! getWebConfig(name: 'billing_input_by_customer') || getWebConfig(name: 'wholesale_billing_address_status')) {
            $this->markTestSkipped('Test database needs retail billing input on and wholesale billing address off.');
        }

        $order = $this->placeOrder(service: $service);

        $this->assertSame(BusinessModule::WHOLESALE, $order->business_module);
        $this->assertNull($order->billing_address);
        $this->assertNull($order->getRawOriginal('billing_address_data'));
    }

    public function test_wholesale_placed_order_tax_columns_are_correct_in_one_write(): void
    {
        $this->skipUnlessWholesaleAddonPublished();

        $service = app(OrderService::class);

        $this->assertSame(ShippingMethodService::RESPONSIBILITY_SETTING, $this->callHook(service: $service, method: 'shippingResponsibilitySetting'));

        $this->makeWholesaleTaxModelDifferFromRetail();
        $orderWrites = [];
        DB::listen(static function (QueryExecuted $query) use (&$orderWrites): void {
            if (preg_match('/^\s*(insert into|update)\s+`orders`/i', $query->sql) === 1) {
                $orderWrites[] = $query->sql;
            }
        });

        $order = $this->placeOrder(service: $service);
        $wholesaleTaxConfig = OrderService::getTaxSystemType(businessModule: BusinessModule::WHOLESALE);

        $this->assertCount(1, $orderWrites);
        $this->assertStringStartsWith('insert', strtolower(trim($orderWrites[0])));
        $this->assertSame($wholesaleTaxConfig['SystemTaxVatType'], $order->tax_type);
        $this->assertSame($wholesaleTaxConfig['is_included'] ? 'include' : 'exclude', $order->tax_model);
        $this->assertNotSame(OrderService::getTaxSystemType()['is_included'] ? 'include' : 'exclude', $order->tax_model);
        $this->assertSame(getWebConfig(name: ShippingMethodService::RESPONSIBILITY_SETTING), $order->shipping_responsibility);
    }

    public function test_retail_order_taxes_are_unchanged(): void
    {
        $service = app(RetailOrderTaxService::class);

        $this->assertSame(BusinessModule::RETAIL, $this->callHook(service: $service, method: 'businessModule'));

        $taxConfig = RetailOrderTaxService::getTaxSystemType();
        $rows = $this->createOrderTaxes(service: $service, businessModule: BusinessModule::RETAIL, taxConfig: $taxConfig);

        $this->assertOrderTaxRows(rows: $rows, systemTaxSetupId: (int) $taxConfig['SystemTaxVat']->id, taxType: $taxConfig['SystemTaxVat']->tax_type);
    }

    public function test_wholesale_order_taxes_match_the_pre_refactor_rows(): void
    {
        $service = app(OrderTaxService::class);

        $this->assertSame(BusinessModule::WHOLESALE, $this->callHook(service: $service, method: 'businessModule'));

        $taxConfig = OrderTaxService::getTaxSystemType(businessModule: BusinessModule::WHOLESALE);
        $retailSetupId = RetailOrderTaxService::getTaxSystemType()['SystemTaxVat']?->id;
        $rows = $this->createOrderTaxes(service: $service, businessModule: BusinessModule::WHOLESALE, taxConfig: $taxConfig);

        $this->assertNotSame($retailSetupId, $taxConfig['SystemTaxVat']->id);
        $this->assertOrderTaxRows(rows: $rows, systemTaxSetupId: (int) $taxConfig['SystemTaxVat']->id, taxType: $taxConfig['SystemTaxVat']->tax_type);
    }

    private function commission(RetailOrderService $service, ?Seller $seller, string $sellerIs = 'seller'): float
    {
        $cartItems = collect([
            (new Cart)->forceFill(['seller_id' => self::SELLER_ID, 'seller_is' => $sellerIs, 'price' => 100, 'discount' => 10, 'quantity' => 1]),
            (new Cart)->forceFill(['seller_id' => self::SELLER_ID, 'seller_is' => $sellerIs, 'price' => 50, 'discount' => 5, 'quantity' => 2]),
        ]);

        return $service->calculateAdminCommission(groupCartItems: $cartItems, coupon: null, couponDiscount: 0, seller: $seller);
    }

    private function seller(int|float|null $retailRate, int|float|null $wholesaleRate): Seller
    {
        return (new Seller)->forceFill([
            'id' => self::SELLER_ID,
            'sales_commission_percentage' => $retailRate,
            'wholesale_commission_percentage' => $wholesaleRate,
        ]);
    }

    private function wholesaleOrderServiceReading(int|float $globalRate, bool $isVendorWiseRateOn): OrderService
    {
        $service = new class(app(Order::class)) extends OrderService
        {
            public array $settingNames = [];

            protected function commissionSettings(): array
            {
                return [...parent::commissionSettings(), ...$this->settingNames];
            }
        };

        $service->settingNames = [
            'setting' => $this->useSetting(value: $globalRate),
            'vendor_wise_status_setting' => $this->useSetting(value: (int) $isVendorWiseRateOn),
        ];

        return $service;
    }

    private function useSetting(int|float $value): string
    {
        $name = 'order_hooks_fixture_'.Str::lower(Str::random(16));
        $setting = BusinessSetting::query()->create(['type' => $name, 'value' => $value]);

        getWebConfig(name: 'order_hooks_loader_'.Str::lower(Str::random(16)));
        (new ReflectionFunction('getWebConfig'))->getStaticVariables()['businessSettings']->push($setting);

        return $name;
    }

    private function placeOrder(RetailOrderService $service): Order
    {
        $orderId = (int) Order::query()->max('id') + random_int(100000, 900000);

        $service->createPlacedOrder(
            orderId: $orderId,
            orderGroupId: 'order-hooks-'.Str::random(8),
            customerId: 0,
            isGuest: 1,
            vendorCart: [
                'seller_id' => self::SELLER_ID,
                'seller_is' => 'seller',
                'coupon_discount' => 0,
                'discount_type' => null,
                'coupon_code' => null,
                'coupon_bearer' => 'inhouse',
                'order_amount_with_tax' => 1100,
                'refer_and_earn_discount' => 0,
                'total_tax_amount' => 100,
                'shipping_address_id' => self::SHIPPING_ADDRESS_ID,
                'billing_address_id' => self::BILLING_ADDRESS_ID,
                'shipping_cost' => 0,
                'free_delivery_discount' => 0,
                'extra_discount_type' => null,
                'free_delivery_bearer' => null,
                'is_shipping_free' => 0,
                'shipping_method_id' => 0,
                'shipping_type' => null,
            ],
            orderData: [
                'payment_status' => RetailOrderService::PAYMENT_STATUS_UNPAID,
                'order_status' => RetailOrderService::STATUS_PENDING,
                'payment_method' => RetailOrderService::PAYMENT_METHOD_CASH_ON_DELIVERY,
            ],
            adminCommission: 0.0,
            shippingAddress: null,
            billingAddress: null,
        );

        return Order::query()->findOrFail($orderId);
    }

    private function makeWholesaleTaxModelDifferFromRetail(): void
    {
        $isRetailTaxIncluded = (bool) OrderService::getTaxSystemType()['is_included'];

        $updatedRows = SystemTaxSetup::query()
            ->where(['business_module' => BusinessModule::WHOLESALE, 'tax_payer' => 'vendor', 'is_default' => 1, 'is_active' => 1])
            ->update(['is_included' => $isRetailTaxIncluded ? 0 : 1]);

        if ($updatedRows === 0) {
            $this->markTestSkipped('Test database has no active default wholesale tax setup.');
        }

        Cache::flush();
    }

    /** @return Collection<int, OrderTax> */
    private function createOrderTaxes(RetailOrderTaxService $service, string $businessModule, array $taxConfig): Collection
    {
        if ($taxConfig['SystemTaxVat'] === null || $taxConfig['SystemTaxVatType'] !== 'product_wise') {
            $this->markTestSkipped('Test database has no active product-wise '.$businessModule.' tax setup.');
        }

        $taxes = Tax::query()->where(['business_module' => $businessModule, 'is_active' => 1])->where('is_default', 1)->whereIn('tax_rate', [8, 2])->orderByDesc('id')->get()->keyBy(static fn (Tax $tax): int => (int) $tax->tax_rate);

        if ($taxes->count() < 2) {
            $this->markTestSkipped('Test database has no active 8% and 2% '.$businessModule.' taxes.');
        }

        $productTaxes = [$taxes[8]->toArray(), $taxes[2]->toArray(), null];
        $shippingTaxId = (int) $taxes[8]->id;

        $orderId = (int) OrderTax::query()->max('order_id') + random_int(100000, 900000);
        $vendorCart = [
            'seller_id' => self::SELLER_ID,
            'seller_is' => 'seller',
            'order_amount_with_tax' => 1152,
            'total_tax_amount' => 152,
            'applied_tax_cart_list' => [
                [
                    'cart_id' => self::CART_ID,
                    'seller_id' => self::SELLER_ID,
                    'seller_is' => 'seller',
                    'applied_tax_ids' => $productTaxes,
                    'applied_tax_amount' => 100,
                    'applied_shipping_cost_tax' => 12,
                    'applied_shipping_cost_tax_ids' => [['name' => self::SHIPPING_TAX_GROUP, 'tax_ids' => [$shippingTaxId, self::MISSING_TAX_ID]]],
                ],
                [
                    'cart_id' => self::SECOND_CART_ID,
                    'seller_id' => self::SELLER_ID,
                    'seller_is' => 'seller',
                    'applied_tax_ids' => [$taxes[8]->toArray()],
                    'applied_tax_amount' => 40,
                    'applied_shipping_cost_tax' => 8,
                    'applied_shipping_cost_tax_ids' => [['name' => self::SHIPPING_TAX_GROUP, 'tax_ids' => [$shippingTaxId]]],
                ],
            ],
        ];

        $service->createShippingTaxes(orderId: $orderId, vendorCart: $vendorCart);
        $service->createProductTaxes(
            orderId: $orderId,
            vendorCart: $vendorCart,
            cartId: self::CART_ID,
            appliedTax: ['amount' => 100, 'rate' => 10],
            beforeTaxAmount: 1000,
            quantity: 10,
            sellerId: self::SELLER_ID,
            sellerIs: 'seller',
        );

        return OrderTax::query()->where('order_id', $orderId)->orderBy('id')->get();
    }

    /** @param Collection<int, OrderTax> $rows */
    private function assertOrderTaxRows(Collection $rows, int $systemTaxSetupId, string $taxType): void
    {
        $expected = [
            [self::SHIPPING_TAX_GROUP, 8.0, 4.0, 1000.0, 1152.0, 0],
            [self::SHIPPING_TAX_GROUP, 8.0, 4.0, 1000.0, 1152.0, 0],
            [RetailOrderTaxService::TAX_ON_BASIC, 8.0, 80.0, 1000.0, 1100.0, 10],
            [RetailOrderTaxService::TAX_ON_BASIC, 2.0, 20.0, 1000.0, 1100.0, 10],
        ];

        $this->assertCount(count($expected), $rows);

        foreach ($expected as $index => [$taxOn, $taxRate, $taxAmount, $beforeTaxAmount, $afterTaxAmount, $quantity]) {
            $row = $rows[$index];

            $this->assertSame($taxOn, $row->tax_on);
            $this->assertEqualsWithDelta($taxRate, (float) $row->tax_rate, 0.000001);
            $this->assertEqualsWithDelta($taxAmount, (float) $row->tax_amount, 0.000001);
            $this->assertEqualsWithDelta($beforeTaxAmount, (float) $row->before_tax_amount, 0.000001);
            $this->assertEqualsWithDelta($afterTaxAmount, (float) $row->after_tax_amount, 0.000001);
            $this->assertSame($quantity, (int) $row->quantity);
            $this->assertSame($systemTaxSetupId, (int) $row->system_tax_setup_id);
            $this->assertSame($taxType, $row->tax_type);
            $this->assertSame('vendor', $row->tax_payer);
            $this->assertSame(self::SELLER_ID, (int) $row->seller_id);
            $this->assertSame('seller', $row->seller_type);
            $this->assertSame('tax_for_orders', $row->tax_for);
        }
    }

    private function callHook(object $service, string $method): mixed
    {
        return (new ReflectionMethod($service, $method))->invoke($service);
    }
}
