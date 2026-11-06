<?php

namespace App\Services\New;

use App\Enums\BusinessModule;
use App\Services\BaseService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection as SupportCollection;
use Modules\TaxModule\app\Models\OrderTax;
use Modules\TaxModule\app\Traits\VatTaxManagement;

class OrderTaxService extends BaseService
{
    use VatTaxManagement;

    public const TAX_ON_BASIC = 'basic';

    private const TAX_PAYER_VENDOR = 'vendor';

    private const ORDER_TYPE_ORDER = 'order';

    private const TAX_FOR_ORDERS = 'tax_for_orders';

    private const SELLER_TYPE_ADMIN = 'admin';

    public function __construct(OrderTax $orderTax)
    {
        parent::__construct($orderTax);
    }

    protected function businessModule(): string
    {
        return BusinessModule::RETAIL;
    }

    public function getList(
        ?string $searchValue = null,
        int|string $dataLimit = DEFAULT_DATA_LIMIT,
        ?int $page = null,
        array $relations = [],
        array $withCount = [],
        array $filters = [],
        ?string $sortBy = null,
    ): Collection|LengthAwarePaginator {
        $query = $this->query()->with($relations)->withCount($withCount);

        $this->applyFilters($query, $filters);

        return $dataLimit === 'all'
            ? $query->get()
            : $query->paginate(perPage: (int) $dataLimit, page: $page)
                ->appends($this->listQueryString($searchValue, $sortBy, $filters));
    }

    public function find(mixed $id, array $relations = []): ?Model
    {
        return $this->query()->with($relations)->find($id);
    }

    public function count(array $filters = []): int
    {
        $query = $this->query();

        $this->applyFilters($query, $filters);

        return $query->count();
    }

    /** @return array{amount: float|int, rate: float|int} */
    public function getAppliedProductTax(array $vendorCart, mixed $cartId, mixed $sellerId, ?string $sellerIs): array
    {
        $appliedTaxAmount = 0;
        $appliedTaxRate = 0;

        foreach ($vendorCart['applied_tax_cart_list'] as $cartItem) {
            if ($cartItem['seller_id'] == $sellerId && $cartItem['seller_is'] == $sellerIs) {
                $appliedTaxAmount = collect($vendorCart['applied_tax_cart_list'])->where('cart_id', $cartId)->sum('applied_tax_amount') ?? 0;
                foreach ($cartItem['applied_tax_ids'] as $taxItem) {
                    if ($taxItem) {
                        $appliedTaxRate += $taxItem['tax_rate'];
                    }
                }
            }
        }

        return ['amount' => $appliedTaxAmount, 'rate' => $appliedTaxRate];
    }

    /** @param array{amount: float|int, rate: float|int} $appliedTax */
    public function createProductTaxes(
        int $orderId,
        array $vendorCart,
        mixed $cartId,
        array $appliedTax,
        float|int $beforeTaxAmount,
        int $quantity,
        mixed $sellerId,
        ?string $sellerIs,
    ): void {
        $taxConfig = self::getTaxSystemType(businessModule: $this->businessModule());

        foreach ($vendorCart['applied_tax_cart_list'] as $cartItem) {
            if ($cartItem['cart_id'] != $cartId) {
                continue;
            }

            foreach ($cartItem['applied_tax_ids'] as $taxItem) {
                if (! $taxItem) {
                    continue;
                }

                $this->insertOrderTax(
                    systemTaxVat: $taxConfig['SystemTaxVat'],
                    taxRate: $taxItem,
                    orderId: $orderId,
                    data: [
                        'tax_amount' => ($appliedTax['amount'] > 0 && $appliedTax['rate'] > 0) ? ($appliedTax['amount'] * $taxItem['tax_rate']) / $appliedTax['rate'] : 0,
                        'before_tax_amount' => $beforeTaxAmount,
                        'after_tax_amount' => $beforeTaxAmount + $appliedTax['amount'],
                        'quantity' => $quantity,
                        'seller_id' => $sellerId,
                        'seller_type' => $sellerIs,
                    ],
                );
            }
        }
    }

    public function createShippingTaxes(int $orderId, array $vendorCart): void
    {
        $taxConfig = self::getTaxSystemType(businessModule: $this->businessModule());
        $taxVats = collect($taxConfig['taxVats']);
        $appliedTaxAmount = 0;
        $appliedTaxRate = 0;

        foreach ($vendorCart['applied_tax_cart_list'] as $cartItem) {
            if ($cartItem['seller_id'] == $vendorCart['seller_id'] && $cartItem['seller_is'] == $vendorCart['seller_is']) {
                $appliedTaxAmount = collect($vendorCart['applied_tax_cart_list'])->where('cart_id', $cartItem['cart_id'])->sum('applied_shipping_cost_tax') ?? 0;
                foreach ($cartItem['applied_shipping_cost_tax_ids'] as $taxIdGroup) {
                    foreach ($taxIdGroup['tax_ids'] as $taxId) {
                        $taxItem = $taxVats->firstWhere('id', $taxId);
                        if ($taxItem) {
                            $appliedTaxRate += $taxItem['tax_rate'];
                        }
                    }
                }
            }
        }

        foreach (collect($vendorCart['applied_tax_cart_list'])->pluck('applied_shipping_cost_tax_ids')->toArray() as $taxGroups) {
            foreach ($taxGroups as $taxGroup) {
                foreach ($taxGroup['tax_ids'] as $taxId) {
                    $taxItem = $taxVats->firstWhere('id', $taxId);
                    if (! $taxItem) {
                        continue;
                    }

                    $this->insertOrderTax(
                        systemTaxVat: $taxConfig['SystemTaxVat'],
                        taxRate: $taxItem,
                        orderId: $orderId,
                        data: [
                            'tax_amount' => ($appliedTaxAmount > 0 && $appliedTaxRate > 0) ? ($appliedTaxAmount * $taxItem['tax_rate']) / $appliedTaxRate : 0,
                            'before_tax_amount' => $vendorCart['order_amount_with_tax'] - $vendorCart['total_tax_amount'],
                            'after_tax_amount' => $vendorCart['order_amount_with_tax'],
                            'quantity' => 0,
                            'seller_id' => $vendorCart['seller_id'],
                            'seller_type' => $vendorCart['seller_is'],
                        ],
                        taxOn: $taxGroup['name'],
                    );
                }
            }
        }
    }

