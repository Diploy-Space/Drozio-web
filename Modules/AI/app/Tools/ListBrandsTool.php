<?php

namespace Modules\AI\app\Tools;

use App\Enums\BusinessModule;
use App\Services\New\BrandService;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;

class ListBrandsTool implements Tool
{
    public function __construct(
        private readonly BrandService $brandService,
    ) {}

    protected function businessModule(): string
    {
        return BusinessModule::RETAIL;
    }

    public function name(): string
    {
        return 'list_brands';
    }

    public function description(): string
    {
        return 'List the product BRANDS (manufacturers / labels like Samsung, Nike) available on this marketplace. '
            .'Brands are DISTINCT from stores/vendors/sellers — call this whenever the customer asks which brands '
            .'exist, wants the brand list, or asks what brands you carry. Use list_vendors for stores/sellers instead. '
            .'Optionally narrow by a name fragment.';
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'query' => $schema->string()->description('Optional brand name fragment to filter brands (optional).'),
            'limit' => $schema->integer()->description('Maximum brands to return (1–30, default 15).'),
        ];
    }

    public function handle(Request $request): string
    {
        $query = strtolower(trim((string) $request->string('query')));
        $limit = min(max((int) $request->integer('limit', 15), 1), 30);
        $module = $this->businessModule();

        $cacheKey = 'ai_brands:'.$module.':'.md5($query.'|'.$limit);

        return Cache::remember($cacheKey, now()->addMinutes(15), function () use ($query, $limit) {
            $brands = $this->brandService->getActiveWithProductCounts(
                limit: $limit,
                nameLike: $query !== '' ? $query : null,
            )->map(fn (Model $brand) => [
                'name' => (string) ($brand->name ?? ''),
                'product_count' => (int) ($brand->brand_products_count ?? 0),
            ])->all();

            return json_encode(['total' => count($brands), 'brands' => $brands]);
        });
    }
}
