<?php

namespace App\Http\Controllers\RestAPI\v6\Customer;

use App\Http\Controllers\RestAPI\v6\BaseApiController;
use App\Http\Requests\Customer\Product\SectionListRequest;
use App\Http\Resources\V6\Customer\ProductResource;
use App\Services\New\GuestUserService;
use App\Services\New\ProductCompareService;
use App\Services\New\ProductRetailService;
use App\Services\New\WishlistService;
use App\Traits\CustomerOwnerTrait;
use Illuminate\Http\JsonResponse;
use Modules\ApiPlayground\app\Attributes\ApiDoc;

class ProductSectionController extends BaseApiController
{
    use CustomerOwnerTrait;

    private const SECTION_QUERY = [
        'limit' => 'nullable|integer|min:0',
        'offset' => 'nullable|integer',
        'guest_id' => 'nullable|integer',
    ];

    public function __construct(
        private readonly ProductRetailService $productRetailService,
        private readonly WishlistService $wishlistService,
        private readonly ProductCompareService $productCompareService,
        private readonly GuestUserService $guestUserService,
    ) {}

    #[ApiDoc(
        summary: 'Latest products',
        description: 'Send a bearer token to receive the customer\'s wishlist and compare flags; guests get both as 0.',
        tag: 'Product section',
        query: self::SECTION_QUERY,
        status: [422],
        auth: false,
        guard: 'api',
    )]
    public function getLatest(SectionListRequest $request): JsonResponse
    {
        return $this->sectionList(request: $request, section: ProductRetailService::SECTION_LATEST);
    }

    #[ApiDoc(
        summary: 'New arrival products',
        description: 'Ordered by the new arrival priority setup when custom sorting is on.',
        tag: 'Product section',
        query: self::SECTION_QUERY,
        status: [422],
        auth: false,
        guard: 'api',
    )]
    public function getNewArrival(SectionListRequest $request): JsonResponse
    {
        return $this->sectionList(request: $request, section: ProductRetailService::SECTION_NEW_ARRIVAL);
    }

    #[ApiDoc(
        summary: 'Featured products',
        description: 'Ordered by the featured product priority setup when custom sorting is on.',
        tag: 'Product section',
        query: self::SECTION_QUERY,
        status: [422],
        auth: false,
        guard: 'api',
    )]
    public function getFeatured(SectionListRequest $request): JsonResponse
    {
        return $this->sectionList(request: $request, section: ProductRetailService::SECTION_FEATURED);
    }

    #[ApiDoc(
        summary: 'Top rated products',
        description: 'Reviewed products, ordered by the top rated priority setup when custom sorting is on.',
        tag: 'Product section',
        query: self::SECTION_QUERY,
        status: [422],
        auth: false,
        guard: 'api',
    )]
    public function getTopRated(SectionListRequest $request): JsonResponse
    {
        return $this->sectionList(request: $request, section: ProductRetailService::SECTION_TOP_RATED);
    }

    #[ApiDoc(
        summary: 'Best selling products',
        description: 'Ordered products, ordered by the best selling priority setup when custom sorting is on.',
        tag: 'Product section',
        query: self::SECTION_QUERY,
        status: [422],
        auth: false,
        guard: 'api',
    )]
    public function getBestSelling(SectionListRequest $request): JsonResponse
    {
        return $this->sectionList(request: $request, section: ProductRetailService::SECTION_BEST_SELLING);
    }

    protected function guestUserService(): GuestUserService
    {
        return $this->guestUserService;
    }

    private function sectionList(SectionListRequest $request, string $section): JsonResponse
    {
        $args = [
            'limit' => $request->dataLimit(),
            'offset' => $request->page(),
        ];

        $products = $this->productRetailService->getSectionList(
            section: $section,
            dataLimit: $args['limit'],
            page: $args['offset'],
        );

        $this->stampOwnerFlags(request: $request, products: $products);

        return $this->responseFormatter(
            config: config('response.data_fetched'),
            content: [
                'data' => ProductResource::collection($products),
                'params' => $this->paramsFormatter($args),
                'pagination' => $this->paginateFormatter($products),
            ],
        );
    }

    private function stampOwnerFlags(SectionListRequest $request, iterable $products): void
    {
        $owner = $this->resolveCustomerOwner($request);
        $customerId = $owner !== null && ! $owner['is_guest'] ? (int) $owner['id'] : null;

        $this->productRetailService->applyOwnerFlags(
            products: $products,
            wishlistProductIds: $customerId === null ? [] : $this->wishlistService->getProductIds(customerId: $customerId),
            compareProductIds: $customerId === null ? [] : $this->productCompareService->getProductIds(customerId: $customerId),
        );
    }
}
