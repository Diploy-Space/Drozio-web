<?php

namespace App\Http\Middleware;

use App\Utils\Helpers;
use Closure;
use Devrabiul\ToastMagic\Facades\ToastMagic;
use Illuminate\Http\Request;

class ModulePermissionMiddleware
{
    public function handle(Request $request, Closure $next, string ...$modules): mixed
    {
        if (auth('admin')->check()) {
            if (Helpers::module_permission_check(...$modules)) {
                return $next($request);
            }
        }

        if (! auth('admin')->check()) {
            ToastMagic::error(translate('Access denied!!').'!');

            return back();
        }

        $landing = businessModuleManager()->permittedLandingUrl();

        abort_if($landing === '', 403, translate('No module permission is assigned to your role'));

        ToastMagic::error(translate('Access denied!!').'!');

        return redirect($landing);
    }
}
