<?php

namespace App\Http\Controllers\RestAPI\v6\Vendor;

use App\Enums\BusinessModule;
use App\Http\Controllers\RestAPI\v6\BaseApiController;
use App\Http\Requests\Shared\Shipping\CategoryCostUpdateRequest;
use App\Http\Requests\Vendor\Shipping\MethodAddRequest;
use App\Http\Requests\Vendor\Shipping\MethodStatusUpdateRequest;
use App\Http\Requests\Vendor\Shipping\MethodUpdateRequest;
use App\Http\Requests\Vendor\Shipping\TypeUpdateRequest;
use App\Http\Resources\V6\Vendor\ShippingMethodResource;
use App\Services\New\CategoryService;
use App\Services\New\CategoryShippingCostService;
use App\Services\New\ShippingMethodService;
use App\Services\New\ShippingTypeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Modules\ApiPlayground\app\Attributes\ApiDoc;

class ShippingController extends BaseApiController
{
    private const CONTEXT = BusinessModule::RETAIL;

    public function __construct(
        private readonly ShippingMethodService $shippingMethodService,
        private readonly ShippingTypeService $shippingTypeService,
        private readonly CategoryService $categoryService,
        private readonly CategoryShippingCostService $categoryShippingCostService,
    ) {}

    #[ApiDoc(
        summary: 'Shipping methods the vendor created',
        tag: 'Shipping (Vendor)',
        query: ['limit' => 'nullable|string', 'offset' => 'nullable|integer', 'search' => 'nullable|string'],
        status: [401, 403],
        guard: 'seller',
    )]
    public function getMethods(Request $request): JsonResponse
    {
        $args = [

            'search' => $request->query('search'),

        ];
        $limit = $request->query('limit', DEFAULT_DATA_LIMIT);
        $offset = (int) $request->query('offset', 1);

        $methods = $this->shippingMethodService->getList(
            searchValue: $args['search'],
            dataLimit: $limit,
            page: $offset,
            filters: $this->methodFilters($request),
            sortBy: ShippingMethodService::SORT_NEWEST,
        );

        return $this->responseFormatter(
            config: config('response.data_fetched'),
            content: [
                'data' => ShippingMethodResource::collection($methods),
                'params' => $this->paramsFormatter($args),
                'pagination' => $this->paginateFormatter($methods),
            ],
        );
    }

    #[ApiDoc(
        summary: 'Create a shipping method',
        tag: 'Shipping (Vendor)',
        status: [201, 401, 403, 422],
        guard: 'seller',
    )]
    public function storeMethod(MethodAddRequest $request): JsonResponse
    {
        $method = $this->shippingMethodService->create(
            data: $request->validated(),
            creatorId: $request->attributes->get('seller')->id,
            creatorType: ShippingMethodService::CREATOR_SELLER,
            context: self::CONTEXT,
        );

        return $this->responseFormatter(
            config: config('response.shipping_method_added_successfully'),
            content: ['data' => new ShippingMethodResource($method)],
        );
    }

    #[ApiDoc(
        summary: 'Get one of the vendor own shipping methods',
        tag: 'Shipping (Vendor)',
        status: [401, 403, 404],
        guard: 'seller',
    )]
    public function getMethod(Request $request, string $id): JsonResponse
    {
        $method = $this->ownMethod($request, $id);

        return $method === null
            ? $this->methodNotFound()
            : $this->responseFormatter(
                config: config('response.data_fetched'),
                content: ['data' => new ShippingMethodResource($method),
                ],
            );
    }

    #[ApiDoc(
        summary: 'Update one of the vendor own shipping methods',
        description: 'The method keeps whatever status it already had; editing never silently re-enables a disabled method.',
        tag: 'Shipping (Vendor)',
        status: [401, 403, 404, 422],
        guard: 'seller',
    )]
    public function updateMethod(MethodUpdateRequest $request, string $id): JsonResponse
    {
        $method = $this->ownMethod($request, $id);

        if ($method === null) {
            return $this->methodNotFound();
        }

        $updated = $this->shippingMethodService->update(id: $method->id, data: $request->validated());

        return $this->responseFormatter(
            config: config('response.shipping_method_updated_successfully'),
            content: ['data' => new ShippingMethodResource($updated)],
        );
    }

    #[ApiDoc(
        summary: 'Turn one of the vendor own shipping methods on or off',
        tag: 'Shipping (Vendor)',
        status: [401, 403, 404, 422],
        guard: 'seller',
    )]
    public function updateMethodStatus(MethodStatusUpdateRequest $request, string $id): JsonResponse
    {
        $method = $this->ownMethod($request, $id);

        if ($method === null) {
            return $this->methodNotFound();
        }

        $this->shippingMethodService->updateStatus(id: $method->id, status: (bool) $request['status']);

        return $this->responseFormatter(config: config('response.shipping_method_status_updated_successfully'));
    }

    #[ApiDoc(
        summary: 'Delete one of the vendor own shipping methods',
        tag: 'Shipping (Vendor)',
        status: [401, 403, 404],
        guard: 'seller',
    )]
    public function deleteMethod(Request $request, string $id): JsonResponse
    {
        $method = $this->ownMethod($request, $id);

        if ($method === null) {
            return $this->methodNotFound();
        }

        $this->shippingMethodService->delete(id: $method->id);

        return $this->responseFormatter(config: config('response.shipping_method_deleted_successfully'));
    }

    #[ApiDoc(
        summary: 'The shipping type the vendor charges by',
        description: 'Answers order_wise or category_wise, defaulting to order_wise when the vendor has never chosen.',
        tag: 'Shipping (Vendor)',
        status: [401, 403],
        guard: 'seller',
    )]
    public function getType(Request $request): JsonResponse
    {
        return $this->responseFormatter(
            config: config('response.data_fetched'),
            content: ['type' => $this->shippingTypeService->findBy(params: $this->sellerScope($request))?->shipping_type
                ?? ShippingTypeService::TYPE_ORDER_WISE,
            ],
        );
    }

    #[ApiDoc(
        summary: 'Choose the shipping type the vendor charges by',
        tag: 'Shipping (Vendor)',
        status: [401, 403, 422],
        guard: 'seller',
    )]
    public function updateType(TypeUpdateRequest $request): JsonResponse
    {
        $this->shippingTypeService->updateOrInsert(
            params: $this->sellerScope($request),
            data: ['shipping_type' => $request['shippingType']],
        );

        return $this->responseFormatter(
            config: config('response.shipping_type_updated_successfully'),
            content: ['type' => $request['shippingType']],
        );
    }

    #[ApiDoc(
        summary: 'Category-wise shipping costs for the vendor',
        description: 'Every top-level category with the vendor cost against it. A category the vendor has never priced answers 0 without a row being created, so reading this never writes.',
        tag: 'Shipping (Vendor)',
        status: [401, 403],
        guard: 'seller',
    )]
    public function getCategoryCosts(Request $request): JsonResponse
    {
        $stored = $this->categoryShippingCostService
            ->getList(dataLimit: 'all', relations: ['category'], filters: $this->sellerScope($request))
            ->keyBy('category_id');

        $categories = $this->categoryService->getList(
            dataLimit: 'all',
            filters: ['position' => CategoryService::POSITION_CATEGORY],
        );

        return $this->responseFormatter(
            config: config('response.data_fetched'),
            content: ['all_category_shipping_cost' => $categories->map(fn ($category): array => [
                'id' => $stored[$category->id]->id ?? null,
                'category_id' => (int) $category->id,
                'category_name' => $category->name,
                'cost' => (float) ($stored[$category->id]->cost ?? 0),
                'multiply_qty' => (int) ($stored[$category->id]->multiply_qty ?? 0),
            ])->values(),
            ],
        );
    }

    #[ApiDoc(
        summary: 'Set category-wise shipping costs',
        description: 'Send parallel ids, cost and multiply_qty arrays. Each id is a category id, and every row written is scoped to the signed-in vendor.',
        tag: 'Shipping (Vendor)',
        status: [401, 403, 422],
        guard: 'seller',
    )]
    public function updateCategoryCosts(CategoryCostUpdateRequest $request): JsonResponse
    {
        $scope = $this->sellerScope($request);
        $categoryIds = $this->categoryService
            ->getList(dataLimit: 'all', filters: ['position' => CategoryService::POSITION_CATEGORY])
            ->pluck('id')
            ->all();

        $unknown = array_diff(array_map('intval', $request['ids']), array_map('intval', $categoryIds));

        if ($unknown !== []) {
            return $this->responseFormatter(config: config('response.one_of_the_selected_categories_is_not_available'));
        }

        DB::transaction(function () use ($request, $scope): void {
            foreach ($request['ids'] as $index => $categoryId) {
                $this->categoryShippingCostService->updateOrInsert(
                    params: $scope + ['category_id' => (int) $categoryId],
                    data: [
                        'cost' => currencyConverter($request['cost'][$index], 'usd'),
                        'multiply_qty' => (int) filter_var($request['multiply_qty'][$index], FILTER_VALIDATE_BOOLEAN),
                    ],
                );
            }
        });

        return $this->responseFormatter(config: config('response.shipping_cost_updated_successfully'));
    }

    /** @return array<string, mixed> */
    private function sellerScope(Request $request): array
    {
        return [
            'seller_id' => $request->attributes->get('seller')->id,
            'context' => self::CONTEXT,
        ];
    }

    /** @return array<string, mixed> */
    private function methodFilters(Request $request): array
    {
        return [
            'creator_id' => $request->attributes->get('seller')->id,
            'creator_type' => ShippingMethodService::CREATOR_SELLER,
            'context' => self::CONTEXT,
        ];
    }

    private function ownMethod(Request $request, string $id): mixed
    {
        return $this->shippingMethodService->findBy(params: $this->methodFilters($request) + ['id' => (int) $id]);
    }

    private function methodNotFound(): JsonResponse
    {
        return $this->responseFormatter(config: config('response.shipping_method_not_found'));
    }
}
