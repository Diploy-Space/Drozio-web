<?php

namespace App\Http\Controllers\RestAPI\v6\Customer;

use App\Http\Controllers\RestAPI\v6\BaseApiController;
use App\Http\Resources\V6\Customer\BannerResource;
use App\Services\New\BannerService;
use App\Services\New\BrandService;
use App\Services\New\CategoryService;
use App\Services\New\ProductRetailService;
use App\Services\New\ShopService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\ApiPlayground\app\Attributes\ApiDoc;

class BannerController extends BaseApiController
{
    public function __construct(
        private readonly BannerService $bannerService,
        private readonly ProductRetailService $productService,
        private readonly ShopService $shopService,
        private readonly CategoryService $categoryService,
        private readonly BrandService $brandService,
    ) {}

    #[ApiDoc(
        summary: 'Banner list',
        tag: 'Banner',
        query: ['limit' => 'nullable|string', 'offset' => 'nullable|integer', 'search' => 'nullable|string', 'banner_type' => 'nullable|string'],
    )]
    public function getList(Request $request): JsonResponse
    {
        $args = [
            'search' => $request->query('search'),
            'banner_type' => $request->query('banner_type'),
        ];
        $limit = $request->query('limit', DEFAULT_DATA_LIMIT);
        $offset = (int) $request->query('offset', 1);

        $banners = $this->bannerService->getList(
            searchValue: $args['search'],
            dataLimit: $limit,
            page: $offset,
            relations: ['storage'],
            filters: $this->bannerFilters($args),
        );

        $this->attachResources($banners);

        return $this->responseFormatter(
            config: config('response.data_fetched'),
            content: [
                'data' => BannerResource::collection($banners),
                'params' => $this->paramsFormatter($args),
                'pagination' => $this->paginateFormatter($banners),
            ],
        );
    }

    private function bannerFilters(array $args): array
    {
        $filters = ['published' => 1, 'theme' => theme_root_path()];

        if ($args['banner_type'] !== null) {
            $filters['banner_type'] = $args['banner_type'];
        }

        return $filters;
    }

    private function attachResources(iterable $banners): void
    {
        $resolved = [];

        foreach ($banners as $banner) {
            $key = $banner->resource_type.':'.$banner->resource_id;

            if (! array_key_exists($key, $resolved)) {
                $resolved[$key] = $this->findResource($banner->resource_type, $banner->resource_id);
            }

            $banner->setRelation('linkedResource', $resolved[$key]);
        }
    }

    private function findResource(?string $resourceType, mixed $resourceId): ?Model
    {
        if (empty($resourceId)) {
            return null;
        }

        return match ($resourceType) {
            BannerService::RESOURCE_PRODUCT => $this->productService->find(id: $resourceId),
            BannerService::RESOURCE_SHOP => $this->shopService->find(id: $resourceId),
            BannerService::RESOURCE_CATEGORY => $this->categoryService->find(id: $resourceId, relations: []),
            BannerService::RESOURCE_BRAND => $this->brandService->find(id: $resourceId, relations: []),
            default => null,
        };
    }
}
