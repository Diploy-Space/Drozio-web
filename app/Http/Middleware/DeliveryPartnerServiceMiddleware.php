<?php

namespace App\Http\Middleware;

use App\Enums\BusinessModule;
use Closure;
use Devrabiul\ToastMagic\Facades\ToastMagic;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Symfony\Component\HttpFoundation\Response;

class DeliveryPartnerServiceMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $businessModule = deliveryPartnerRequestBusinessModule();

        if ($this->isAvailable(businessModule: $businessModule)) {
            return $next($request);
        }

        if ($request->expectsJson()) {
            abort(404);
        }

        ToastMagic::error($this->refusalMessage());

        return redirect()->to($this->previousUrl(request: $request, businessModule: $businessModule));
    }

    protected function isAvailable(string $businessModule): bool
    {
        return deliveryPartnerServiceAvailable(businessModule: $businessModule);
    }

    protected function refusalMessage(): string
    {
        return translate('The delivery partner service is currently turned off');
    }

    private function previousUrl(Request $request, string $businessModule): string
    {
        $previous = url()->previous();

        if ($previous !== $request->fullUrl()) {
            return $previous;
        }

        $panel = $request->routeIs('vendor.*') ? 'vendor' : 'admin';
        $dashboardModule = $businessModule === BusinessModule::WHOLESALE ? BusinessModule::WHOLESALE : BusinessModule::RETAIL;
        $dashboardRoute = $panel.'.'.$dashboardModule.'.dashboard.index';

        return Route::has($dashboardRoute) ? route($dashboardRoute) : route($panel.'.'.BusinessModule::RETAIL.'.dashboard.index');
    }
}
