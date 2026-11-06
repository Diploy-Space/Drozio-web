<?php

namespace App\Http\Controllers\RestAPI\v6\Customer;

use App\Enums\BusinessModule;
use App\Http\Controllers\RestAPI\v6\BaseApiController;
use App\Http\Resources\V6\Customer\ProductDetailResource;
use App\Http\Resources\V6\Customer\ProductResource;
use App\Http\Resources\V6\Customer\ShippingMethodResource;
use App\Services\New\ColorService;
use App\Services\New\OrderDetailService;
use App\Services\New\ProductCompareService;
use App\Services\New\ProductRetailService;
use App\Services\New\RestockProductCustomerService;
use App\Services\New\RestockProductService;
use App\Services\New\ShippingMethodService;
use App\Services\New\WishlistService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\ApiPlayground\app\Attributes\ApiDoc;

class ProductController extends BaseApiController
{
    private const DETAIL_RELATIONS = [
        'reviews.customer',
        'seller.shop',
        'tags',
        'digitalVariation',
        'digitalProductAuthors.author',
        'digitalProductPublishingHouse.publishingHouse',
        'rating',
    ];

    public function __construct(
        private readonly ProductRetailService $productRetailService,
        private readonly WishlistService $wishlistService,
        private readonly ProductCompareService $productCompareService,
        private readonly RestockProductService $restockProductService,
        private readonly RestockProductCustomerService $restockProductCustomerService,
        private readonly ColorService $colorService,
        private readonly OrderDetailService $orderDetailService,
        private readonly ShippingMethodService $shippingMethodService,
    ) {}

    #[ApiDoc(
        summary: 'Active shipping methods',
        tag: 'Product details',
        auth: false,
    )]
    public function getShippingMethods(): JsonResponse
    {
        $shippingMethods = $this->shippingMethodService->getList(
            dataLimit: 'all',
            filters: ['status' => 1, 'context' => BusinessModule::RETAIL],
            sortBy: ShippingMethodService::SORT_OLDEST,
        );

        return $this->responseFormatter(
            config: config('response.data_fetched'),
            content: [
                'data' => ShippingMethodResource::collection($shippingMethods),
                'params' => $this->paramsFormatter([]),
                'pagination' => $this->paginateFormatter($shippingMethods),
            ],
        );
    }

    #[ApiDoc(
        summary: 'Storefront share link of a product',
        tag: 'Product details',
        status: [404],
        auth: false,
    )]
    public function getShareLink(string $slug): JsonResponse
    {
        $product = $this->productRetailService->findBy(filters: ['slug' => $slug]);

        if ($product === null) {
            return $this->responseFormatter(config: config('response.product_not_found'));
        }

        return $this->responseFormatter(
            config: config('response.data_fetched'),
            content: ['link' => route('product', $product->slug)],
        );
    }

    #[ApiDoc(
        summary: 'Products related to a product',
        description: 'Send a bearer token to receive the customer\'s wishlist and compare flags; guests get both as 0.',
        tag: 'Product details',
        status: [404],
        auth: false,
        guard: 'api',
    )]
    public function getRelated(Request $request, string $slug): JsonResponse
    {
        $product = $this->productRetailService->findBy(filters: ['slug' => $slug]);

        if ($product === null) {
            return $this->responseFormatter(config: config('response.product_not_found'));
        }

        $relatedProducts = $this->productRetailService->getSectionList(
            section: ProductRetailService::SECTION_RELATED,
            filters: [
                ProductRetailService::FILTER_EXACT_CATEGORY_IDS => $product->getRawOriginal('category_ids'),
                ProductRetailService::FILTER_EXCLUDE_IDS => [$product->id],
            ],
        );

        $this->stampOwnerFlags(products: $relatedProducts, customerId: $request->user('api')?->id);

        return $this->responseFormatter(
            config: config('response.data_fetched'),
            content: [
                'data' => ProductResource::collection($relatedProducts),
                'params' => $this->paramsFormatter([]),
                'pagination' => $this->paginateFormatter($relatedProducts),
            ],
        );
    }

    #[ApiDoc(
        summary: 'Order and wishlist counts of a product',
        tag: 'Product details',
        status: [404],
        auth: false,
    )]
    public function getCounts(string $slug): JsonResponse
    {
        $product = $this->productRetailService->findBy(filters: ['slug' => $slug]);

        if ($product === null) {
            return $this->responseFormatter(config: config('response.product_not_found'));
        }

        return $this->responseFormatter(
            config: config('response.data_fetched'),
            content: [
                'order_count' => $this->orderDetailService->count(filters: ['product_id' => $product->id]),
                'wishlist_count' => $this->wishlistService->count(filters: ['product_id' => $product->id]),
            ],
        );
    }

    #[ApiDoc(
        summary: 'Product details',
        description: 'Send a bearer token to receive the customer\'s wishlist, compare and restock request flags; guests get them empty.',
        tag: 'Product details',
        status: [404],
        auth: false,
        guard: 'api',
    )]
    public function find(Request $request, string $slug): JsonResponse
    {
        $product = $this->productRetailService->findActiveBy(
            filters: ['slug' => $slug],
            relations: [
                ...self::DETAIL_RELATIONS,
                'clearanceSale' => fn ($clearanceSale) => $clearanceSale->active(),
            ],
        );

        if ($product === null) {
            return $this->responseFormatter(config: config('response.product_not_found'));
        }

        $customerId = $request->user('api')?->id;
        $restockRequestedVariants = $this->getRestockRequestedVariants(productId: $product->id, customerId: $customerId);

        $this->stampOwnerFlags(products: [$product], customerId: $customerId);

        $product->setAttribute('colors_formatted', $this->productRetailService->getFormattedColors(
            product: $product,
            colorNames: $this->colorService->getNameMap(),
        ));
        $product->setAttribute('average_review', getOverallRating($product->reviews)[0]);
        $product->setAttribute('reviews_count', $product->reviews->count());
        $product->setAttribute('digital_product_authors_names', $this->productRetailService->getAuthorsInfo(product: $product)['names']);
        $product->setAttribute('digital_product_publishing_house_names', $this->productRetailService->getPublishingHouseInfo(product: $product)['names']);
        $product->setAttribute('restock_requested_list', $restockRequestedVariants);
        $product->setAttribute('is_restock_requested', count($restockRequestedVariants) > 0 ? 1 : 0);

        return $this->responseFormatter(
            config: config('response.data_fetched'),
            content: ['data' => ProductDetailResource::make($product)],
        );
    }

    private function stampOwnerFlags(iterable $products, ?int $customerId): void
    {
        $this->productRetailService->applyOwnerFlags(
            products: $products,
            wishlistProductIds: $customerId === null ? [] : $this->wishlistService->getProductIds(customerId: $customerId),
            compareProductIds: $customerId === null ? [] : $this->productCompareService->getProductIds(customerId: $customerId),
        );
    }

    private function getRestockRequestedVariants(int $productId, ?int $customerId): array
    {
        if ($customerId === null) {
            return [];
        }

        $restockProductIds = $this->restockProductService
            ->getList(dataLimit: 'all', filters: ['product_id' => $productId])
            ->pluck('id')
            ->all();

        if ($restockProductIds === []) {
            return [];
        }

        return $this->restockProductCustomerService
            ->getList(dataLimit: 'all', filters: ['customer_id' => $customerId, 'restock_product_id' => $restockProductIds])
            ->pluck('variant')
            ->all();
    }
}
