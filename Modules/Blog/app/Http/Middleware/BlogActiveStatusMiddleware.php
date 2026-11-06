<?php

namespace Modules\Blog\app\Http\Middleware;

use Closure;
use Devrabiul\ToastMagic\Facades\ToastMagic;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class BlogActiveStatusMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {

        if (! getWebConfig(name: 'blog_feature_active_status') ?? 0) {
            if (! $request->expectsJson()) {
                ToastMagic::error(translate('Not found'));

                return redirect()->route('home');
            }

            return response()->json([
                'code' => 404,
                'message' => translate('Not found'),
            ], 404);
        }

        return $next($request);
    }
}
