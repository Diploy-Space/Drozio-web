<?php

use App\Enums\AuthErrorCode;
use App\Http\Middleware\ActivationCheckMiddleware;
use App\Http\Middleware\AdminMiddleware;
use App\Http\Middleware\APIGuestMiddleware;
use App\Http\Middleware\APILocalizationMiddleware;
use App\Http\Middleware\Authenticate;
use App\Http\Middleware\CanonicalHostMiddleware;
use App\Http\Middleware\CollapseResponseWhitespaceMiddleware;
use App\Http\Middleware\CompressResponseMiddleware;
use App\Http\Middleware\CustomerMiddleware;
use App\Http\Middleware\DatabaseRefreshMiddleware;
use App\Http\Middleware\DeliveryManAuth;
use App\Http\Middleware\DeliveryPartnerConfigMiddleware;
use App\Http\Middleware\DeliveryPartnerServiceMiddleware;
use App\Http\Middleware\DetectMobile;
use App\Http\Middleware\EncryptCookies;
use App\Http\Middleware\ErpTokenAuthMiddleware;
use App\Http\Middleware\GuestMiddleware;
use App\Http\Middleware\HandleInertiaRequests;
use App\Http\Middleware\InstallationMiddleware;
use App\Http\Middleware\Localization;
use App\Http\Middleware\LogUserBrowsingNavigationMiddleware;
use App\Http\Middleware\MaintenanceModeMiddleware;
use App\Http\Middleware\ModulePermissionMiddleware;
use App\Http\Middleware\RedirectIfAuthenticated;
use App\Http\Middleware\SellerApiAuthMiddleware;
use App\Http\Middleware\SellerMiddleware;
use App\Http\Middleware\V6\DeliveryManApiAuthMiddleware;
use App\Http\Middleware\V6\VendorApiAuthMiddleware;
use App\Http\Middleware\VendorDeliveryPartnerConfigMiddleware;
use App\Http\Middleware\VendorDeliveryPartnerSetupMiddleware;
use App\Http\Middleware\VerifyCsrfToken;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Auth\Middleware\AuthenticateWithBasicAuth;
use Illuminate\Auth\Middleware\Authorize;
use Illuminate\Auth\Middleware\EnsureEmailIsVerified;
use Illuminate\Auth\Middleware\RequirePassword;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Foundation\Http\Middleware\CheckForMaintenanceMode;
use Illuminate\Foundation\Http\Middleware\ConvertEmptyStringsToNull;
use Illuminate\Foundation\Http\Middleware\TrimStrings;
use Illuminate\Foundation\Http\Middleware\ValidatePostSize;
use Illuminate\Http\Exceptions\ThrottleRequestsException;
use Illuminate\Http\Middleware\HandleCors;
use Illuminate\Http\Middleware\SetCacheHeaders;
use Illuminate\Http\Middleware\TrustProxies;
use Illuminate\Http\Request;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Illuminate\Routing\Middleware\ValidateSignature;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

if (! defined('DOMAIN_POINTED_DIRECTORY')) {
    define('DOMAIN_POINTED_DIRECTORY', env('DOMAIN_POINTED_DIRECTORY', 'public'));
}

