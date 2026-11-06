<?php

namespace App\Http\Controllers\RestAPI\v6\Erp;

use App\Http\Controllers\RestAPI\v6\BaseApiController;
use App\Http\Requests\Erp\Vendor\ListRequest;
use App\Http\Resources\V6\Erp\VendorResource;
use App\Services\New\ReviewService;
use App\Services\New\VendorService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Collection;
use Modules\ApiPlayground\app\Attributes\ApiDoc;

class VendorController extends BaseApiController
{
    private const DEFAULT_LIMIT = 10;

    private const RELATIONS = ['shop'];

    private const WITH_COUNT = ['orders'];

    public function __construct(
        private readonly VendorService $vendorService,
        private readonly ReviewService $reviewService,
    ) {}

    #[ApiDoc(
        summary: 'Vendors with their shop, order count and rating, oldest first',
        tag: 'ERP Integration',
        query: [
            'limit' => 'nullable|integer',
            'offset' => 'nullable|integer',
        ],
        status: [401, 422],
        guard: 'erp',
    )]
    public function getList(ListRequest $request): JsonResponse
    {
        $args = $request->only(['limit', 'offset']);

        $vendors = $this->vendorService->getList(
            dataLimit: (int) ($args['limit'] ?? self::DEFAULT_LIMIT),
            page: (int) ($args['offset'] ?? 1),
            relations: self::RELATIONS,
            withCount: self::WITH_COUNT,
            sortBy: VendorService::SORT_OLDEST,
        );

        $this->attachRatings(vendors: $vendors->getCollection());

        return $this->responseFormatter(
            config: config('response.data_fetched'),
            content: [
                'data' => VendorResource::collection($vendors->getCollection())->resolve(),
                'params' => $this->paramsFormatter($args),
                'pagination' => $this->paginateFormatter($vendors),
            ],
        );
    }

    #[ApiDoc(
        summary: 'One vendor with its shop, order count and rating',
        tag: 'ERP Integration',
        status: [401, 404],
        guard: 'erp',
    )]
    public function find(int $id): JsonResponse
    {
        $vendor = $this->vendorService->getList(
            dataLimit: 'all',
            relations: self::RELATIONS,
            withCount: self::WITH_COUNT,
            filters: ['id' => $id],
        )->first();

        if ($vendor === null) {
            return $this->responseFormatter(
                config: config('response.data_not_found'),
                errors: [['error_code' => 'vendor', 'message' => translate('Vendor not found')]],
            );
        }

        $this->attachRatings(vendors: collect([$vendor]));

        return $this->responseFormatter(
            config: config('response.data_fetched'),
            content: ['data' => VendorResource::make($vendor)->resolve()],
        );
    }

    private function attachRatings(Collection $vendors): void
    {
        $ratings = $this->reviewService->getAverageRatingBySellers(
            sellerIds: $vendors->map(static fn ($vendor): int => (int) $vendor->id)->all(),
        );

        $vendors->each(function ($vendor) use ($ratings): void {
            $vendor->rating = $ratings[(int) $vendor->id] ?? null;
        });
    }
}
