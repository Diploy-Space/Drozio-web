<?php

namespace App\Http\Controllers\Web\v6;

use App\Enums\SessionKey;
use App\Http\Controllers\Controller;
use App\Http\Requests\Customer\Product\SearchRequest;
use App\Http\Requests\Customer\ProductCompare\AddRequest;
use App\Http\Requests\Customer\ProductCompare\DeleteRequest;
use App\Services\New\AttributeService;
use App\Services\New\ProductCompareService;
use App\Services\New\ProductRetailService;
use App\Services\New\WishlistService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;

class ProductCompareController extends Controller
{
    private const FASHION_THEME = 'theme_fashion';

    private const SEARCH_LIMIT = 10;

    private const LIST_RELATIONS = [
        'product',
        'product.digitalVariation',
        'product.reviews',
        'product.brand',
        'product.category',
    ];

    public function __construct(
        private readonly ProductCompareService $productCompareService,
        private readonly WishlistService $wishlistService,
        private readonly AttributeService $attributeService,
        private readonly ProductRetailService $productService,
    ) {}

    public function index(): View
    {
        $customerId = (int) auth('customer')->id();

        $compareLists = $this->productCompareService->getList(
            dataLimit: 'all',
            relations: self::LIST_RELATIONS,
            filters: ['user_id' => $customerId, ProductCompareService::FILTER_HAS_PRODUCT => true],
            sortBy: ProductCompareService::SORT_NEWEST_ID,
        );

        $attributes = theme_root_path() === self::FASHION_THEME
            ? $this->attributeService->getList(dataLimit: 'all', sortBy: AttributeService::SORT_NEWEST_ID)
            : [];

        $this->syncCompareAndWishlistSession(customerId: $customerId);

        return view(themeViewName(key: 'account_compare_list'), [
            'compareLists' => $compareLists,
            'attributes' => $attributes,
        ]);
    }

    public function add(AddRequest $request): JsonResponse|RedirectResponse
    {
        $productId = (int) $request['product_id'];

        if (! $request->ajax()) {
            return $this->addFromCompareTab(productId: $productId);
        }

        if (! auth('customer')->check()) {
            return response()->json(['error' => translate('Please login your account'), 'value' => 0]);
        }

        $customerId = (int) auth('customer')->id();
        $listed = $this->productCompareService->findBy(params: ['user_id' => $customerId, 'product_id' => $productId]);

        if ($listed !== null) {
            $this->productCompareService->delete(id: $listed->id);
            $compareProductIds = $this->syncCompareSession(customerId: $customerId);

            return response()->json([
                'error' => translate('Compare list Removed'),
                'value' => 2,
                'count' => count($compareProductIds),
                'product_count' => count($compareProductIds),
                'compare_product_ids' => $compareProductIds,
            ]);
        }

        $this->productCompareService->addWithinLimit(customerId: $customerId, productId: $productId);
        $compareProductIds = $this->syncCompareSession(customerId: $customerId);

        return response()->json([
            'success' => translate('Product added to compare list'),
            'value' => 1,
            'count' => count($compareProductIds),
            'id' => $request['product_id'],
            'product_count' => count($compareProductIds),
            'compare_product_ids' => $compareProductIds,
        ]);
    }

    public function delete(DeleteRequest $request): RedirectResponse
    {
        $customerId = $this->resolveCustomerId();

        $this->productCompareService->deleteBy(params: ['id' => $request['id'], 'user_id' => $customerId]);
        $this->syncCompareSession(customerId: $customerId, existingProductsOnly: false);

        return redirect()->back();
    }

    public function deleteAll(): RedirectResponse
    {
        $customerId = $this->resolveCustomerId();

        $this->productCompareService->deleteBy(params: ['user_id' => $customerId]);
        $this->syncCompareSession(customerId: $customerId, existingProductsOnly: false);

        return redirect()->back();
    }

    public function search(SearchRequest $request): JsonResponse
    {
        $products = $this->productService->getList(
            searchValue: $request['name'],
            dataLimit: self::SEARCH_LIMIT,
            page: 1,
            relations: ['rating', 'tags'],
            filters: [ProductRetailService::FILTER_ACTIVE => true],
        )->items();

        return response()->json([
            'result' => view(themeViewName(key: 'product_search_result_for_compare_list'), [
                'products' => $products,
                'compare_id' => $request['compare_id'],
            ])->render(),
        ]);
    }

    private function addFromCompareTab(int $productId): RedirectResponse
    {
        $customerId = $this->resolveCustomerId();
        $listed = $this->productCompareService->findBy(params: ['user_id' => $customerId, 'product_id' => $productId]);

        if ($listed !== null) {
            return redirect()->back();
        }

        $this->productCompareService->addWithinLimit(customerId: $customerId, productId: $productId);
        $this->syncCompareSession(customerId: $customerId);

        return redirect()->back();
    }

    /**
     * @return array<int, int>
     */
    private function syncCompareSession(?int $customerId, bool $existingProductsOnly = true): array
    {
        $filters = ['user_id' => $customerId];

        if ($existingProductsOnly) {
            $filters[ProductCompareService::FILTER_HAS_PRODUCT] = true;
        }

        $compareProductIds = $this->productCompareService->getList(
            dataLimit: 'all',
            filters: $filters,
            sortBy: ProductCompareService::SORT_NEWEST_ID,
        )->pluck('product_id')->all();

        session()->forget(SessionKey::PRODUCT_COMPARE_LIST);
        session()->put(SessionKey::PRODUCT_COMPARE_LIST, $compareProductIds);

        return $compareProductIds;
    }

    private function resolveCustomerId(): ?int
    {
        return auth('customer')->check() ? (int) auth('customer')->id() : null;
    }

    private function syncCompareAndWishlistSession(int $customerId): void
    {
        session()->put(SessionKey::WISH_LIST, $this->wishlistService->getProductIds(customerId: $customerId));
        session()->put(SessionKey::PRODUCT_COMPARE_LIST, $this->productCompareService->getProductIds(customerId: $customerId));
    }
}
