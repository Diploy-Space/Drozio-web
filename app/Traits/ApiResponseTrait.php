<?php

namespace App\Traits;

use Illuminate\Http\JsonResponse;

trait ApiResponseTrait
{
    protected function responseFormatter(array $config, mixed $content = null, array $errors = []): JsonResponse
    {
        return response()->json([
            'identical_code' => $config['identical_code'] ?? null,
            'message' => isset($config['message']) ? translate($config['message']) : null,
            'content' => $content,
            'errors' => $errors,
        ], $config['http_response_code'] ?? 200);
    }

    protected function authError(array $config, string $errorCode, mixed $content = null): JsonResponse
    {
        return $this->responseFormatter(
            config: $config,
            content: $content,
            errors: [
                ['error_code' => $errorCode, 'message' => translate($config['message'])],
            ],
        );
    }

    protected function authThrottled(array $config, string $errorCode, int $retryAfter): JsonResponse
    {
        return $this->authError(
            config: $config,
            errorCode: $errorCode,
            content: ['retry_after' => $retryAfter],
        )->header('Retry-After', (string) $retryAfter);
    }
}
