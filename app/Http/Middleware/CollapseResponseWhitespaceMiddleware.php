<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CollapseResponseWhitespaceMiddleware
{
    private const MINIMUM_COLLAPSIBLE_BYTES = 1024;

    private const COLLAPSE_PATTERN = '#<(pre|textarea|script|style)\b[^>]*>.*?</\1\s*>|(?:\r\n|\n|\r)[ \t]+|(?:\r\n|\n|\r){2,}#is';

    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if (! $this->shouldCollapse($response)) {
            return $response;
        }

        $collapsed = preg_replace_callback(
            self::COLLAPSE_PATTERN,
            static fn (array $match): string => $match[0][0] === '<' ? $match[0] : "\n",
            (string) $response->getContent()
        );

        if ($collapsed === null) {
            return $response;
        }

        $response->setContent($collapsed);

        if ($response->headers->has('Content-Length')) {
            $response->headers->set('Content-Length', (string) strlen($collapsed));
        }

        return $response;
    }

    private function shouldCollapse(Response $response): bool
    {
        if ($response instanceof StreamedResponse || $response instanceof BinaryFileResponse) {
            return false;
        }

        if ($response->headers->has('Content-Encoding') || $response->isRedirection()) {
            return false;
        }

        if (headers_sent() || (int) ob_get_length() > 0) {
            return false;
        }

        $contentType = strtolower(strtok((string) $response->headers->get('Content-Type', ''), ';'));

        if ($contentType !== 'text/html') {
            return false;
        }

        $content = $response->getContent();

        return is_string($content) && strlen($content) >= self::MINIMUM_COLLAPSIBLE_BYTES;
    }
}
