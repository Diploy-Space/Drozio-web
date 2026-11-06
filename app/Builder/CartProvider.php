<?php

namespace App\Builder;

use App\Builder\Resources\ItemCardResource;
use App\Enums\BusinessModule;
use App\Models\Cart;
use App\Utils\CartManager;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Modules\Builder\app\Contracts\CartProvider as CartProviderContract;
use Modules\Builder\app\Services\StorefrontContext;

class CartProvider implements CartProviderContract
{
    public function __construct(private StorefrontContext $context)
    {
    }

    public function list(): array
    {
        $rows = $this->baseQuery()->get()->map(fn (Cart $row) => $this->formatRow($row))->values()->all();
        return $this->withTotals($rows);
    }

    public function add(array $payload): array
    {
        $variant = $this->resolveVariant($payload);

        $result = CartManager::add_to_cart($this->buildAddRequest($payload, $variant), true);

        if (($result['status'] ?? 1) === 0) {
            throw ValidationException::withMessages(['item_id' => $result['message'] ?? translate('Could not add to cart')]);
        }

        return $this->list();
    }

    public function update(int $cartId, array $payload): array
    {
        $this->ownedRow($cartId);

        $request = new Request();
        $request->merge([
            'key' => $cartId,
            'quantity' => max(1, (int) ($payload['quantity'] ?? 1)),
            'buy_now' => 0,
        ]);

        $result = CartManager::update_cart_qty($request);

        if (($result['status'] ?? 1) === 0) {
            throw ValidationException::withMessages(['quantity' => $result['message'] ?? translate('Sorry stock is limited')]);
        }

        return $this->list();
    }

    public function remove(int $cartId): array
    {
        $this->ownedRow($cartId)->delete();
        return $this->list();
    }

    public function clear(): array
    {
        $this->baseQuery()->delete();
        return $this->list();
    }

    public function count(): int
    {
        return $this->baseQuery()->count();
    }

    private function buildAddRequest(array $payload, ?string $variant): Request
    {
        $request = new Request();
        $request->merge([
            'id' => (int) ($payload['item_id'] ?? 0),
            'quantity' => max(1, (int) ($payload['quantity'] ?? 1)),
            'buy_now' => 0,
            'shipping_method_exist' => $variant ? 1 : 0,
            'product_variation_code' => $variant,
            'variant_key' => $variant,
            'guest_id' => $this->context->getGuestId(),
        ]);

        return $request;
    }

    private function resolveVariant(array $payload): ?string
    {
        $variation = $payload['variation'] ?? null;
        $variationType = null;
        if (is_array($variation)) {
            $variationType = $variation[0]['type'] ?? $variation['type'] ?? null;
        }

        $candidates = [
            $payload['variant'] ?? null,
            $payload['product_variation_code'] ?? null,
            $variationType,
        ];

        foreach ($candidates as $candidate) {
            if (is_string($candidate) && $candidate !== '') {
                return str_replace(' ', '', $candidate);
            }
        }

        return null;
    }

    private function baseQuery()
    {
        $shopperId = $this->context->getShopperId();
        if ($shopperId === null) {
            return Cart::query()->whereRaw('1 = 0');
        }

        $query = Cart::query()
            ->businessModule(BusinessModule::RETAIL)
            ->where('customer_id', $shopperId)
            ->where('is_guest', $this->context->shopperIsGuestFlag());

        $vendorId = $this->context->getVendorId();
        if ($vendorId) {
            $query->where('seller_id', $vendorId)->where('seller_is', 'seller');
        }

        return $query;
    }

    private function ownedRow(int $cartId): Cart
    {
        $cart = (clone $this->baseQuery())->where('id', $cartId)->first();

        if (!$cart) {
            throw ValidationException::withMessages(['cart_id' => translate('Product not found in cart') ?: 'Cart item not found']);
        }
        return $cart;
    }

    private function formatRow(Cart $row): array
    {
        $lineTotal = ((float) $row->price - (float) $row->discount) * (int) $row->quantity;

        $product = $row->product;
        $formatted = $product ? ItemCardResource::fromOne($product) : null;

        return [
            'id'          => $row->id,
            'item_id'     => $row->product_id,
            'item_type'   => 'Item',
            'price'       => (float) webCurrencyConverterOnlyDigit(amount: $lineTotal),
            'quantity'    => (int) $row->quantity,
            'variation'   => $this->formatVariation($row),
            'add_on_ids'  => [],
            'add_on_qtys' => [],
            'item'        => $formatted,
        ];
    }

    private function formatVariation(Cart $row): array
    {
        $label = collect($this->decodeJson($row->variations))
            ->filter(fn ($value) => \filled($value))
            ->implode(', ');

        if ($label === '') {
            $label = \is_string($row->variant) ? \trim($row->variant) : '';
        }

        if ($label === '') {
            return [];
        }

        return [[
            'type'  => $label,
            'price' => (float) webCurrencyConverterOnlyDigit(amount: (float) $row->price),
            'stock' => (int) $row->quantity,
        ]];
    }

    private function withTotals(array $rows): array
    {
        $quantity = 0;
        $subtotal = 0.0;
        foreach ($rows as $row) {
            $quantity += (int) $row['quantity'];
            $subtotal += (float) $row['price'];
        }

        return [
            'items'  => $rows,
            'totals' => [
                'count'    => \count($rows),
                'quantity' => $quantity,
                'subtotal' => round($subtotal, $this->context->getDigitAfterDecimalPoint()),
            ],
        ];
    }

    private function decodeJson($value): array
    {
        if (\is_array($value)) {
            return $value;
        }
        if (\is_string($value) && $value !== '') {
            $decoded = \json_decode($value, true);
            return \is_array($decoded) ? $decoded : [];
        }
        return [];
    }
}
