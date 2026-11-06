<?php

namespace Modules\AI\app\Tools;

use App\Enums\BusinessModule;
use App\Services\New\ColorService;
use App\Services\New\CurrencyService;
use App\Services\New\Deal\FeaturedDealService;
use App\Services\New\Deal\FlashDealService;
use App\Services\New\ProductRetailService;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Support\Facades\Cache;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Modules\AI\app\Services\ShoppingAssistant\ProductCollector;
use Modules\AI\app\Traits\ProductCardBuilderTrait;

class SearchOffersTool implements Tool
{
    use ProductCardBuilderTrait;

    public function __construct(
        private readonly ProductRetailService $productService,
        private readonly FlashDealService $flashDealService,
        private readonly FeaturedDealService $featuredDealService,
        private readonly CurrencyService $currencyService,
        private readonly ColorService $colorService,
        private readonly ProductCollector $collector,
    ) {}

    protected function businessModule(): string
    {
        return BusinessModule::RETAIL;
    }

    public function name(): string
    {
        return 'search_offers';
    }

    public function description(): string
    {
        return 'Find products currently ON PROMOTION — discounted items, sale/clearance products, '
            .'special offers and deals. Call this whenever the customer asks about deals, discounts, '
            .'offers, sales, promotions, flash deals, featured deals, or "what is on sale". '
            .'Set deal_type to match the SPECIFIC campaign the customer names so results match that '
            .'exact page on the website: "flash_deal" for the flash-deals page, "featured_deal" for the '
            .'featured-deal-products page. Use "general" (or omit) for any other deal/discount/sale ask. '
            .'Optionally narrow by a keyword (e.g. offers on "phones").';
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'query' => $schema->string()->description('Optional keyword to narrow the offers (product name/type), e.g. "phone".'),
            'deal_type' => $schema->string()->enum(['flash_deal', 'featured_deal', 'general'])->description('Which promotion the customer asked for: "flash_deal" = flash-deals campaign/page, "featured_deal" = featured-deal-products campaign/page, "general" (default) = any discounted/sale/clearance product.'),
            'limit' => $schema->integer()->description('Maximum results to return (1–10, default 5).'),
            'max_price' => $schema->number()->description('Maximum budget amount in the currency specified by currency_code (optional).'),
            'currency_code' => $schema->string()->description('ISO 4217 currency code for max_price, e.g. "USD", "BDT" (optional, defaults to store base currency).'),
        ];
    }

    public function handle(Request $request): string
    {
        $query = strtolower(trim((string) $request->string('query')));
        $limit = min((int) $request->integer('limit', 5), 10);
        $dealType = strtolower(trim((string) $request->string('deal_type')));
        $maxPrice = $request->has('max_price') ? (float) $request->float('max_price') : null;
        $currencyCode = strtoupper(trim((string) $request->string('currency_code')));

        $maxPriceUsd = ($maxPrice !== null && $maxPrice > 0)
            ? $this->currencyService->convertToBaseUsd(amount: $maxPrice, currencyCode: $currencyCode)
            : null;

        $currency = (string) session('currency_code', '');
        $locale = app()->getLocale();
        $module = $this->businessModule();
        $cacheKey = 'ai_offers:'.$module.':'.md5($locale.'|'.$currency.'|'.$dealType.'|'.$query.'|'.$limit.'|'.($maxPriceUsd ?? ''));

        $cards = Cache::remember($cacheKey, now()->addMinutes(15), function () use ($dealType, $query, $limit, $maxPriceUsd) {
            $keyword = $query !== '' ? $query : null;

            $products = match ($dealType) {
                'flash_deal' => $this->campaignDealProducts($this->flashDealService, $keyword, $limit, $maxPriceUsd),
                'featured_deal' => $this->campaignDealProducts($this->featuredDealService, $keyword, $limit, $maxPriceUsd),
                default => $this->productService->getAssistantDiscountedList(keyword: $keyword, limit: $limit, maxPriceUsd: $maxPriceUsd),
            };

            return $this->buildCards($products);
        });

        $this->collector->add(products: $cards, toolName: 'search_offers');

        return json_encode(['total' => count($cards), 'products' => $cards]);
    }

    private function campaignDealProducts(FlashDealService $dealService, ?string $keyword, int $limit, ?float $maxPriceUsd): array
    {
        $campaign = $dealService->getRunningDeals()->first();

        if ($campaign === null) {
            return [];
        }

        return $this->productService->getAssistantDealList(dealId: (int) $campaign->id, keyword: $keyword, limit: $limit, maxPriceUsd: $maxPriceUsd);
    }
}
