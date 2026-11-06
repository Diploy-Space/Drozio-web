<?php

namespace App\Http\Controllers\Admin\Promotion;

use App\Http\Controllers\BaseController;
use App\Http\Requests\Admin\Promotion\MostDemanded\AddRequest;
use App\Services\New\MostDemandedService;
use App\Services\New\ProductRetailService;
use Devrabiul\ToastMagic\Facades\ToastMagic;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class MostDemandedController extends BaseController
{
    public function __construct(
        private readonly MostDemandedService $mostDemandedService,
        private readonly ProductRetailService $productService,
    ) {}

    /**
     * @return View Index function is the starting point of a controller
     *              Index function is the starting point of a controller
     */
    public function index(?Request $request, ?string $type = null): View
    {
        return $this->getListView($request);
    }

    public function getListView(Request $request): View|RedirectResponse
    {
        if (theme_root_path() != 'theme_fashion') {
            return redirect(businessModuleDashboardUrl());
        }
        $products = $this->productService->getList(dataLimit: 'all', sortBy: ProductRetailService::SORT_NAME_ASC);
        $mostDemandedProducts = $this->mostDemandedService->getList(
            searchValue: $request['searchValue'],
            dataLimit: getWebConfig(name: 'pagination_limit'),
            page: $request['page'],
            relations: ['product'],
            sortBy: MostDemandedService::SORT_NEWEST_ID,
        );

        return view('admin-views.theme-features.most-demanded.view', compact('products', 'mostDemandedProducts'));
    }

    public function add(AddRequest $request): RedirectResponse
    {
        $this->mostDemandedService->create(
            data: ['product_id' => $request['product_id']],
            banner: $request->file('image'),
        );
        ToastMagic::success(translate('Most demanded product added successfully'));

        return back();
    }

    public function updateStatus(Request $request): JsonResponse
    {
        $this->mostDemandedService->updateStatus(id: $request['id'], status: (bool) $request->get('status', 0));

        return response()->json(['success' => 1, 'message' => translate(':item updated successfully', ['item' => translate('Status')])], 200);
    }

    public function getUpdateView($id): View|RedirectResponse
    {
        if (theme_root_path() != 'theme_fashion') {
            return redirect(businessModuleDashboardUrl());
        }
        $products = $this->productService->getList(dataLimit: 'all', sortBy: ProductRetailService::SORT_NAME_ASC);
        $mostDemandedProduct = $this->mostDemandedService->find(id: $id);

        return view('admin-views.theme-features.most-demanded.edit', compact('products', 'mostDemandedProduct'));
    }

    public function update(Request $request, $id): RedirectResponse
    {
        $this->mostDemandedService->update(
            id: $id,
            data: ['product_id' => $request['product_id']],
            banner: $request->file('image'),
        );
        ToastMagic::success(translate('Most demanded product updated successfully'));

        return redirect()->route('admin.retail.most-demanded.index');
    }

    public function delete(Request $request): JsonResponse
    {
        $this->mostDemandedService->delete(id: $request['id']);

        return response()->json(['message' => translate('Most demanded product deleted successfully')]);
    }
}
