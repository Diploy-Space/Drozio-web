<?php

namespace App\Http\Controllers\RestAPI\v6\Customer;

use App\Http\Controllers\RestAPI\v6\BaseApiController;
use App\Http\Resources\V6\Customer\AuthorResource;
use App\Http\Resources\V6\Customer\PublishingHouseResource;
use App\Services\New\AuthorService;
use App\Services\New\ProductRetailService;
use App\Services\New\PublishingHouseService;
use App\Services\New\ShopService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\ApiPlayground\app\Attributes\ApiDoc;

class DigitalCatalogueController extends BaseApiController
{
    private const CATALOGUE_QUERY = [
        'slug' => 'nullable|string',
    ];

    public function __construct(
        private readonly AuthorService $authorService,
        private readonly PublishingHouseService $publishingHouseService,
        private readonly ProductRetailService $productRetailService,
        private readonly ShopService $shopService,
    ) {}

    #[ApiDoc(
        summary: 'Digital product authors',
        description: 'Authors of active digital products ranked by product count, plus an Unknown row (id 0) counting digital products without an author. Pass a shop slug to count only that shop\'s products.',
        tag: 'Digital catalogue',
        query: self::CATALOGUE_QUERY,
        auth: false,
    )]
    public function getAuthors(Request $request): JsonResponse
    {
        $slug = $this->getShopSlug(request: $request);
        $productIds = $this->getShopActiveProductIds(slug: $slug);

        $authors = $this->authorService->getListRankedByProducts(
            unattachedProductCount: $this->getUnattachedDigitalCount(
                productIds: $productIds,
                attachedProductIds: $this->authorService->getAttachedProductIds(),
            ),
            productIds: $productIds,
            includeWithoutProducts: $productIds === [],
        );

        return $this->responseFormatter(
            config: config('response.data_fetched'),
            content: [
                'data' => AuthorResource::collection($authors->values()),
                'params' => $this->paramsFormatter(['slug' => $slug]),
            ],
        );
    }

    #[ApiDoc(
        summary: 'Digital product publishing houses',
        description: 'Publishing houses of active digital products ranked by product count, plus an Unknown row (id 0) counting digital products without a publishing house. Pass a shop slug to count only that shop\'s products.',
        tag: 'Digital catalogue',
        query: self::CATALOGUE_QUERY,
        auth: false,
    )]
    public function getPublishingHouses(Request $request): JsonResponse
    {
        $slug = $this->getShopSlug(request: $request);
        $productIds = $this->getShopActiveProductIds(slug: $slug);

        $publishingHouses = $this->publishingHouseService->getListRankedByProducts(
            unattachedProductCount: $this->getUnattachedDigitalCount(
                productIds: $productIds,
                attachedProductIds: $this->publishingHouseService->getAttachedProductIds(),
            ),
            productIds: $productIds,
        );

        return $this->responseFormatter(
            config: config('response.data_fetched'),
            content: [
                'data' => PublishingHouseResource::collection($publishingHouses->values()),
                'params' => $this->paramsFormatter(['slug' => $slug]),
            ],
        );
    }

    private function getShopSlug(Request $request): ?string
    {
        $slug = $request->query('slug');

        return is_string($slug) && $slug !== '' ? $slug : null;
    }

    /**
     * @return array<int, int>
     */
    private function getShopActiveProductIds(?string $slug): array
    {
        $shop = $slug === null ? null : $this->shopService->findBy(params: ['slug' => $slug]);

        $ownershipFilters = match (true) {
            $shop === null => [],
            $shop->author_type === ProductRetailService::ADDED_BY_ADMIN => ['added_by' => ProductRetailService::ADDED_BY_IN_HOUSE],
            default => ['added_by' => ProductRetailService::ADDED_BY_SELLER, 'seller_id' => $shop->seller_id],
        };

        return $this->productRetailService->getActiveIds(filters: $ownershipFilters);
    }

    private function getUnattachedDigitalCount(array $productIds, array $attachedProductIds): int
    {
        $filters = [
            ProductRetailService::FILTER_ACTIVE => true,
            ProductRetailService::FILTER_EXCLUDE_IDS => $attachedProductIds,
            'product_type' => ProductRetailService::PRODUCT_TYPE_DIGITAL,
        ];

        if ($productIds !== []) {
            $filters['id'] = $productIds;
        }

        return $this->productRetailService->count(filters: $filters);
    }
}
