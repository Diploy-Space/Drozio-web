<?php

namespace App\Http\Controllers\RestAPI\v6\Customer;

use App\Http\Controllers\RestAPI\v6\BaseApiController;
use App\Http\Resources\V6\Customer\DealOfTheDayResource;
use App\Http\Resources\V6\Customer\FlashDealResource;
use App\Http\Resources\V6\Customer\ProductResource;
use App\Services\New\Deal\DealOfTheDayService;
use App\Services\New\Deal\FeaturedDealService;
use App\Services\New\Deal\FlashDealProductService;
use App\Services\New\Deal\FlashDealService;
use App\Services\New\ProductRetailService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\ApiPlayground\app\Attributes\ApiDoc;

class DealController extends BaseApiController
{
    public function __construct(
        private readonly FlashDealService $flashDealService,
        private readonly FeaturedDealService $featuredDealService,
        private readonly DealOfTheDayService $dealOfTheDayService,
        private readonly FlashDealProductService $flashDealProductService,
        private readonly ProductRetailService $productService,
    ) {}

    #[ApiDoc(
        summary: 'Running flash deals',
        tag: 'Deal',
        query: ['limit' => 'nullable|string', 'offset' => 'nullable|integer', 'search' => 'nullable|string'],
    )]
    public function getFlashDeals(Request $request): JsonResponse
    {
        return $this->dealList($request, $this->flashDealService);
    }

    #[ApiDoc(
        summary: 'Products of one running flash deal',
        tag: 'Deal',
        query: ['limit' => 'nullable|string', 'offset' => 'nullable|integer', 'search' => 'nullable|string'],
        status: [404],
    )]
    public function getFlashDealProducts(Request $request, int|string $dealId): JsonResponse
    {
        return $this->dealProducts($request, $this->flashDealService, $dealId);
    }

    #[ApiDoc(
        summary: 'Running featured deals',
        tag: 'Deal',
        query: ['limit' => 'nullable|string', 'offset' => 'nullable|integer', 'search' => 'nullable|string'],
    )]
    public function getFeaturedDeals(Request $request): JsonResponse
    {
        return $this->dealList($request, $this->featuredDealService);
    }

    #[ApiDoc(
        summary: 'Products of one running featured deal',
        tag: 'Deal',
        query: ['limit' => 'nullable|string', 'offset' => 'nullable|integer', 'search' => 'nullable|string'],
        status: [404],
    )]
    public function getFeaturedDealProducts(Request $request, int|string $dealId): JsonResponse
    {
        return $this->dealProducts($request, $this->featuredDealService, $dealId);
    }

    #[ApiDoc(
        summary: 'The running deal of the day',
        tag: 'Deal',
        status: [404],
    )]
    public function getDealOfTheDay(): JsonResponse
    {
        $deal = $this->dealOfTheDayService->getRunningDeal(relations: ['product.seller.shop'], locale: app()->getLocale());

        if ($deal === null) {
            return $this->responseFormatter(config: config('response.deal_not_found'));
        }

        return $this->responseFormatter(
            config: config('response.data_fetched'),
            content: ['data' => new DealOfTheDayResource($deal)],
        );
    }

    private function dealList(Request $request, FlashDealService $service): JsonResponse
    {
        $args = [
            'search' => $request->query('search'),
        ];
        $limit = $request->query('limit', DEFAULT_DATA_LIMIT);
        $offset = (int) $request->query('offset', 1);

        $deals = $service->getList(
            searchValue: $args['search'],
            dataLimit: $limit,
            page: $offset,
            relations: ['translations' => fn ($query) => $query->where('locale', app()->getLocale())],
            withCount: ['products' => fn (Builder $query) => $query->whereHas('product', fn (Builder $product) => $product->active())],
            filters: ['status' => 1, FlashDealService::FILTER_RUNNING => true],
        );

        return $this->responseFormatter(
            config: config('response.data_fetched'),
            content: [
                'data' => FlashDealResource::collection($deals),
                'params' => $this->paramsFormatter($args),
                'pagination' => $this->paginateFormatter($deals),
            ],
        );
    }

    private function dealProducts(Request $request, FlashDealService $service, int|string $dealId): JsonResponse
    {
        $deal = $service->getRunningDeals()->firstWhere('id', (int) $dealId);

        if ($deal === null) {
            return $this->responseFormatter(config: config('response.deal_not_found'));
        }

        $args = [
            'search' => $request->query('search'),
        ];

        $limit = $request->query('limit', DEFAULT_DATA_LIMIT);

        $offset = (int) $request->query('offset', 1);

        $products = $this->productService->getList(
            searchValue: $args['search'],
            dataLimit: $limit,
            page: $offset,
            relations: [
                'seller.shop',
                'flashDealProducts' => fn ($query) => $query->where('flash_deal_id', $deal->id),
            ],
            filters: [
                ProductRetailService::FILTER_ACTIVE => true,
                'id' => $this->flashDealProductService->getProductIds(dealId: $deal->id),
            ],
            sortBy: $service->getPriorityType(),
        );

        return $this->responseFormatter(
            config: config('response.data_fetched'),
            content: [
                'data' => ProductResource::collection($products),
                'params' => $this->paramsFormatter($args),
                'pagination' => $this->paginateFormatter($products),
            ],
        );
    }
}
