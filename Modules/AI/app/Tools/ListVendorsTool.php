<?php

namespace Modules\AI\app\Tools;

use App\Enums\BusinessModule;
use App\Services\New\ShopService;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;

class ListVendorsTool implements Tool
{
    public function __construct(
        private readonly ShopService $shopService,
    ) {}

    protected function businessModule(): string
    {
        return BusinessModule::RETAIL;
    }

    public function name(): string
    {
        return 'list_vendors';
    }

    public function description(): string
    {
        return 'List the stores / vendors / sellers available on this marketplace. '
            .'Call this whenever the customer asks which stores, vendors, sellers, or shops exist, '
            .'or wants to see the available sellers. Optionally narrow by a name fragment.';
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'query' => $schema->string()->description('Optional store name fragment to filter vendors (optional).'),
            'limit' => $schema->integer()->description('Maximum vendors to return (1–20, default 10).'),
        ];
    }

    public function handle(Request $request): string
    {
        $query = strtolower(trim((string) $request->string('query')));
        $limit = min(max((int) $request->integer('limit', 10), 1), 20);
        $module = $this->businessModule();

        $cacheKey = 'ai_vendors:'.$module.':'.md5($query.'|'.$limit);

        return Cache::remember($cacheKey, now()->addMinutes(15), function () use ($query, $limit, $module) {
            $shops = $this->shopService->getApprovedVendorsWithProductCounts(
                limit: $limit,
                nameLike: $query !== '' ? $query : null,
                businessModule: $module,
            );

            $vendors = $shops->map(fn (Model $shop) => [
                'name' => (string) ($shop->name ?? ''),
                'product_count' => (int) ($shop->products_count ?? 0),
            ])->all();

            return json_encode(['total' => count($vendors), 'vendors' => $vendors]);
        });
    }
}
