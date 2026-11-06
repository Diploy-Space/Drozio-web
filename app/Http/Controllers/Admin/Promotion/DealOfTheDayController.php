<?php

namespace App\Http\Controllers\Admin\Promotion;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Promotion\DealOfTheDay\AddRequest;
use App\Http\Requests\Admin\Promotion\DealOfTheDay\UpdateRequest;
use App\Services\New\BusinessSettingService;
use App\Services\New\Deal\DealOfTheDayService;
use App\Services\New\ProductRetailService;
use Devrabiul\ToastMagic\Facades\ToastMagic;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class DealOfTheDayController extends Controller
{
    private const PICKER_LIMIT = 20;

    private const PICKER_RELATIONS = ['brand', 'category', 'seller.shop'];

    public function __construct(
        private readonly DealOfTheDayService $dealOfTheDayService,
        private readonly BusinessSettingService $businessSettingService,
        private readonly ProductRetailService $productService,
    ) {}

    public function index(Request $request): View
    {
        $settings = $this->businessSettingService
            ->getList(dataLimit: 'all', filters: ['type' => ['pagination_limit', 'pnc_language']])
            ->pluck('value', 'type');

        $deals = $this->dealOfTheDayService->getList(
            searchValue: $request->get('searchValue'),
            dataLimit: $settings->get('pagination_limit'),
            relations: ['product', 'translations'],
        );

        $products = $this->pickerProducts();
        $language = json_decode((string) $settings->get('pnc_language'), true) ?? [];

        return view('admin-views.deal.day-index', [
            'deals' => $deals,
            'dealTitles' => $this->dealTitles($deals),
            'products' => $products,
            'language' => $language,
            'defaultLanguage' => $language[0] ?? null,
        ]);
    }

    public function add(AddRequest $request): RedirectResponse
    {
        $product = $this->productService->find(id: $request->validated()['product_id']);

        if ($product === null) {
            ToastMagic::error(translate('The selected product is not valid'));

            return back();
        }

        $this->dealOfTheDayService->create(data: $request->validated() + $request->all(), product: $product);

        ToastMagic::success(translate(':item added successfully', ['item' => translate('Deal')]));

        return back();
    }

    public function update(UpdateRequest $request, string|int $id): RedirectResponse
    {
        $product = $this->productService->find(id: $request->validated()['product_id']);

        if ($product === null) {
            ToastMagic::error(translate('The selected product is not valid'));

            return back();
        }

        $deal = $this->dealOfTheDayService->update(
            id: $id,
            data: $request->validated() + $request->all(),
            product: $product,
        );

        $deal === null
            ? ToastMagic::error(translate('Deal not found'))
            : ToastMagic::success(translate(':item updated successfully', ['item' => translate('Deal')]));

        return redirect()->route('admin.retail.deal.day');
    }

    public function updateStatus(Request $request): JsonResponse
    {
        $isUpdated = $this->dealOfTheDayService->updateStatus(id: $request['id'], status: (bool) $request->get('status', 0));

        if (! $isUpdated) {
            return response()->json(['success' => 0, 'message' => translate('Deal not found')], 404);
        }

        return response()->json(['success' => 1, 'message' => translate('Deal of the day status updated')]);
    }

    public function delete(Request $request): JsonResponse
    {
        $isDeleted = $this->dealOfTheDayService->delete(id: $request['id']);

        if (! $isDeleted) {
            return response()->json(['message' => translate('Deal not found')], 404);
        }

        return response()->json(['message' => translate('Delete successfully')]);
    }

    /**
     * @return array<int, array<string, string>>
     */
    private function dealTitles(mixed $deals): array
    {
        return collect($deals->items())
            ->mapWithKeys(fn (Model $deal): array => [
                $deal->id => $deal->translations->where('key', 'title')->pluck('value', 'locale')->all(),
            ])
            ->all();
    }

    private function pickerProducts(): mixed
    {
        return $this->productService->getList(
            dataLimit: self::PICKER_LIMIT,
            relations: self::PICKER_RELATIONS,
            filters: [ProductRetailService::FILTER_ACTIVE => true],
        );
    }
}
