<?php

namespace App\Http\Controllers\Admin\Promotion;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Promotion\FlashDeal\AddProductRequest;
use App\Http\Requests\Admin\Promotion\FlashDeal\AddRequest;
use App\Http\Requests\Admin\Promotion\FlashDeal\UpdateRequest;
use App\Services\New\BusinessSettingService;
use App\Services\New\Deal\FlashDealProductService;
use App\Services\New\Deal\FlashDealService;
use App\Services\New\ProductRetailService;
use Devrabiul\ToastMagic\Facades\ToastMagic;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class FlashDealController extends Controller
{
    private const PICKER_LIMIT = 20;

    private const PICKER_RELATIONS = ['brand', 'category', 'seller.shop'];

    public function __construct(
        private readonly FlashDealService $flashDealService,
        private readonly FlashDealProductService $flashDealProductService,
        private readonly ProductRetailService $productService,
        private readonly BusinessSettingService $businessSettingService,
    ) {}

    public function index(Request $request): View
    {
        $flashDeals = $this->flashDealService->getList(
            searchValue: $request->get('searchValue'),
            dataLimit: $this->businessSettingService->findBy(params: ['type' => 'pagination_limit'])?->value,
            withCount: ['products' => fn (Builder $query) => $query->whereHas('product', fn (Builder $product) => $product->active())],
        );

        $priority = $this->businessSettingService->findBy(params: ['type' => $this->flashDealService->getPriorityType()]);

        return view('admin-views.deal.flash-index', [
            'flashDeals' => $flashDeals,
            'flashDealPriority' => $priority === null ? null : json_decode($priority->value),
        ]);
    }

    public function getAddView(): View
    {
        $language = json_decode((string) $this->businessSettingService->findBy(params: ['type' => 'pnc_language'])?->value, true) ?? [];

        return view('admin-views.deal.flash-add', [
            'language' => $language,
            'defaultLanguage' => $language[0] ?? null,
        ]);
    }

    public function add(AddRequest $request): RedirectResponse
    {
        $this->flashDealService->create(
            data: $request->validated() + $request->all(),
            image: $request->file('image'),
            metaImage: $request->file('meta_image'),
        );

        ToastMagic::success(translate('Flash deal added successfully'));

        return redirect()->route('admin.retail.deal.flash');
    }

    public function getUpdateView(string|int $id): View|RedirectResponse
    {
        $deal = $this->flashDealService->find(id: $id);

        if ($deal === null) {
            ToastMagic::error(translate('Flash deal not found'));

            return redirect()->route('admin.retail.deal.flash');
        }

        $language = json_decode((string) $this->businessSettingService->findBy(params: ['type' => 'pnc_language'])?->value, true) ?? [];

        return view('admin-views.deal.flash-update', [
            'deal' => $deal,
            'language' => $language,
            'defaultLanguage' => $language[0] ?? null,
            'translate' => $deal->translations->where('key', 'title')->pluck('value', 'locale')->all(),
        ]);
    }

    public function update(UpdateRequest $request, string|int $id): RedirectResponse
    {
        $deal = $this->flashDealService->update(
            id: $id,
            data: $request->validated() + $request->all(),
            image: $request->file('image'),
            metaImage: $request->file('meta_image'),
        );

        $deal === null
            ? ToastMagic::error(translate('Flash deal not found'))
            : ToastMagic::success(translate('Flash deal updated successfully'));

        return redirect()->route('admin.retail.deal.flash');
    }

    public function updateStatus(Request $request): RedirectResponse
    {
        $isUpdated = $this->flashDealService->updateStatus(id: $request['id'], status: (bool) $request->get('status', 0));

        $isUpdated
            ? ToastMagic::success(translate('Flash deal status updated'))
            : ToastMagic::error(translate('Flash deal not found'));

        return redirect()->route('admin.retail.deal.flash');
    }

    public function getAddProductView(string|int $dealId): View|RedirectResponse
    {
        $deal = $this->flashDealService->find(id: $dealId, relations: []);

        if ($deal === null) {
            ToastMagic::error(translate('Flash deal not found'));

            return redirect()->route('admin.retail.deal.flash');
        }

        $assignedProductIds = $this->flashDealProductService->getProductIds(dealId: $deal->id);

        $products = $this->productService->getList(
            dataLimit: self::PICKER_LIMIT,
            relations: self::PICKER_RELATIONS,
            filters: [
                ProductRetailService::FILTER_ACTIVE => true,
                ProductRetailService::FILTER_EXCLUDE_IDS => $assignedProductIds,
            ],
        );

        $dealProducts = $this->productService->getList(
            dataLimit: $this->businessSettingService->findBy(params: ['type' => 'pagination_limit'])?->value,
            relations: self::PICKER_RELATIONS,
            filters: [ProductRetailService::FILTER_ACTIVE => true, 'id' => $assignedProductIds],
        );

        return view('admin-views.deal.add-product', [
            'deal' => $deal,
            'dealId' => $deal->id,
            'addProductAction' => route('admin.retail.deal.add-product', [$deal->id]),
            'deleteProductAction' => route('admin.retail.deal.delete-product', [$deal->id]),
            'searchProductAction' => route('admin.retail.deal.search-product'),
            'products' => $products,
            'dealProducts' => $dealProducts,
        ]);
    }

    public function addProduct(AddProductRequest $request, string|int $dealId): RedirectResponse
    {
        $deal = $this->flashDealService->find(id: $dealId, relations: []);

        if ($deal === null) {
            ToastMagic::error(translate('Flash deal not found'));

            return redirect()->route('admin.retail.deal.flash');
        }

        $attached = $this->flashDealProductService->attach(
            dealId: $deal->id,
            productIds: $request->validated()['product_id'],
            discount: $request->validated()['discount'] ?? null,
            discountType: $request->validated()['discount_type'] ?? null,
        );

        $attached > 0
            ? ToastMagic::success(translate(':item added successfully', ['item' => translate('Product')]))
            : ToastMagic::warning(translate('Every selected product is already in this deal'));

        return back();
    }

    public function delete(Request $request, string|int $dealId): JsonResponse
    {
        $isDetached = $this->flashDealProductService->detach(dealId: $dealId, productId: $request['id']);

        if (! $isDetached) {
            return response()->json(['message' => translate('Product not found in this deal')], 404);
        }

        return response()->json(['message' => translate(':item removed successfully', ['item' => translate('Product')])]);
    }

    public function search(Request $request): JsonResponse
    {
        $products = $this->productService->getList(
            searchValue: $request['searchValue'],
            dataLimit: 'all',
            relations: self::PICKER_RELATIONS,
            filters: [
                ProductRetailService::FILTER_ACTIVE => true,
                ProductRetailService::FILTER_EXCLUDE_IDS => $this->flashDealProductService->getProductIds(dealId: $request['deal_id']),
            ],
        );

        return response()->json([
            'result' => view('admin-views.partials._search-product', compact('products'))->render(),
        ]);
    }
}
