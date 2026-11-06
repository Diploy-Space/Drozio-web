<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CompressResponseMiddleware
{
    private const MINIMUM_COMPRESSIBLE_BYTES = 1024;

    private const COMPRESSIBLE_TYPES = [
        'text/html',
        'text/plain',
        'text/css',
        'text/xml',
        'text/javascript',
        'application/json',
        'application/javascript',
        'application/x-javascript',
        'application/xml',
        'application/rss+xml',
        'image/svg+xml',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if (! $this->shouldCompress($request, $response)) {
            return $response;
        }

        $compressed = gzencode($response->getContent(), 5);

        if ($compressed === false) {
            return $response;
        }

        $response->setContent($compressed);
        $response->headers->set('Content-Encoding', 'gzip');
        $response->headers->set('Content-Length', (string) strlen($compressed));
        $response->headers->set('Vary', 'Accept-Encoding', false);

        return $response;
    }

    private function shouldCompress(Request $request, Response $response): bool
    {
        if (! function_exists('gzencode') || ini_get('zlib.output_compression')) {
            return false;
        }

        if ($this->isDebugbarInjecting()) {
            return false;
        }

        if ($response instanceof StreamedResponse || $response instanceof BinaryFileResponse) {
            return false;
        }

        if ($response->headers->has('Content-Encoding') || $response->isRedirection()) {
            return false;
        }

        if (headers_sent() || (int) ob_get_length() > 0) {
            return false;
        }

        if (! str_contains(strtolower($request->headers->get('Accept-Encoding', '')), 'gzip')) {
            return false;
        }

        $content = $response->getContent();

        if (! is_string($content) || strlen($content) < self::MINIMUM_COMPRESSIBLE_BYTES) {
            return false;
        }

        $contentType = strtolower(strtok((string) $response->headers->get('Content-Type', ''), ';'));

        return in_array($contentType, self::COMPRESSIBLE_TYPES, true);
    }

    private function isDebugbarInjecting(): bool
    {
        return app()->bound('debugbar') && app('debugbar')->isEnabled();
    }
}
