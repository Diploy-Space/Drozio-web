<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CanonicalHostMiddleware
{
    private const WWW_PREFIX = 'www.';

    private const MOVED_PERMANENTLY = 301;

    private const PERMANENT_REDIRECT_KEEPING_METHOD = 308;

    public function handle(Request $request, Closure $next): Response
    {
        $canonicalHost = $this->canonicalHostFor($request->getHost());

        if ($canonicalHost === null) {
            return $next($request);
        }

        return new RedirectResponse(
            $this->canonicalUrl($request, $canonicalHost),
            $request->isMethodSafe() ? self::MOVED_PERMANENTLY : self::PERMANENT_REDIRECT_KEEPING_METHOD,
        );
    }

    private function canonicalHostFor(string $requestHost): ?string
    {
        $hostDomain = strtolower(trim((string) config('app.host_domain')));
        $requestHost = strtolower(trim($requestHost));

        if ($hostDomain === '' || $requestHost === '' || $requestHost === $hostDomain) {
            return null;
        }

        return $this->isWwwVariant(host: $requestHost, hostDomain: $hostDomain) ? $hostDomain : null;
    }

    private function isWwwVariant(string $host, string $hostDomain): bool
    {
        return str_starts_with($hostDomain, self::WWW_PREFIX)
            ? self::WWW_PREFIX.$host === $hostDomain
            : $host === self::WWW_PREFIX.$hostDomain;
    }

    private function canonicalUrl(Request $request, string $canonicalHost): string
    {
        $scheme = $request->getScheme();
        $port = $request->getPort();
        $isDefaultPort = ($scheme === 'http' && $port === 80) || ($scheme === 'https' && $port === 443);

        return $scheme.'://'.$canonicalHost.($isDefaultPort || !$port ? '' : ':'.$port).$request->getRequestUri();
    }
}
