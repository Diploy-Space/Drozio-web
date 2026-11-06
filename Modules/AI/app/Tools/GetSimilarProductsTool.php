<?php

namespace Modules\AI\app\Tools;

use App\Enums\BusinessModule;
use App\Services\New\ColorService;
use App\Services\New\ProductService;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Support\Facades\Cache;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Modules\AI\app\Services\ShoppingAssistant\ProductCollector;
use Modules\AI\app\Traits\ProductCardBuilderTrait;

class GetSimilarProductsTool implements Tool
{
    use ProductCardBuilderTrait;

    public function __construct(
        private readonly ProductService $productService,
        private readonly ColorService $colorService,
        private readonly ProductCollector $collector,
    ) {}

    protected function businessModule(): string
    {
        return BusinessModule::RETAIL;
    }

    public function name(): string
    {
        return 'get_similar_products';
    }

    public function description(): string
    {
        return 'Find products similar to a specific product by its ID. '
            .'Use when the customer wants alternatives or more options like an item they are already viewing.';
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'product_id' => $schema->integer()->description('The ID of the product to find alternatives for.')->required(),
            'limit' => $schema->integer()->description('Maximum results to return (1–10, default 5).'),
        ];
    }

    public function handle(Request $request): string
    {
        $productId = (int) $request->integer('product_id');
        $limit = min((int) $request->integer('limit', 5), 10);

        $currency = (string) session('currency_code', '');
        $module = $this->businessModule();
        $cacheKey = 'ai_similar:'.$module.':'.md5($currency.'|'.$productId.'|'.$limit);

        $cards = Cache::remember($cacheKey, now()->addMinutes(15), function () use ($productId, $limit) {
            $products = $this->productService->getAssistantSimilarList(productId: $productId, limit: $limit);

            return $this->buildCards($products);
        });

        $this->collector->add(products: $cards, toolName: 'get_similar_products');

        return json_encode(['total' => count($cards), 'products' => $cards]);
    }
}
