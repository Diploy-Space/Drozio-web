<?php

namespace App\Http\Controllers\Admin\Product;

use App\Http\Controllers\BaseController;
use App\Http\Requests\Admin\Attribute\AddRequest;
use App\Http\Requests\Admin\Attribute\UpdateRequest;
use App\Http\Resources\AttributeResource;
use App\Services\New\AttributeService;
use App\Services\New\BusinessSettingService;
use Devrabiul\ToastMagic\Facades\ToastMagic;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class AttributeController extends BaseController
{
    public function __construct(
        private readonly AttributeService $attributeService,
        private readonly BusinessSettingService $businessSettingService,
    ) {}

    public function index(?Request $request, ?string $type = null): View
    {
        $settings = $this->businessSettingService
            ->getList(dataLimit: 'all', filters: ['type' => ['pagination_limit', 'pnc_language']])
            ->pluck('value', 'type');

        $attributes = $this->attributeService->getList(
            searchValue: $request->get('searchValue'),
            dataLimit: $settings->get('pagination_limit'),
            relations: ['translations'],
        );

        $language = json_decode((string) $settings->get('pnc_language'), true) ?? null;
        $defaultLanguage = $language[0];

        return view('admin-views.attribute.view', compact('attributes', 'language', 'defaultLanguage'));
    }

    public function getList(): JsonResponse
    {
        $attributes = $this->attributeService->getList(dataLimit: 'all');

        return response()->json(AttributeResource::collection($attributes));
    }

    public function add(AddRequest $request): RedirectResponse
    {
        $this->attributeService->create(data: $request->all());

        ToastMagic::success(translate('Attribute added successfully'));

        return back();
    }

    public function update(UpdateRequest $request, string|int $id): RedirectResponse
    {
        $attribute = $this->attributeService->update(id: $id, data: $request->all());

        $attribute === null
            ? ToastMagic::error(translate('Not found'))
            : ToastMagic::success(translate(':item updated successfully', ['item' => translate('Attribute')]));

        return back();
    }

    public function delete(Request $request): JsonResponse
    {
        $isDeleted = $this->attributeService->delete(id: $request['id']);

        return $isDeleted
            ? response()->json(['message' => translate('Attribute deleted successfully')])
            : response()->json(['message' => translate('Not found')], 404);
    }
}
