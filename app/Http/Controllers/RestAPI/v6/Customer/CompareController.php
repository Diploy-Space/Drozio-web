<?php

namespace App\Http\Controllers\RestAPI\v6\Customer;

use App\Http\Controllers\RestAPI\v6\BaseApiController;
use App\Http\Requests\Customer\ProductCompare\AddRequest;
use App\Http\Requests\Customer\ProductCompare\ReplaceRequest;
use App\Http\Resources\V6\Customer\CompareItemResource;
use App\Services\New\ProductCompareService;
use App\Services\New\ProductRetailService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\ApiPlayground\app\Attributes\ApiDoc;

class CompareController extends BaseApiController
{
    public function __construct(
        private readonly ProductCompareService $productCompareService,
        private readonly ProductRetailService $productRetailService,
    ) {}

    #[ApiDoc(
        summary: 'Compare list of the authenticated customer',
        tag: 'Product compare',
        status: [401],
    )]
    public function getList(Request $request): JsonResponse
    {
        $customerId = $request->user('api')->id;

        $compareItems = $this->productCompareService->getList(
            dataLimit: 'all',
            relations: $this->listRelations(),
            filters: [
                'user_id' => $customerId,
                'product_id' => $this->getRetailCompareProductIds(customerId: $customerId),
                ProductCompareService::FILTER_HAS_PRODUCT => true,
            ],
        );

        return $this->responseFormatter(
            config: config('response.data_fetched'),
            content: [
                'data' => CompareItemResource::collection($compareItems),
                'params' => $this->paramsFormatter([]),
                'pagination' => $this->paginateFormatter($compareItems),
            ],
        );
    }

    #[ApiDoc(
        summary: 'Add a product to the compare list, or remove it when already listed',
        tag: 'Product compare',
        status: [401, 404, 422],
    )]
    public function create(AddRequest $request): JsonResponse
    {
        $customerId = $request->user('api')->id;
        $productId = (int) $request['product_id'];

        $isListed = $this->productCompareService->findBy(params: ['user_id' => $customerId, 'product_id' => $productId]) !== null;

        if (! $isListed && $this->productRetailService->findActiveBy(filters: ['id' => $productId]) === null) {
            return $this->responseFormatter(config: config('response.compare_product_not_found'));
        }

        $isAdded = $this->productCompareService->toggleForCustomer(customerId: $customerId, productId: $productId);

        return $this->responseFormatter(
            config: config($isAdded ? 'response.compare_product_added' : 'response.compare_product_removed'),
            content: ['total_product_add' => $this->productCompareService->count(filters: ['product_id' => $productId])],
        );
    }

    #[ApiDoc(
        summary: 'Replace the product of one compare list item',
        tag: 'Product compare',
        status: [401, 404, 422],
    )]
    public function replace(ReplaceRequest $request, int|string $id): JsonResponse
    {
        $customerId = $request->user('api')->id;
        $productId = (int) $request['product_id'];

        if ($this->productCompareService->findBy(params: ['id' => (int) $id, 'user_id' => $customerId]) === null) {
            return $this->responseFormatter(config: config('response.compare_item_not_found'));
        }

        if ($this->productRetailService->findActiveBy(filters: ['id' => $productId]) === null) {
            return $this->responseFormatter(config: config('response.compare_product_not_found'));
        }

        $this->productCompareService->update(id: (int) $id, data: ['product_id' => $productId]);

        return $this->responseFormatter(config: config('response.compare_product_replaced'));
    }

    #[ApiDoc(
        summary: 'Clear the compare list of the authenticated customer',
        tag: 'Product compare',
        status: [401],
    )]
    public function clear(Request $request): JsonResponse
    {
        $this->productCompareService->deleteBy(params: ['user_id' => $request->user('api')->id]);

        return $this->responseFormatter(config: config('response.compare_list_cleared'));
    }

    private function listRelations(): array
    {
        return [
            'product' => fn (BelongsTo $product) => $product
                ->with(['retailDetail', 'rating'])
                ->withCount(['reviews' => fn (Builder $review) => $review->where('status', 1)]),
        ];
    }

    private function getRetailCompareProductIds(int $customerId): array
    {
        $compareProductIds = $this->productCompareService->getProductIds(customerId: $customerId);

        if ($compareProductIds === []) {
            return [];
        }

        return $this->productRetailService
            ->getList(dataLimit: 'all', filters: ['id' => $compareProductIds])
            ->pluck('id')
            ->all();
    }
}
