<?php

namespace App\Http\Controllers\RestAPI\v6\Vendor;

use App\Http\Controllers\RestAPI\v6\BaseApiController;
use App\Http\Resources\V6\Vendor\BrandResource;
use App\Http\Resources\V6\Vendor\CategoryResource;
use App\Services\New\BrandService;
use App\Services\New\CategoryService;
use Illuminate\Http\JsonResponse;
use Modules\ApiPlayground\app\Attributes\ApiDoc;

class CatalogueController extends BaseApiController
{
    public function __construct(
        private readonly CategoryService $categoryService,
        private readonly BrandService $brandService,
    ) {}

    #[ApiDoc(
        summary: 'Retail category tree with sub and sub-sub categories and their product counts, for the product form',
        tag: 'Catalogue (Seller)',
        status: [401],
        guard: 'seller',
    )]
    public function getCategories(): JsonResponse
    {
        return $this->responseFormatter(
            config: config('response.data_fetched'),
            content: ['data' => CategoryResource::collection($this->categoryService->getTree(withProductCounts: true))->resolve()],
        );
    }

    #[ApiDoc(
        summary: 'Every retail brand, active and inactive, for the product form',
        tag: 'Catalogue (Seller)',
        status: [401],
        guard: 'seller',
    )]
    public function getBrands(): JsonResponse
    {
        $brands = $this->brandService->getList(
            dataLimit: 'all',
            relations: ['translations' => fn ($query) => $query->where('locale', app()->getLocale())],
            sortBy: BrandService::SORT_OLDEST_ID,
        );

        return $this->responseFormatter(
            config: config('response.data_fetched'),
            content: ['data' => BrandResource::collection($brands)->resolve()],
        );
    }
}
