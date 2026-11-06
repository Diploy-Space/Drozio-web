<?php

namespace Modules\Courier\app\Http\Controllers\Admin;

use Devrabiul\ToastMagic\Facades\ToastMagic;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Controller;
use Modules\Courier\app\Http\Requests\SaveCourierProviderRequest;
use Modules\Courier\app\Services\New\CourierConfigService;

class CourierConfigController extends Controller
{
    public function __construct(
        private readonly CourierConfigService $courierConfigService,
    ) {}

    public function index(): View
    {
        $businessModules = $this->courierConfigService->getBusinessModules();

        return view('courier::admin.config.index', [
            'providers' => $this->courierConfigService->getProviderCatalog(),
            'businessModules' => $businessModules,
            'activeBusinessModule' => $this->courierConfigService->getOwner()->businessModule,
            'showBusinessModuleTabs' => count($businessModules) > 1,
        ]);
    }

    public function update(SaveCourierProviderRequest $request): RedirectResponse
    {
        if (config('app.mode') === 'demo') {
            ToastMagic::info(translate('Update option is disabled for demo'));

            return redirect()->route('admin.settings.courier.config.index');
        }

        $this->courierConfigService->save(
            data: $request->validated(),
            owner: $this->courierConfigService->getOwner(),
        );
        ToastMagic::success(translate(':item updated successfully', ['item' => translate('Delivery partner settings')]));

        return back();
    }
}
