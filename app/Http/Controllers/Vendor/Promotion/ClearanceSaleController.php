<?php

namespace App\Http\Controllers\Vendor\Promotion;

use App\Enums\ClearanceDiscountType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Vendor\ClearanceSale\ConfigUpdateRequest;
use App\Http\Requests\Vendor\ClearanceSale\ProductAddRequest;
use App\Http\Requests\Vendor\ClearanceSale\ProductDiscountUpdateRequest;
use App\Http\Requests\Vendor\ClearanceSale\ProductStatusUpdateRequest;
use App\Http\Requests\Vendor\ClearanceSale\SeoConfigUpdateRequest;
use App\Http\Requests\Vendor\ClearanceSale\StatusUpdateRequest;
use App\Services\New\Clearance\StockClearanceProductService;
use App\Services\New\Clearance\StockClearanceSetupService;
use App\Services\New\ProductRetailService;
use Devrabiul\ToastMagic\Facades\ToastMagic;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ClearanceSaleController extends Controller
{
    private const PICKER_LIMIT = 20;

    private const PICKER_RELATIONS = ['brand', 'category'];

    public function __construct(
        private readonly StockClearanceSetupService $stockClearanceSetupService,
        private readonly StockClearanceProductService $stockClearanceProductService,
        private readonly ProductRetailService $productService,
    ) {}

    public function index(Request $request): View
    {
        $clearanceConfig = $this->config(relations: ['seo']);

        $stockClearanceProduct = $this->stockClearanceProductService->getList(
            searchValue: $request->get('searchValue'),
            dataLimit: getWebConfig(name: 'pagination_limit'),
            relations: ['product'],
            filters: [
                'added_by' => StockClearanceSetupService::SETUP_BY_VENDOR,
                'user_id' => $this->vendorId(),
            ],
        );

        return view('vendor-views.promotion.clearance-sale.index', [
            'clearanceConfig' => $clearanceConfig,
            'products' => $this->pickerProducts(),
            'stockClearanceProduct' => $stockClearanceProduct,
        ]);
    }

    public function updateStatus(StatusUpdateRequest $request): JsonResponse
    {
        $config = $this->config();

        if ($config === null) {
            return response()->json(['status' => 0, 'message' => translate('Please setup the configuration first')]);
        }

        $this->stockClearanceSetupService->updateStatus(id: $config->id, status: (bool) $request['status']);

        return response()->json([
            'status' => 1,
            'message' => translate(':item updated successfully', ['item' => translate('Status')]),
        ]);
    }

    public function updateClearanceConfig(ConfigUpdateRequest $request): RedirectResponse
    {
        $this->stockClearanceSetupService->updateOrInsert(
            params: $this->setupParams(),
            data: array_merge($request->validated() + $request->all(), [
                'shop_id' => auth('seller')->user()?->shop?->id,
            ]),
        );

        $config = $this->config();

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
        $config = $this->config();

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
            'result' => view('vendor-views.promotion.clearance-sale.partials._search-product', compact('products'))->render(),
        ]);
    }

    public function getMultipleProductDetailsView(Request $request): JsonResponse
    {
        $selectedProducts = $this->productService->getList(
            dataLimit: 'all',
            filters: [
                'id' => (array) $request['productIds'],
                'added_by' => ProductRetailService::ADDED_BY_SELLER,
                'user_id' => $this->vendorId(),
            ],
        );

        $clearanceConfig = $this->config();

        return response()->json([
            'result' => view('vendor-views.promotion.clearance-sale.partials._select-product', compact('selectedProducts', 'clearanceConfig'))->render(),
        ]);
    }

    public function addClearanceProduct(ProductAddRequest $request): JsonResponse
    {
        $config = $this->config();

        if ($config === null) {
            return response()->json(['status' => false, 'message' => translate('Please setup the configuration first')]);
        }

        $result = $this->stockClearanceProductService->attachMany(
            candidates: $this->candidates($request),
            config: $config,
            addedBy: StockClearanceSetupService::SETUP_BY_VENDOR,
            vendorId: $this->vendorId(),
            shopId: auth('seller')->user()?->shop?->id,
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

    public function updateDiscountAmount(ProductDiscountUpdateRequest $request): JsonResponse
    {
        $config = $this->config();
        $product = $this->stockClearanceProductService->findBy(
            params: ['product_id' => $request['product_id'], 'user_id' => $this->vendorId()],
            relations: ['product'],
        )?->product;

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
            vendorId: $this->vendorId(),
        );

        return response()->json([
            'status' => 1,
            'message' => translate(':item updated successfully', ['item' => translate('Discount')]),
        ]);
    }

    public function updateProductStatus(ProductStatusUpdateRequest $request): JsonResponse
    {
        $status = (bool) $request['status'];
        $clearanceProduct = $this->stockClearanceProductService->findBy(
            params: ['product_id' => $request['product_id'], 'user_id' => $this->vendorId()],
            relations: ['setup', 'product'],
        );

        if ($status && ! $this->stockClearanceProductService->isDiscountBelowProductPrice($clearanceProduct)) {
            return response()->json([
                'status' => 0,
                'message' => translate('Your product\'s unit price is lower than the offer price'),
            ]);
        }

        $this->stockClearanceProductService->updateStatus(
            productId: $request['product_id'],
            status: $status,
            vendorId: $this->vendorId(),
        );

        return response()->json([
            'status' => 1,
            'message' => translate(':item updated successfully', ['item' => translate('Product status')]),
        ]);
    }

    public function deleteClearanceProduct(string|int $productId): RedirectResponse
    {
        $this->stockClearanceProductService->detach(productId: $productId, vendorId: $this->vendorId())
            ? ToastMagic::success(translate(':item removed successfully', ['item' => translate('Stock clearance product')]))
            : ToastMagic::error(translate('Stock clearance product not found'));

        return back();
    }

    public function deleteClearanceAllProduct(): RedirectResponse
    {
        $this->stockClearanceProductService->detachAll(
            addedBy: StockClearanceSetupService::SETUP_BY_VENDOR,
            vendorId: $this->vendorId(),
        );

        ToastMagic::success(translate('All stock clearance products removed successfully'));

        return back();
    }

    private function vendorId(): ?int
    {
        return auth('seller')->id();
    }

    /** @return array<string, mixed> */
    private function setupParams(): array
    {
        return [
            'setup_by' => StockClearanceSetupService::SETUP_BY_VENDOR,
            'user_id' => $this->vendorId(),
        ];
    }

    private function config(array $relations = []): mixed
    {
        return $this->stockClearanceSetupService->findBy(params: $this->setupParams(), relations: $relations);
    }

    private function pickerProducts(?string $searchValue = null): mixed
    {
        return $this->productService->getList(
            searchValue: $searchValue,
            dataLimit: self::PICKER_LIMIT,
            relations: self::PICKER_RELATIONS,
            filters: [
                ProductRetailService::FILTER_ACTIVE => true,
                'added_by' => ProductRetailService::ADDED_BY_SELLER,
                'user_id' => $this->vendorId(),
                ProductRetailService::FILTER_EXCLUDE_IDS => $this->stockClearanceProductService->getProductIds(
                    addedBy: StockClearanceSetupService::SETUP_BY_VENDOR,
                    vendorId: $this->vendorId(),
                ),
            ],
        );
    }

    /**
     * @return array<int, array{product: mixed, discount_type: ?string, discount_amount: mixed}>
     */
    private function candidates(ProductAddRequest $request): array
    {
        $discounts = $request->discountInputs();

        $products = $this->productService->getList(
            dataLimit: 'all',
            filters: [
                'id' => $request->productIds(),
                'added_by' => ProductRetailService::ADDED_BY_SELLER,
                'user_id' => $this->vendorId(),
            ],
        );

        return $products->map(fn ($product): array => [
            'product' => $product,
            'discount_type' => $discounts[$product->id]['discount_type'] ?? null,
            'discount_amount' => $discounts[$product->id]['discount_amount'] ?? null,
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
}