    public function getTaxBreakdown(Collection $orderTaxes): array
    {
        return $orderTaxes->groupBy('tax_on')->sortKeys()
            ->map(fn (SupportCollection $group, string $taxOn): array => [
                'name' => $taxOn === self::TAX_ON_BASIC ? translate('Order tax') : ucwords(str_replace('_', ' ', $taxOn)),
                'rows' => $group->groupBy('tax_name')
                    ->map(fn (SupportCollection $rows, string $taxName): array => [
                        'name' => ucwords($taxName),
                        'tax_rate' => (float) $rows->first()->tax_rate,
                        'total_amount' => (float) $rows->sum('tax_amount'),
                    ])
                    ->values()
                    ->all(),
            ])
            ->values()
            ->all();
    }

    public function deleteOrderTaxesForOrder(int|string $orderId): int
    {
        return $this->query()->where('order_id', $orderId)->where('tax_for', self::TAX_FOR_ORDERS)->delete();
    }

    public function createEditedOrderTaxes(object|array $order, array $taxConfig, array $taxSummary): void
    {
        $lines = collect($taxSummary['applied_tax_cart_list']);
        $taxVats = collect($taxConfig['taxVats']);
        $orderRows = [
            'before_tax_amount' => $taxSummary['order_amount_with_tax'] - $taxSummary['total_tax_amount'],
            'after_tax_amount' => $taxSummary['order_amount_with_tax'],
            'seller_id' => $order['seller_id'],
            'seller_type' => $order['seller_is'],
        ];

        $productTaxAmount = $lines->sum('applied_tax_amount');
        $productTaxRate = 0;
        foreach ($lines as $line) {
            foreach ($line['applied_tax_ids'] as $taxItem) {
                if ($taxItem) {
                    $productTaxRate += $taxItem['tax_rate'];
                }
            }
        }

        foreach ($lines as $line) {
            foreach ($line['applied_tax_ids'] as $taxItem) {
                if (! $taxItem) {
                    continue;
                }

                $this->insertOrderTax(
                    systemTaxVat: $taxConfig['SystemTaxVat'],
                    taxRate: $taxItem,
                    orderId: (int) $order['id'],
                    data: [
                        'tax_amount' => ($productTaxAmount > 0 && $productTaxRate > 0) ? ($productTaxAmount * $taxItem['tax_rate']) / $productTaxRate : 0,
                        'quantity' => $line['quantity'],
                    ] + $orderRows,
                );
            }
        }

        $shippingTaxAmount = $lines->sum('applied_shipping_cost_tax');
        $shippingTaxRate = 0;
        foreach ($lines as $line) {
            foreach ($line['applied_shipping_cost_tax_ids'] as $taxIdGroup) {
                foreach ($taxIdGroup['tax_ids'] as $taxId) {
                    $taxItem = $taxVats->firstWhere('id', $taxId);
                    if ($taxItem) {
                        $shippingTaxRate += $taxItem['tax_rate'];
                    }
                }
            }
        }

        foreach ($lines->pluck('applied_shipping_cost_tax_ids')->toArray() as $taxGroups) {
            foreach ($taxGroups as $taxGroup) {
                foreach ($taxGroup['tax_ids'] as $taxId) {
                    $taxItem = $taxVats->firstWhere('id', $taxId);
                    if (! $taxItem) {
                        continue;
                    }

                    $this->insertOrderTax(
                        systemTaxVat: $taxConfig['SystemTaxVat'],
                        taxRate: $taxItem,
                        orderId: (int) $order['id'],
                        data: [
                            'tax_amount' => ($shippingTaxAmount > 0 && $shippingTaxRate > 0) ? ($shippingTaxAmount * $taxItem['tax_rate']) / $shippingTaxRate : 0,
                            'quantity' => 0,
                        ] + $orderRows,
                        taxOn: $taxGroup['name'],
                    );
                }
            }
        }
    }

    private function insertOrderTax(object|array $systemTaxVat, object|array $taxRate, int $orderId, array $data, string $taxOn = self::TAX_ON_BASIC): void
    {
        $this->query()->insert([
            'tax_name' => $taxRate['name'],
            'tax_type' => $systemTaxVat->tax_type,
            'tax_on' => $taxOn,
            'tax_rate' => $taxRate['tax_rate'],
            'tax_amount' => $data['tax_amount'] ?? 0,
            'before_tax_amount' => $data['before_tax_amount'] ?? 0,
            'after_tax_amount' => $data['after_tax_amount'] ?? 0,
            'tax_payer' => self::TAX_PAYER_VENDOR,
            'order_id' => $orderId,
            'order_type' => self::ORDER_TYPE_ORDER,
            'quantity' => $data['quantity'] ?? 0,
            'tax_id' => $taxRate['id'],
            'taxable_id' => '',
            'taxable_type' => '',
            'seller_id' => $data['seller_id'] ?? 0,
            'seller_type' => $data['seller_type'] ?? self::SELLER_TYPE_ADMIN,
            'system_tax_setup_id' => $systemTaxVat->id,
            'tax_for' => self::TAX_FOR_ORDERS,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
