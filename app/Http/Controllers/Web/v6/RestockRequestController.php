<?php

namespace App\Http\Controllers\Web\v6;

use App\Enums\BusinessModule;
use App\Enums\WebConfigKey;
use App\Http\Controllers\Controller;
use App\Services\New\CustomerService;
use App\Services\New\ProductCompareService;
use App\Services\New\ProductRetailService;
use App\Services\New\RestockProductCustomerService;
use App\Services\New\RestockProductService;
use App\Services\New\WishlistService;
use Devrabiul\ToastMagic\Facades\ToastMagic;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class RestockRequestController extends Controller
{
    private const STATUS_SUCCESS = 'success';

    private const STATUS_WARNING = 'warning';

    private const STATUS_ERROR = 'error';

    public function __construct(
        private readonly RestockProductService $restockProductService,
        private readonly RestockProductCustomerService $restockProductCustomerService,
        private readonly ProductRetailService $productService,
        private readonly CustomerService $customerService,
        private readonly WishlistService $wishlistService,
        private readonly ProductCompareService $productCompareService,
    ) {}

    public function index(Request $request): View
    {
        $restockProducts = $this->restockProductService->getList(
            searchValue: $request['searchValue'],
            dataLimit: getWebConfig(name: WebConfigKey::PAGINATION_LIMIT),
            relations: [
                'product' => fn ($query) => $query->active()->withCount([
                    'reviews' => fn ($reviews) => $reviews->active()->whereNull('delivery_man_id'),
                ]),
                'product.reviews',
                'product.brand',
                'product.clearanceSale' => fn ($query) => $query->active(),
            ],
            withCount: ['restockProductCustomers'],
            filters: [RestockProductService::FILTER_CUSTOMER_ID => auth('customer')->id()],
            sortBy: RestockProductService::SORT_RECENTLY_UPDATED,
        );

        return view(themeViewName(key: 'user_restock_requests'), [
            'restockProducts' => $restockProducts,
            'productIdsArray' => $restockProducts->pluck('product_id')->toArray(),
            'restockCards' => $this->getRestockCards(restockProducts: $restockProducts),
            'restockClearUrl' => route('user-restock-request-delete'),
            'moduleTabs' => customerProfileModuleTabs(menuKey: 'restock-requests', activeModule: BusinessModule::RETAIL),
        ]);
    }

    public function delete(Request $request): RedirectResponse
    {
        $customerId = auth('customer')->id();

        $this->restockProductCustomerService->deleteBy(
            params: $request['id']
                ? ['restock_product_id' => $request['id'], 'customer_id' => $customerId]
                : ['customer_id' => $customerId],
        );
        $this->restockProductService->deleteWithoutCustomers();

        ToastMagic::success(translate('Product restock request removed successfully'));

        return redirect()->route('user-restock-requests');
    }

    public function store(Request $request): JsonResponse
    {
        $product = $this->productService->findActiveBy(filters: ['id' => $request['id']]);

        if ($product === null) {
            return response()->json([
                'status' => self::STATUS_ERROR,
                'message' => translate('Product not found'),
            ]);
        }

        $customerId = auth('customer')->id();

        $restockRequest = DB::transaction(fn (): ?Model => $this->createRestockRequest(
            productId: $product->id,
            variant: $request['product_variation_code'],
            customerId: $customerId,
        ));

        if ($restockRequest === null) {
            return response()->json([
                'status' => self::STATUS_WARNING,
                'message' => translate('Already Requested'),
            ]);
        }

        $this->refreshCustomerSession(customerId: $customerId);

        return response()->json([
            'status' => self::STATUS_SUCCESS,
            'message' => translate(':item sent successfully', ['item' => translate('Request')]),
            'fcm_topic' => getRestockProductFCMTopic(restockRequest: $restockRequest),
        ]);
    }

    /** @return array<int, array<string, mixed>> */
    private function getRestockCards(iterable $restockProducts): array
    {
        $cards = [];

        foreach ($restockProducts as $restockProduct) {
            $product = $restockProduct->product;
            $hasDiscount = getProductPriceByType(product: $product, type: 'discount', result: 'value') > 0;
            $unitPrice = $this->getVariantUnitPrice(product: $product, variant: $restockProduct['variant']);
            $overallRating = getOverallRating($product?->reviews);

            $cards[$restockProduct['id']] = [
                'url' => $product?->slug ? route('product', $product->slug) : 'javascript:',
                'discount_label' => $hasDiscount ? getProductPriceByType(product: $product, type: 'discount', result: 'string') : null,
                'price_label' => $hasDiscount
                    ? getProductPriceByType(product: $product, type: 'discounted_unit_price', result: 'string', price: $unitPrice)
                    : webCurrencyConverter(amount: $unitPrice),
                'original_price_label' => $hasDiscount ? webCurrencyConverter(amount: $unitPrice) : null,
                'rating' => $overallRating[0],
                'rating_count' => $overallRating[1],
                'delete_url' => route('user-restock-request-delete', ['id' => $restockProduct['id']]),
            ];
        }

        return $cards;
    }

    private function getVariantUnitPrice(?Model $product, ?string $variant): float
    {
        $unitPrice = (float) ($product?->unit_price ?? 0);
        $variations = json_decode($product?->retailDetail?->variation ?? '[]', true);

        foreach (is_array($variations) ? $variations : [] as $variation) {
            if (($variation['type'] ?? null) === $variant) {
                $unitPrice = (float) $variation['price'];
            }
        }

        return $unitPrice;
    }

    private function createRestockRequest(int $productId, ?string $variant, ?int $customerId): ?Model
    {
        $restockRequest = $this->restockProductService->firstOrCreateForVariant(productId: $productId, variant: $variant);
        $customerRequest = [
            'restock_product_id' => $restockRequest->id,
            'customer_id' => $customerId,
            'variant' => $variant,
        ];

        if ($this->restockProductCustomerService->findBy(params: $customerRequest) !== null) {
            return null;
        }

        $this->restockProductCustomerService->create(data: $customerRequest);
        $this->restockProductService->update(id: $restockRequest->id, data: ['updated_at' => now()]);

        return $restockRequest;
    }

    private function refreshCustomerSession(?int $customerId): void
    {
        $sessionState = $this->customerService->buildSignInSessionState(
            wishListProductIds: $this->wishlistService->getProductIds(customerId: $customerId),
            compareListProductIds: $this->getCompareListProductIds(customerId: $customerId),
            restockFcmTopics: $this->getRestockFcmTopics(customerId: $customerId),
        );

        session()->forget($sessionState['forget']);
        session()->put($sessionState['put']);
    }

    private function getCompareListProductIds(?int $customerId): array
    {
        if ($customerId !== null) {
            return $this->productCompareService->getProductIds(customerId: $customerId);
        }

        return $this->productCompareService->getList(
            dataLimit: 'all',
            filters: [ProductCompareService::FILTER_HAS_PRODUCT => true, 'user_id' => null],
        )->pluck('product_id')->all();
    }

    private function getRestockFcmTopics(?int $customerId): array
    {
        if ($customerId !== null) {
            return $this->restockProductService->getCustomerFcmTopics(customerId: $customerId);
        }

        $guestRestockProductIds = $this->restockProductCustomerService->getList(
            dataLimit: 'all',
            filters: ['customer_id' => null],
        )->pluck('restock_product_id')->unique()->values()->all();

        return $this->restockProductService->getList(dataLimit: 'all', filters: ['id' => $guestRestockProductIds])
            ->map(fn (Model $restockProduct): string => getRestockProductFCMTopic(restockRequest: $restockProduct))
            ->all();
    }
}
