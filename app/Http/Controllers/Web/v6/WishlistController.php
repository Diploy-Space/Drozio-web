<?php

namespace App\Http\Controllers\Web\v6;

use App\Enums\BusinessModule;
use App\Enums\SessionKey;
use App\Http\Controllers\Controller;
use App\Http\Requests\Customer\Wishlist\ClearRequest;
use App\Http\Requests\Customer\Wishlist\DeleteRequest;
use App\Http\Requests\Customer\Wishlist\ListRequest;
use App\Http\Requests\Customer\Wishlist\ToggleRequest;
use App\Services\New\BusinessSettingService;
use App\Services\New\ProductCompareService;
use App\Services\New\WishlistService;
use Devrabiul\ToastMagic\Facades\ToastMagic;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class WishlistController extends Controller
{
    private const LIST_LIMIT = 15;

    private const MODULE_PANEL = 'module';

    public function __construct(
        private readonly WishlistService $wishlistService,
        private readonly ProductCompareService $productCompareService,
        private readonly BusinessSettingService $businessSettingService,
    ) {}

    public function index(ListRequest $request): View
    {
        $customerId = (int) auth('customer')->id();

        $wishlists = $this->wishlistService->getList(
            searchValue: $request['search'],
            dataLimit: self::LIST_LIMIT,
            relations: $this->listRelations(customerId: $customerId, businessModule: BusinessModule::RETAIL),
            filters: [
                'customer_id' => $customerId,
                WishlistService::FILTER_HAS_PRODUCT => true,
                WishlistService::FILTER_BUSINESS_MODULE => BusinessModule::RETAIL,
            ],
        );

        $this->syncWishlistSession(customerId: $customerId);
        session()->put(SessionKey::PRODUCT_COMPARE_LIST, $this->productCompareService->getProductIds(customerId: $customerId));

        return view(themeViewName(key: 'account_wishlist'), [
            ...$this->getListView(wishlists: $wishlists, businessModule: BusinessModule::RETAIL),
            'moduleTabs' => customerProfileModuleTabs(
                menuKey: 'wishlist',
                activeModule: BusinessModule::RETAIL,
                counts: $this->wishlistService->getCustomerCountsByBusinessModule(customerId: $customerId),
            ),
        ]);
    }

    public function toggle(ToggleRequest $request): ?JsonResponse
    {
        if (! $request->ajax()) {
            return null;
        }

        if (! auth('customer')->check()) {
            return response()->json(['error' => translate('Please login your account'), 'value' => 0]);
        }

        $customerId = (int) auth('customer')->id();
        $productId = (int) $request['product_id'];

        $isWishlisted = $this->wishlistService->toggleForCustomer(customerId: $customerId, productId: $productId);

        $customerWishlistCount = $this->wishlistService->countForCustomer(customerId: $customerId);
        $productWishlistCount = $this->wishlistService->count(filters: ['product_id' => $productId]);

        $this->syncWishlistSession(customerId: $customerId, activeProductRelation: WishlistService::RELATION_ACTIVE_PRODUCT);

        if (! $isWishlisted) {
            return response()->json([
                'error' => translate('Product removed from the wishlist'),
                'value' => 2,
                'count' => $customerWishlistCount,
                'product_count' => $productWishlistCount,
            ]);
        }

        return response()->json([
            'success' => translate('Product has been added to wishlist'),
            'value' => 1,
            'count' => $customerWishlistCount,
            'id' => $request['product_id'],
            'product_count' => $productWishlistCount,
        ]);
    }

    public function delete(DeleteRequest $request): JsonResponse|RedirectResponse
    {
        $customerId = $this->resolveCustomerId();

        $this->wishlistService->deleteBy(params: ['product_id' => $request['id'], 'customer_id' => $customerId]);

        $businessModule = $this->resolveBusinessModule(requested: $request['business_module']);

        $wishlists = $this->wishlistService->getList(
            dataLimit: self::LIST_LIMIT,
            relations: $this->listRelations(customerId: (int) $customerId, businessModule: $businessModule),
            filters: [
                'customer_id' => $customerId,
                WishlistService::FILTER_HAS_PRODUCT => true,
                WishlistService::FILTER_BUSINESS_MODULE => $businessModule,
            ],
        );

        $this->syncWishlistSession(customerId: $customerId, activeProductRelation: WishlistService::RELATION_ACTIVE_PRODUCT);

        if ($request->ajax()) {
            return response()->json([
                'success' => translate('Product has been removed from wishlist').'!',
                'message' => translate('Product has been removed from wishlist'),
                'count' => $this->wishlistService->countForCustomer(customerId: $customerId),
                'module_counts' => $this->wishlistService->getCustomerCountsByBusinessModule(customerId: (int) $customerId),
                'id' => $request['id'],
                'wishlist' => view(
                    themeViewName(key: 'account_wishlist_partials'),
                    $this->getListView(wishlists: $wishlists, businessModule: $businessModule),
                )->render(),
            ]);
        }

        ToastMagic::success(translate('Product has been removed from wishlist'));

        return back();
    }

    public function deleteAll(ClearRequest $request): RedirectResponse
    {
        $customerId = $this->resolveCustomerId();

        $this->wishlistService->deleteBy(params: array_filter([
            'customer_id' => $customerId,
            WishlistService::FILTER_BUSINESS_MODULE => $request['business_module'],
        ], fn (mixed $value) => $value !== null));

        $this->syncWishlistSession(customerId: $customerId, activeProductRelation: null);

        return redirect()->back();
    }

    public function getHeaderWishlist(Request $request): JsonResponse
    {
        $wishlistPartialKey = $request->query('panel') === self::MODULE_PANEL ? 'header_module_wishlist_partials' : 'header_wishlist_partials';

        return response()->json(['html' => view(themeViewName(key: $wishlistPartialKey))->render()]);
    }

    private function brandSetting(): mixed
    {
        return $this->businessSettingService->findBy(params: ['type' => BusinessSettingService::PRODUCT_BRAND])->value;
    }

    private function resolveBusinessModule(?string $requested): string
    {
        return $requested === BusinessModule::WHOLESALE && businessModuleManager()->isAvailable(BusinessModule::WHOLESALE)
            ? BusinessModule::WHOLESALE
            : BusinessModule::RETAIL;
    }

    /** @return array<string, mixed> */
    private function getListView(LengthAwarePaginator $wishlists, string $businessModule): array
    {
        return [
            'wishlists' => $wishlists,
            'brand_setting' => $this->brandSetting(),
            'wishlistBusinessModule' => $businessModule,
            'isRetailWishlist' => $businessModule === BusinessModule::RETAIL,
            'wishlistExploreUrl' => $businessModule === BusinessModule::WHOLESALE ? route('wholesale.products.index') : route('products'),
        ];
    }

    private function listRelations(int $customerId, string $businessModule): array
    {
        $relations = [
            'productFullInfo.compareList' => fn (HasMany $compare) => $compare->where('user_id', $customerId),
            'productFullInfo.digitalVariation',
            'productFullInfo.seller.shop',
            'productFullInfo.clearanceSale' => fn (HasOne $sale) => $sale->active(),
        ];

        return $businessModule === BusinessModule::WHOLESALE
            ? [...$relations, 'productFullInfo.wholesaleDetail']
            : $relations;
    }

    /**
     * @return array<int, int>
     */
    private function syncWishlistSession(
        ?int $customerId,
        ?string $activeProductRelation = WishlistService::RELATION_ACTIVE_WISHLIST_PRODUCT,
    ): array {
        $wishlistProductIds = $this->wishlistService->getProductIds(
            customerId: $customerId,
            activeProductRelation: $activeProductRelation,
        );

        session()->forget(SessionKey::WISH_LIST);
        session()->put(SessionKey::WISH_LIST, $wishlistProductIds);

        return $wishlistProductIds;
    }

    private function resolveCustomerId(): ?int
    {
        return auth('customer')->check() ? (int) auth('customer')->id() : null;
    }
}
