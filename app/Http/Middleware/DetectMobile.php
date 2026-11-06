<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class DetectMobile
{
    public function handle(Request $request, Closure $next)
    {
        if ($request->expectsJson() || $request->is('api/*') || $request->is('vendor/*') || $request->is('admin/*')) {
            return $next($request);
        }

        $isMobile = false;
        $userAgent = $request->header('User-Agent');
        $isAndroid = preg_match('/android/i', $userAgent);
        $isIOS = preg_match('/iphone/i', $userAgent);

        try {
            $hasBusinessSettings = Cache::remember('_schema_has_business_settings', 21600, fn() => Schema::hasTable('business_settings'));

            if ($hasBusinessSettings) {
                $isMobile = $isAndroid || $isIOS;
                $appDeepLink = getWebConfig(name: 'app_deep_link') ?? [];
                if ($isMobile && $isAndroid && empty($appDeepLink['playstore_redirect_url'])) {
                    $isMobile = false;
                }

                if ($isMobile && $isIOS && empty($appDeepLink['app_store_redirect_url'])) {
                    $isMobile = false;
                }
            }
        } catch (\Exception) {}

        view()->share([
            'isMobile' => $isMobile,
            'isAndroid' => $isAndroid,
            'isIOS' => $isIOS,
        ]);
        return $next($request);
    }
}
