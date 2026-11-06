<?php

namespace App\Http\Controllers\Admin\Promotion;

use App\Enums\ClearanceDiscountType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ClearanceSale\ConfigUpdateRequest;
use App\Http\Requests\Admin\ClearanceSale\SeoConfigUpdateRequest;
use App\Services\New\Clearance\StockClearanceProductService;
use App\Services\New\Clearance\StockClearanceSetupService;
use App\Services\New\ProductRetailService;
use App\Traits\InHouseTrait;
use Devrabiul\ToastMagic\Facades\ToastMagic;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ClearanceSaleController extends Controller
{
    use InHouseTrait;

    private const PICKER_LIMIT = 20;

    private const PRODUCT_LIST_LIMIT = 5;

    private const PICKER_RELATIONS = ['brand', 'category', 'seller.shop'];

    /** @var array<string, mixed> */
    private const ADMIN_SETUP_PARAMS = [
        'setup_by' => StockClearanceSetupService::SETUP_BY_ADMIN,
        'user_id' => null,
    ];

    public function __construct(
        private readonly StockClearanceSetupService $stockClearanceSetupService,
        private readonly StockClearanceProductService $stockClearanceProductService,
        private readonly ProductRetailService $productService,
    ) {}

    public function index(Request $request): View
    {
        $clearanceConfig = $this->adminSetup();

        $stockClearanceProduct = $this->stockClearanceProductService->getList(
            searchValue: $request->get('searchValue'),
            dataLimit: self::PRODUCT_LIST_LIMIT,
            relations: ['product'],
            filters: ['added_by' => StockClearanceSetupService::SETUP_BY_ADMIN],
        );

        return view('admin-views.deal.clearance-sale.index', [
            'clearanceConfig' => $clearanceConfig,
            'products' => $this->pickerProducts(),
            'stockClearanceProduct' => $stockClearanceProduct,
            'inhouseShop' => $this->getInHouseShopObject(),
        ]);
    }

    public function updateStatus(Request $request): JsonResponse
    {
        $config = $this->adminSetup(relations: []);

        if ($config === null) {
            return response()->json(['status' => 0, 'message' => translate('Please setup the configuration first')]);
        }

        $this->stockClearanceSetupService->updateStatus(id: $config->id, status: (bool) $request->get('status', 0));

        return response()->json([
            'status' => 1,
            'message' => translate(':item updated successfully', ['item' => translate('Status')]),
        ]);
    }

    public function updateClearanceConfig(ConfigUpdateRequest $request): RedirectResponse
    {
        $this->stockClearanceSetupService->updateOrInsert(
            params: self::ADMIN_SETUP_PARAMS,
            data: $this->setupData($request),
        );

        $config = $this->adminSetup(relations: []);

        if ($config->discount_type === ClearanceDiscountType::FLAT) {
            $this->stockClearanceProductService->applyFlatDiscountToProducts(
                setupId: $config->id,
                discountAmount: $request['discount_amount'],
            );
        }

        ToastMagic::success(translate(':item updated successfully', ['item' => translate('Setup')]));

        return back();
    }

    public function updateClearanceSeoConfig(SeoConfigUpdateRequest $request): RedirectResponse
    {
        $config = $this->adminSetup();

        if ($config === null) {
            ToastMagic::error(translate('Please setup the configuration first'));

            return back();
        }

        $this->stockClearanceSetupService->update(
            id: $config->id,
            data: $request->validated() + $request->all(),
            metaImage: $request->file('meta_image'),
        );

        ToastMagic::success(translate('Meta updated successfully'));

        return back();
    }

    public function getSearchedProductsView(Request $request): JsonResponse
    {
        $products = $this->pickerProducts(searchValue: $request['searchValue']);

        return response()->json([
            'count' => $products->count(),
            'result' => view('admin-views.deal.clearance-sale.partials._search-product', compact('products'))->render(),
        ]);
    }

    public function getMultipleProductDetailsView(Request $request): JsonResponse
    {
        $selectedProducts = $this->productService->getList(
            dataLimit: 'all',
            filters: ['id' => (array) $request['productIds']],
        );

        $clearanceConfig = $this->adminSetup(relations: []);

        return response()->json([
            'result' => view('admin-views.deal.clearance-sale.partials._select-product', compact('selectedProducts', 'clearanceConfig'))->render(),
        ]);
    }

    public function addClearanceProduct(Request $request): JsonResponse
    {
        $config = $this->adminSetup(relations: []);

        if ($config === null) {
            return response()->json(['status' => false, 'message' => translate('Please setup the configuration first')]);
        }

        $result = $this->stockClearanceProductService->attachMany(
            candidates: $this->candidates($request),
            config: $config,
            addedBy: StockClearanceSetupService::SETUP_BY_ADMIN,
            shopId: 0,
        );

        if ($result['reason'] !== null) {
            return response()->json(['status' => false, 'message' => $this->discountMessage($result['reason'])]);
        }

        if ($result['attached'] === 0) {
            return response()->json(['status' => false, 'message' => translate('The selected products are already in a clearance sale')]);
        }

        return response()->json([
            'status' => true,
            'message' => translate(':item added successfully', ['item' => translate('Product')]),
        ]);
    }

    public function updateDiscountAmount(Request $request): JsonResponse
    {
        $config = $this->adminSetup(relations: []);
        $product = $this->productService->find(id: $request->get('product_id'));

        if ($config === null || $product === null) {
            return response()->json(['status' => 0, 'message' => translate('Please setup the configuration first')]);
        }

        $reason = $this->stockClearanceProductService->validateDiscountUpdate(
            product: $product,
            config: $config,
            discountType: $request['discount_type'],
            discountAmount: $request['discount_amount'],
        );

        if ($reason !== null) {
            return response()->json(['status' => 0, 'message' => $this->discountMessage($reason)]);
        }

        $this->stockClearanceProductService->updateDiscount(
            productId: $product->id,
            discountType: $request['discount_type'],
            discountAmount: $request['discount_amount'],
        );

        return response()->json([
            'status' => 1,
            'message' => translate(':item updated successfully', ['item' => translate('Discount')]),
        ]);
    }

    public function updateProductStatus(Request $request): JsonResponse
    {
        $status = (bool) $request->get('status', 0);
        $clearanceProduct = $this->stockClearanceProductService->findBy(
            params: ['product_id' => $request['product_id']],
            relations: ['setup', 'product'],
        );

        if ($status && ! $this->stockClearanceProductService->isDiscountBelowProductPrice($clearanceProduct)) {
            return response()->json([
                'status' => 0,
                'message' => translate('Your products unit price is lower then offer price'),
            ]);
        }

        $this->stockClearanceProductService->updateStatus(productId: $request['product_id'], status: $status);

        return response()->json([
            'status' => 1,
            'message' => translate(':item updated successfully', ['item' => translate('Product status')]),
        ]);
    }

    /**
     * @return array<int, array{product: mixed, discount_type: ?string, discount_amount: mixed}>
     */
    private function candidates(Request $request): array
    {
        $products = $this->productService->getList(
            dataLimit: 'all',
            filters: ['id' => (array) $request['productIds'], 'added_by' => ProductRetailService::ADDED_BY_ADMIN],
        );

        return $products->map(fn ($product): array => [
            'product' => $product,
            'discount_type' => $request['discount_type'][$product->id] ?? null,
            'discount_amount' => $request['discount_amount'][$product->id] ?? null,
        ])->all();
    }

    private function discountMessage(string $reason): string
    {
        return match ($reason) {
            StockClearanceProductService::REASON_DISCOUNT_MISSING => translate('Discount amount can not be empty'),
            StockClearanceProductService::REASON_DISCOUNT_NOT_POSITIVE => translate('Discount amount can not be less than or equal to zero'),
            StockClearanceProductService::REASON_PERCENTAGE_OUT_OF_RANGE => translate('Discount amount for percentage type must be between 1 and 100'),
            StockClearanceProductService::REASON_ABOVE_UNIT_PRICE => translate('Discount amount can not be greater than unit price'),
            StockClearanceProductService::REASON_FLAT_SETUP_MANAGED => translate('The flat discount of the clearance sale setup applies to every product, so a product discount can not be changed'),
            default => translate('Discount amount can not be greater than any variation product unit price'),
        };
    }

    public function deleteClearanceProduct(string|int $productId): RedirectResponse
    {
        $this->stockClearanceProductService->detach(productId: $productId)
            ? ToastMagic::success(translate(':item removed successfully', ['item' => translate('Stock clearance product')]))
            : ToastMagic::error(translate('Stock clearance product not found'));

        return back();
    }

    public function deleteClearanceAllProduct(): RedirectResponse
    {
        $this->stockClearanceProductService->detachAll(addedBy: StockClearanceSetupService::SETUP_BY_ADMIN);
        ToastMagic::success(translate('All stock clearance products removed successfully'));

        return back();
    }

    private function adminSetup(array $relations = ['seo']): mixed
    {
        return $this->stockClearanceSetupService->findBy(
            params: ['setup_by' => StockClearanceSetupService::SETUP_BY_ADMIN],
            relations: $relations,
        );
    }

    /** @return array<string, mixed> */
    private function setupData(ConfigUpdateRequest $request): array
    {
        return array_merge($request->validated() + $request->all(), [
            'shop_id' => 0,
            'show_in_homepage' => $request['show_in_homepage'] ?? 0,
        ]);
    }

    private function pickerProducts(?string $searchValue = null): mixed
    {
        return $this->productService->getList(
            searchValue: $searchValue,
            dataLimit: self::PICKER_LIMIT,
            relations: self::PICKER_RELATIONS,
            filters: [
                ProductRetailService::FILTER_ACTIVE => true,
                'added_by' => ProductRetailService::ADDED_BY_ADMIN,
                ProductRetailService::FILTER_EXCLUDE_IDS => $this->stockClearanceProductService->getProductIds(
                    addedBy: StockClearanceSetupService::SETUP_BY_ADMIN,
                ),
            ],
        );
    }
}
