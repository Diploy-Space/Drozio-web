<?php

namespace Modules\AI\app\Http\Resources\ShoppingAssistant;

use App\Utils\Helpers;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Collection as SupportCollection;
use Throwable;

class ProductCardResource extends JsonResource
{
    public function __construct(mixed $resource, private readonly array $colorNameMap = [])
    {
        parent::__construct($resource);
    }

    /** @param iterable<int, Model> $products @return array<int, string> */
    public static function collectColorCodes(iterable $products): array
    {
        $codes = [];
        foreach ($products as $product) {
            foreach (self::extractColorCodes($product) as $code) {
                $codes[$code] = true;
            }
        }

        return array_keys($codes);
    }

    /** @param array<int, Model> $products */
    public static function loadDigitalVariations(array $products): void
    {
        $needsDigital = array_values(array_filter(
            $products,
            fn (Model $product) => ($product->product_type ?? 'physical') === 'digital'
                && ! $product->relationLoaded('digitalVariation'),
        ));

        if ($needsDigital !== []) {
            (new Collection($needsDigital))->load('digitalVariation');
        }
    }

    public function toArray(Request $request): array
    {
        $product = $this->resource;

        $unitPrice = (float) ($product->unit_price ?? 0);
        $discountAmt = Helpers::getProductDiscount(product: $product, price: $unitPrice);
        $discountedPrice = max(0.0, $unitPrice - $discountAmt);

        $thumbnailUrl = '';
        try {
            $thumbnailUrl = \getStorageImages(path: $product->thumbnail_full_url, type: 'product');
        } catch (Throwable) {
        }

        $ratings = collect($product->rating ?? []);
        $avgRating = round((float) $ratings->avg('rating'), 1);
        $ratingCount = $ratings->count();

        return [
            'id' => $product->id,
            'name' => $product->name ?? '',
            'slug' => $product->slug ?? '',
            'thumbnail_full_url' => $thumbnailUrl,
            'unit_price' => webCurrencyConverterOnlyDigit($unitPrice),
            'discount' => (float) ($product->discount ?? 0),
            'discount_type' => $product->discount_type ?? 'flat',
            'discounted_price' => webCurrencyConverterOnlyDigit($discountedPrice),
            'unit_price_formatted' => webCurrencyConverter($unitPrice),
            'discounted_price_formatted' => webCurrencyConverter($discountedPrice),
            'avg_rating' => $avgRating,
            'rating_count' => $ratingCount,
            'product_type' => $product->product_type ?? 'physical',
            'colors' => $this->parseColors($product),
            'color_images' => $this->parseColorImages($product),
            'choice_options' => $this->parseChoiceOptions($product),
            'variation' => $this->parseVariation($product),
            'digital_variations' => $this->parseDigitalVariations($product),
            'digital_product_extensions' => $this->parseDigitalProductExtensions($product),
            'current_stock' => (int) ($product->current_stock ?? 0),
            'minimum_order_qty' => (int) ($product->minimum_order_qty ?? 1),
        ];
    }

    private function parseColorImages(mixed $product): array
    {
        try {
            $entries = $product->color_images_full_url ?? [];
            if (empty($entries)) {
                return [];
            }

            return array_values(array_filter(array_map(function ($item) {
                $item = (array) $item;
                $code = ltrim((string) ($item['color'] ?? ''), '#');
                if ($code === '') {
                    return null;
                }
                $url = \getStorageImages(path: $item['image_name'] ?? [], type: 'product');

                return ['code' => '#'.$code, 'url' => $url];
            }, $entries)));
        } catch (Throwable) {
            return [];
        }
    }

    private static function extractColorCodes(mixed $product): array
    {
        try {
            $raw = $product->colors ?? '[]';
            $codes = is_array($raw) ? $raw : (json_decode($raw, true) ?? []);

            return array_values(array_filter($codes, fn ($c) => is_string($c) && $c !== ''));
        } catch (Throwable) {
            return [];
        }
    }

    private function parseColors(mixed $product): array
    {
        $codes = self::extractColorCodes($product);
        if (empty($codes)) {
            return [];
        }

        return array_map(fn ($code) => [
            'code' => $code,
            'name' => $this->colorNameMap[$code] ?? ltrim($code, '#'),
        ], $codes);
    }

    private function parseChoiceOptions(mixed $product): array
    {
        try {
            $raw = $product->choice_options ?? '[]';
            $parsed = is_array($raw) ? $raw : (json_decode($raw, true) ?? []);

            if (empty($parsed)) {
                return [];
            }

            return array_values(array_map(function ($choice) {
                $isArr = is_array($choice);

                return [
                    'title' => (string) ($isArr ? ($choice['title'] ?? '') : ($choice->title ?? '')),
                    'name' => (string) ($isArr ? ($choice['name'] ?? '') : ($choice->name ?? '')),
                    'options' => array_values((array) ($isArr ? ($choice['options'] ?? []) : ($choice->options ?? []))),
                ];
            }, $parsed));
        } catch (Throwable) {
            return [];
        }
    }

    private function parseVariation(mixed $product): array
    {
        try {
            $raw = is_array($product) ? ($product['variation'] ?? '[]') : ($product->retailDetail?->variation ?? '[]');
            $parsed = is_array($raw) ? $raw : (json_decode($raw, true) ?? []);

            if (empty($parsed)) {
                return [];
            }

            return array_values(array_map(function ($var) {
                $isArr = is_array($var);
                $basePrice = (float) ($isArr ? ($var['price'] ?? 0) : ($var->price ?? 0));

                return [
                    'type' => (string) ($isArr ? ($var['type'] ?? '') : ($var->type ?? '')),
                    'price' => (float) webCurrencyConverterOnlyDigit($basePrice),
                    'sku' => (string) ($isArr ? ($var['sku'] ?? '') : ($var->sku ?? '')),
                    'qty' => (int) ($isArr ? ($var['qty'] ?? 0) : ($var->qty ?? 0)),
                ];
            }, $parsed));
        } catch (Throwable) {
            return [];
        }
    }

    private function parseDigitalVariations(mixed $product): array
    {
        if (($product->product_type ?? 'physical') !== 'digital') {
            return [];
        }

        try {
            $variations = $product->digitalVariation ?? [];

            return array_values(array_map(function ($var) {
                $key = (string) ($var->variant_key ?? '');

                return [
                    'variant_key' => $key,
                    'label' => str_replace('_', ' ', $key),
                    'price' => (float) webCurrencyConverterOnlyDigit((float) ($var->price ?? 0)),
                    'sku' => (string) ($var->sku ?? ''),
                ];
            }, $variations instanceof SupportCollection ? $variations->all() : (array) $variations));
        } catch (Throwable) {
            return [];
        }
    }

    private function parseDigitalProductExtensions(mixed $product): array
    {
        if (($product->product_type ?? 'physical') !== 'digital') {
            return [];
        }

        try {
            $raw = is_array($product) ? ($product['digital_product_extensions'] ?? []) : ($product->retailDetail?->digital_product_extensions ?? []);
            $parsed = is_array($raw) ? $raw : (json_decode($raw, true) ?? []);

            if (empty($parsed)) {
                return [];
            }

            $extensions = [];
            foreach ($parsed as $type => $options) {
                $extensions[] = [
                    'type' => (string) $type,
                    'options' => array_values(array_filter((array) $options, fn ($o) => $o !== null && $o !== '')),
                ];
            }

            return $extensions;
        } catch (Throwable) {
            return [];
        }
    }
}
