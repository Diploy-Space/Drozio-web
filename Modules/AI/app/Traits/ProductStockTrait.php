<?php

namespace Modules\AI\app\Traits;

trait ProductStockTrait
{
    protected function availableStockFor(array $product, ?string $variantType): ?int
    {
        if (($product['product_type'] ?? 'physical') === 'digital' || ! empty($product['digital_variations'])) {
            return null;
        }

        if (! empty($product['variation'])) {
            $variantType = trim((string) $variantType);
            if ($variantType === '') {
                return null;
            }

            $variant = collect($product['variation'])->first(
                fn ($v) => strcasecmp((string) ($v['type'] ?? ''), $variantType) === 0,
            );

            return $variant ? (int) ($variant['qty'] ?? 0) : null;
        }

        return (int) ($product['current_stock'] ?? 0);
    }
}
