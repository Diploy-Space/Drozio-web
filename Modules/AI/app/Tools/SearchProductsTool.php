<?php

namespace Modules\AI\app\Tools;

use App\Enums\BusinessModule;
use App\Services\New\AuthorService;
use App\Services\New\BrandService;
use App\Services\New\CategoryService;
use App\Services\New\ColorService;
use App\Services\New\CurrencyService;
use App\Services\New\ProductService;
use App\Services\New\PublishingHouseService;
use App\Services\New\ShopService;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Support\Facades\Cache;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Modules\AI\app\Services\ShoppingAssistant\ProductCollector;
use Modules\AI\app\Traits\ProductCardBuilderTrait;

class SearchProductsTool implements Tool
{
    use ProductCardBuilderTrait;

    public function __construct(
        private readonly ProductService $productService,
        private readonly BrandService $brandService,
        private readonly CategoryService $categoryService,
        private readonly ShopService $shopService,
        private readonly CurrencyService $currencyService,
        private readonly ColorService $colorService,
        private readonly AuthorService $authorService,
        private readonly PublishingHouseService $publishingHouseService,
        private readonly ProductCollector $collector,
    ) {}

    protected function businessModule(): string
    {
        return BusinessModule::RETAIL;
    }

    public function name(): string
    {
        return 'search_products';
    }

    public function description(): string
    {
        return 'Search for products in the store by keywords, category, or attributes. '
            .'Call this whenever the customer wants to find, browse, or compare products — even if the request is vague. '
            .'Extract keywords from the ENTIRE conversation, not just the latest message. '
            .'If the customer explicitly asks for variation/configurable products (with selectable options like '
            .'color/size/type) or, conversely, for non-variation/simple products, set has_variation accordingly.';
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'query' => $schema->string()->description('Search keywords: product name, type, brand, or feature.')->required(),
            'category' => $schema->string()->description('Product category name to narrow results (optional).'),
            'attributes' => $schema->object()->description('Extra filters e.g. {"color":"blue","brand":"Nike"} (optional).'),
            'limit' => $schema->integer()->description('Maximum results to return (1–10, default 5).'),
            'max_price' => $schema->number()->description('Maximum budget amount in the currency specified by currency_code (optional).'),
            'currency_code' => $schema->string()->description('ISO 4217 currency code for max_price, e.g. "USD", "BDT", "EUR" (optional, defaults to store base currency).'),
            'has_variation' => $schema->boolean()->description('Set ONLY when the customer explicitly asks by configurability: true for variation/configurable products (selectable color/size/type options), false for non-variation/simple products. Omit entirely otherwise to return both.'),
            'sort' => $schema->string()->description('Result ranking. Use "popularity" when the customer asks for the best/top/good/recommended/popular/highest-rated products, so results are ranked by ratings and orders. Use "price_high" when they ask for the most expensive / highest-priced / priciest products, or "price_low" for the cheapest / lowest-priced / most affordable products (both order by price and need NO budget). Use "relevance" (or omit) for a plain keyword search.'),
            'brand' => $schema->string()->description('Brand NAME to filter by, e.g. "Samsung", when the customer asks for a specific brand (optional).'),
            'store' => $schema->string()->description('Store/shop/vendor/seller NAME to filter by, e.g. "Gizmox", when the customer wants products from a specific store (optional).'),
        ];
    }

    public function handle(Request $request): string
    {
        $query = strtolower(trim((string) $request->string('query')));
        $category = strtolower(trim((string) $request->string('category')));
        $limit = min((int) $request->integer('limit', 5), 10);
        $maxPrice = $request->has('max_price') ? (float) $request->float('max_price') : null;
        $currencyCode = strtoupper(trim((string) $request->string('currency_code')));
        $hasVariation = $request->has('has_variation') ? (bool) $request->boolean('has_variation') : null;
        $sortInput = strtolower(trim((string) $request->string('sort')));
        $sort = in_array($sortInput, ['popularity', 'price_high', 'price_low'], true) ? $sortInput : null;
        $brand = trim((string) $request->string('brand'));
        $store = trim((string) $request->string('store'));

        $maxPriceUsd = ($maxPrice !== null && $maxPrice > 0)
            ? $this->currencyService->convertToBaseUsd(amount: $maxPrice, currencyCode: $currencyCode)
            : null;

        $categoryId = $category !== '' ? $this->categoryService->getIdByName($category) : null;
        $brandId = $brand !== '' ? $this->brandService->getActiveIdByName($brand) : null;
        $sellerId = $store !== '' ? $this->shopService->getSellerIdByShopName($store) : null;

        $authorProductIds = $query !== '' ? $this->authorService->getMatchingProductIds(keyword: $query) : [];
        $publishingHouseProductIds = $query !== '' ? $this->publishingHouseService->getMatchingProductIds(keyword: $query) : [];

        $currency = (string) session('currency_code', '');
        $locale = app()->getLocale();
        $module = $this->businessModule();
        $cacheKey = 'ai_search:'.$module.':'.md5(
            $locale.'|'.$currency.'|'.$query.'|'.$category.'|'.$limit.'|'.($maxPriceUsd ?? '').'|'.($hasVariation === null ? '' : ($hasVariation ? '1' : '0')).'|'.($sort ?? '').'|'.$brand.'|'.$store
        );

        $cards = Cache::remember($cacheKey, now()->addMinutes(15), function () use ($query, $categoryId, $brandId, $sellerId, $limit, $maxPriceUsd, $hasVariation, $sort, $authorProductIds, $publishingHouseProductIds) {
            $products = $this->productService->getAssistantSuggestions(
                query: $query,
                categoryId: $categoryId,
                brandId: $brandId,
                sellerId: $sellerId,
                limit: $limit,
                maxPriceUsd: $maxPriceUsd,
                hasVariation: $hasVariation,
                sort: $sort,
                authorProductIds: $authorProductIds,
                publishingHouseProductIds: $publishingHouseProductIds,
            );

            return $this->buildCards($products);
        });

        $this->collector->add(products: $cards, toolName: 'search_products');

        return json_encode(['total' => count($cards), 'products' => $cards]);
    }
}
