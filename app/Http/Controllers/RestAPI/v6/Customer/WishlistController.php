<?php

namespace App\Http\Controllers\RestAPI\v6\Customer;

use App\Enums\BusinessModule;
use App\Http\Controllers\RestAPI\v6\BaseApiController;
use App\Http\Requests\Customer\Wishlist\DeleteRequest;
use App\Http\Requests\Customer\Wishlist\ListRequest;
use App\Http\Requests\Customer\Wishlist\ToggleRequest;
use App\Http\Resources\V6\Customer\WishlistResource;
use App\Services\New\ProductRetailService;
use App\Services\New\WishlistService;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Http\JsonResponse;
use Modules\ApiPlayground\app\Attributes\ApiDoc;

class WishlistController extends BaseApiController
{
    public function __construct(
        private readonly WishlistService $wishlistService,
        private readonly ProductRetailService $productRetailService,
    ) {}

    #[ApiDoc(
        summary: 'Wishlist of the authenticated customer',
        tag: 'Wishlist',
        query: [
            'search' => 'nullable|string',
            'limit' => 'nullable|integer',
            'offset' => 'nullable|integer',
        ],
        status: [401, 422],
    )]
    public function getList(ListRequest $request): JsonResponse
    {
        $customerId = $request->user('api')->id;
        $args = ['search' => $request->query('search')];

        $wishlists = $this->wishlistService->getList(
            dataLimit: (int) $request->query('limit', DEFAULT_DATA_LIMIT),
            page: (int) $request->query('offset', 1),
            relations: $this->listRelations(),
            filters: [
                'customer_id' => $customerId,
                'product_id' => $this->getRetailWishlistProductIds(customerId: $customerId),
                WishlistService::FILTER_HAS_PRODUCT_NAMED => $args['search'],
            ],
        );

        return $this->responseFormatter(
            config: config('response.data_fetched'),
            content: [
                'data' => WishlistResource::collection($wishlists),
                'params' => $this->paramsFormatter($args),
                'pagination' => $this->paginateFormatter($wishlists),
            ],
        );
    }

    #[ApiDoc(
        summary: 'Add a product to the customer\'s wishlist',
        tag: 'Wishlist',
        status: [401, 404, 409, 422],
    )]
    public function create(ToggleRequest $request): JsonResponse
    {
        $customerId = $request->user('api')->id;
        $productId = (int) $request['product_id'];

        if ($this->wishlistService->findBy(params: ['customer_id' => $customerId, 'product_id' => $productId]) !== null) {
            return $this->responseFormatter(config: config('response.wishlist_product_already_added'));
        }

        if ($this->productRetailService->findActiveBy(filters: ['id' => $productId]) === null) {
            return $this->responseFormatter(config: config('response.wishlist_product_not_found'));
        }

        $this->wishlistService->create(data: ['customer_id' => $customerId, 'product_id' => $productId]);

        return $this->responseFormatter(config: config('response.wishlist_product_added'));
    }

    #[ApiDoc(
        summary: 'Remove a product from the customer\'s wishlist',
        description: 'Scoped to the retail module, so a wholesale product id leaves the wholesale wishlist untouched.',
        tag: 'Wishlist',
        status: [401, 404],
    )]
    public function delete(DeleteRequest $request, int|string $productId): JsonResponse
    {
        $removed = $this->wishlistService->deleteBy(params: [
            'customer_id' => $request->user('api')->id,
            'product_id' => (int) $request['id'],
            WishlistService::FILTER_BUSINESS_MODULE => BusinessModule::RETAIL,
        ]);

        if ($removed === 0) {
            return $this->responseFormatter(config: config('response.wishlist_item_not_found'));
        }

        return $this->responseFormatter(config: config('response.wishlist_product_removed'));
    }

    private function listRelations(): array
    {
        return [
            'productFullInfo' => fn (BelongsTo $product) => $product
                ->with(['retailDetail', 'rating', 'clearanceSale' => fn (HasOne $sale) => $sale->active()])
                ->withCount('reviews')
                ->withAvg('reviews', 'rating'),
        ];
    }

    private function getRetailWishlistProductIds(int $customerId): array
    {
        $wishlistProductIds = $this->wishlistService->getProductIds(customerId: $customerId);

        if ($wishlistProductIds === []) {
            return [];
        }

        return $this->productRetailService
            ->getList(dataLimit: 'all', filters: ['id' => $wishlistProductIds])
            ->pluck('id')
            ->all();
    }
}
