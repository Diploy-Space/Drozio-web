<?php

namespace App\Utils;

use Illuminate\Database\Eloquent\Model;

class ProductGalleryVariations
{
    public const DIGITAL_READY_PRODUCT = 'ready_product';

    public function buildForProducts(iterable $products): array
    {
        $sections = [];

        foreach ($products as $product) {
            $sections[$product->id] = [
                'availableVariations' => $this->buildAvailableVariations($product),
                'variationRows' => $this->buildVariationRows($product),
                'digitalVariationRows' => $this->buildDigitalVariationRows($product),
                'hasDigitalFileDownload' => $product->retailDetail?->digital_product_type === self::DIGITAL_READY_PRODUCT,
            ];
        }

        return $sections;
    }

    private function buildAvailableVariations(Model $product): array
    {
        $rows = [];
        $colorNames = $this->buildColorNames($product);

        if ($colorNames !== []) {
            $rows[] = ['label' => translate('Color'), 'value' => implode(', ', $colorNames)];
        }

        foreach ($this->buildChoiceOptions($product) as $choice) {
            $rows[] = ['label' => $choice->title, 'value' => implode(', ', $choice->options)];
        }

        foreach ($this->buildDigitalExtensions($product) as $extensionName => $extensions) {
            $rows[] = ['label' => $extensionName, 'value' => implode(', ', $extensions)];
        }

        return $rows;
    }

    private function buildColorNames(Model $product): array
    {
        $codes = json_decode($product->colors ?? '[]', true) ?: [];

        return array_map(static fn (string $code): string => getColorNameByCode(code: $code) ?: $code, $codes);
    }

    private function buildChoiceOptions(Model $product): array
    {
        if ($product->product_type !== 'physical' || blank($product->choice_options)) {
            return [];
        }

        return json_decode($product->choice_options) ?: [];
    }

    private function buildDigitalExtensions(Model $product): array
    {
        if ($product->product_type !== 'digital' || blank($product->retailDetail?->digital_product_extensions)) {
            return [];
        }

        return $product->retailDetail->digital_product_extensions;
    }

    private function buildVariationRows(Model $product): array
    {
        $variations = json_decode($product->retailDetail?->variation ?? '[]') ?: [];

        return array_map(static fn (object $variation): array => [
            'variation' => $variation->type ?? '-',
            'sku' => $variation->sku,
            'price' => setCurrencySymbol(
                amount: usdToDefaultCurrency(amount: $variation->price),
                currencyCode: getCurrencyCode(),
            ),
            'stock' => $variation->qty,
        ], $variations);
    }

    private function buildDigitalVariationRows(Model $product): array
    {
        $variations = $product->digitalVariation ?? [];
        $rows = [];

        foreach ($variations as $index => $variation) {
            $rows[] = [
                'serial' => $index + 1,
                'name' => $variation->variant_key ?? '-',
                'sku' => $variation->sku,
                'price' => setCurrencySymbol(
                    amount: usdToDefaultCurrency(amount: $variation->price),
                    currencyCode: getCurrencyCode(),
                ),
                'filePath' => $variation->file_full_url['path'] ?? null,
            ];
        }

        return $rows;
    }
}