return Application::configure(basePath: dirname(__DIR__))
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->use([
            CompressResponseMiddleware::class,
            CollapseResponseWhitespaceMiddleware::class,
            TrustProxies::class,
            CanonicalHostMiddleware::class,
            CheckForMaintenanceMode::class,
            ValidatePostSize::class,
            TrimStrings::class,
            ConvertEmptyStringsToNull::class,
            DatabaseRefreshMiddleware::class,
            HandleCors::class,
        ]);
        $middleware->group('web', [
            EncryptCookies::class,
            AddQueuedCookiesToResponse::class,
            StartSession::class,
            ShareErrorsFromSession::class,
            VerifyCsrfToken::class,
            SubstituteBindings::class,
            Localization::class,
            DetectMobile::class,
            HandleInertiaRequests::class,
        ]);
        $middleware->group('api', [
            'throttle:3000,1',
            SubstituteBindings::class,
        ]);
        $middleware->alias([
            'auth' => Authenticate::class,
            'auth.basic' => AuthenticateWithBasicAuth::class,
            'bindings' => SubstituteBindings::class,
            'cache.headers' => SetCacheHeaders::class,
            'can' => Authorize::class,
            'guest' => RedirectIfAuthenticated::class,
            'password.confirm' => RequirePassword::class,
            'signed' => ValidateSignature::class,
            'throttle' => ThrottleRequests::class,
            'verified' => EnsureEmailIsVerified::class,
            'admin' => AdminMiddleware::class,
            'seller' => SellerMiddleware::class,
            'vendor' => SellerMiddleware::class,
            'customer' => CustomerMiddleware::class,
            'module' => ModulePermissionMiddleware::class,
            'installation-check' => InstallationMiddleware::class,
            'actch' => ActivationCheckMiddleware::class,
            'api_lang' => APILocalizationMiddleware::class,
            'maintenance_mode' => MaintenanceModeMiddleware::class,
            'delivery_man_auth' => DeliveryManAuth::class,
            'v6_delivery_man_auth' => DeliveryManApiAuthMiddleware::class,
            'seller_api_auth' => SellerApiAuthMiddleware::class,
            'v6_seller_auth' => VendorApiAuthMiddleware::class,
            'erp_token' => ErpTokenAuthMiddleware::class,
            'guestCheck' => GuestMiddleware::class,
            'apiGuestCheck' => APIGuestMiddleware::class,
            'logUserBrowsingNavigation' => LogUserBrowsingNavigationMiddleware::class,
            'detectMobile' => DetectMobile::class,
            'delivery_partner_service' => DeliveryPartnerServiceMiddleware::class,
            'vendor_delivery_partner_setup' => VendorDeliveryPartnerSetupMiddleware::class,
            'delivery_partner_config' => DeliveryPartnerConfigMiddleware::class,
            'vendor_delivery_partner_config' => VendorDeliveryPartnerConfigMiddleware::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        $exceptions->render(function (AuthenticationException $exception, Request $request) {
            if (! $request->is('api/v6/*')) {
                return null;
            }

            $envelope = config('response.unauthorized_401');
            $message = translate('Your session has expired. Please log in again');

            return response()->json([
                'identical_code' => $envelope['identical_code'],
                'message' => $message,
                'content' => null,
                'errors' => [
                    ['error_code' => AuthErrorCode::UNAUTHENTICATED, 'message' => $message],
                ],
            ], $envelope['http_response_code'], ['WWW-Authenticate' => 'Bearer']);
        });

        $exceptions->render(function (NotFoundHttpException $exception, Request $request) {
            if (! $request->is('api/v6/*')) {
                return null;
            }

            $envelope = config('response.not_found_404');
            $message = translate('We could not find what you were looking for');

            return response()->json([
                'identical_code' => $envelope['identical_code'],
                'message' => $message,
                'content' => null,
                'errors' => [
                    ['error_code' => $envelope['identical_code'], 'message' => $message],
                ],
            ], $envelope['http_response_code']);
        });

        $exceptions->render(function (ThrottleRequestsException $exception, Request $request) {
            if (! $request->is('api/v6/*')) {
                return null;
            }

            $envelope = config('response.too_many_requests_429');
            $retryAfter = (int) ($exception->getHeaders()['Retry-After'] ?? 0);
            $message = translate('Too many attempts. Please try again later');

            return response()->json([
                'identical_code' => $envelope['identical_code'],
                'message' => $message,
                'content' => ['retry_after' => $retryAfter],
                'errors' => [
                    ['error_code' => AuthErrorCode::TOO_MANY_REQUESTS, 'message' => $message],
                ],
            ], $envelope['http_response_code'], $exception->getHeaders());
        });
    })
    ->create();
