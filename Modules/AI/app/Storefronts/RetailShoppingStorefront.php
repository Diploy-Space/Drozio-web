<?php

namespace Modules\AI\app\Storefronts;

use App\Enums\BusinessModule;
use App\Services\New\ProductRetailService;
use Laravel\Ai\Contracts\Tool;
use Modules\AI\app\Contracts\ShoppingStorefrontInterface;
use Modules\AI\app\PromptTemplates\ShoppingSystemTemplate;
use Modules\AI\app\Tools\AddToCartTool;
use Modules\AI\app\Tools\CheckoutTool;
use Modules\AI\app\Tools\GetSimilarProductsTool;
use Modules\AI\app\Tools\ListBrandsTool;
use Modules\AI\app\Tools\ListVendorsTool;
use Modules\AI\app\Tools\RecallShownProductsTool;
use Modules\AI\app\Tools\SafeTool;
use Modules\AI\app\Tools\SearchOffersTool;
use Modules\AI\app\Tools\SearchProductsTool;
use Modules\AI\app\Tools\UpdateCartQuantityTool;

class RetailShoppingStorefront implements ShoppingStorefrontInterface
{
    private const OFFERS_GUIDANCE = <<<'GUIDANCE'
- search_offers: customer asks about deals, discounts, offers, sales, promotions, flash deals, featured deals, or "what's on sale". Returns products currently on promotion; optionally narrow with a keyword. Set deal_type to the campaign they name so results match that exact website page: deal_type="flash_deal" for FLASH DEALS (flash-deals page), deal_type="featured_deal" for FEATURED DEALS (featured-deal-products page). For any other "deals/discounts/sale/on offer" ask, use deal_type="general" (or omit) to return discounted/clearance products. These are DISTINCT — a featured-deal ask must NOT return flash-deal or general results, and vice versa. If a specific campaign returns nothing, say that campaign is not running right now and offer to show general deals instead.
GUIDANCE;

    public function __construct(
        private readonly SearchProductsTool $searchProductsTool,
        private readonly GetSimilarProductsTool $getSimilarProductsTool,
        private readonly AddToCartTool $addToCartTool,
        private readonly UpdateCartQuantityTool $updateCartQuantityTool,
        private readonly CheckoutTool $checkoutTool,
        private readonly RecallShownProductsTool $recallShownProductsTool,
        private readonly SearchOffersTool $searchOffersTool,
        private readonly ListVendorsTool $listVendorsTool,
        private readonly ListBrandsTool $listBrandsTool,
        private readonly ProductRetailService $productService,
    ) {}

    public function businessModule(): string
    {
        return BusinessModule::RETAIL;
    }

    public function tools(): array
    {
        return array_map(fn (Tool $tool) => new SafeTool($tool), [
            $this->searchProductsTool,
            $this->getSimilarProductsTool,
            $this->addToCartTool,
            $this->updateCartQuantityTool,
            $this->checkoutTool,
            $this->recallShownProductsTool,
            $this->searchOffersTool,
            $this->listVendorsTool,
            $this->listBrandsTool,
        ]);
    }

    public function systemPrompt(): string
    {
        return (new ShoppingSystemTemplate(offersGuidance: self::OFFERS_GUIDANCE))->build();
    }

    public function getActiveProductIds(array $productIds): array
    {
        return $this->productService->getActiveProductIds(productIds: $productIds);
    }
}
