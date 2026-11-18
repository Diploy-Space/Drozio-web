<?php

namespace App\Services\New\Sandbox;

use App\Http\Controllers\Admin\ThirdParty\SandboxKycController;
use App\Services\New\BusinessSettingService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SandboxKycClient
{
    public function __construct(private readonly BusinessSettingService $businessSettingService) {}

    public function getConfig(): array
    {
        $setting = $this->businessSettingService->findBy(params: ['type' => SandboxKycController::SETTING_KEY]);
        $config = $setting ? (json_decode((string) $setting->value, true) ?: []) : [];
        return array_merge([
            'status' => 0,
            'mode' => 'test',
            'base_url_test' => 'https://test-api.sandbox.co.in',
            'base_url_live' => 'https://api.sandbox.co.in',
            'api_key' => '',
            'api_secret' => '',
            'api_version' => '1.0',
            'verify_pan' => 1,
            'verify_gstin' => 1,
            'verify_bank' => 1,
            'manual_fallback' => 1,
        ], $config);
    }

    public function isEnabled(): bool
    {
        $c = $this->getConfig();
        return (int) ($c['status'] ?? 0) === 1 && !empty($c['api_key']) && !empty($c['api_secret']);
    }

    public function baseUrl(): string
    {
        $c = $this->getConfig();
        $mode = $c['mode'] ?? 'test';
        return rtrim((string) ($mode === 'live' ? ($c['base_url_live'] ?? '') : ($c['base_url_test'] ?? '')), '/') ?: ($mode === 'live' ? 'https://api.sandbox.co.in' : 'https://test-api.sandbox.co.in');
    }

    public function apiVersion(): string
    {
        return (string) ($this->getConfig()['api_version'] ?? '1.0');
    }

    public function canVerify(string $type): bool
    {
        $c = $this->getConfig();
        if (!$this->isEnabled()) return false;
        return match ($type) {
            'pan' => (int) ($c['verify_pan'] ?? 1) === 1,
            'gst', 'gstin' => (int) ($c['verify_gstin'] ?? 1) === 1,
            'bank' => (int) ($c['verify_bank'] ?? 1) === 1,
            default => false,
        };
    }

    public function manualFallbackEnabled(): bool
    {
        return (int) ($this->getConfig()['manual_fallback'] ?? 1) === 1;
    }

    protected function cacheKey(): string
    {
        $c = $this->getConfig();
        return 'sandbox_kyc_token_' . md5(($c['mode'] ?? 'test') . '|' . ($c['api_key'] ?? '') . '|' . $this->baseUrl());
    }

    public function authenticate(bool $force = false): array
    {
        $c = $this->getConfig();
        if (empty($c['api_key']) || empty($c['api_secret'])) {
            return ['ok' => false, 'message' => 'Sandbox API credentials are not configured'];
        }
        $key = $this->cacheKey();
        if (!$force) {
            $cached = Cache::get($key);
            if (!empty($cached)) return ['ok' => true, 'access_token' => $cached, 'cached' => true];
        }

        try {
            $resp = Http::timeout(20)->acceptJson()->withHeaders([
                'x-api-key' => $c['api_key'],
                'x-api-secret' => $c['api_secret'],
                'x-api-version' => $this->apiVersion(),
            ])->post($this->baseUrl() . '/authenticate');

            $json = $resp->json() ?: [];
            $token = $json['data']['access_token'] ?? $json['access_token'] ?? null;
            if ($resp->successful() && $token) {
                Cache::put($key, $token, now()->addHours(23));
                return ['ok' => true, 'access_token' => $token, 'cached' => false];
            }
            return ['ok' => false, 'message' => $json['message'] ?? 'Sandbox authentication failed', 'status' => $resp->status(), 'raw' => $this->safeMeta($json)];
        } catch (\Throwable $e) {
            Log::warning('Sandbox KYC auth error', ['error' => $e->getMessage()]);
            return ['ok' => false, 'message' => 'Sandbox authentication unavailable'];
        }
    }

    public function request(string $method, string $path, array $payload = [], array $query = [], int $retry = 1): array
    {
        $auth = $this->authenticate();
        if (!($auth['ok'] ?? false)) {
            return ['ok' => false, 'unavailable' => true, 'message' => $auth['message'] ?? 'Authentication failed'];
        }
        $c = $this->getConfig();
        $url = $this->baseUrl() . '/' . ltrim($path, '/');
        $headers = [
            'authorization' => $auth['access_token'], // Sandbox expects token without Bearer
            'x-api-key' => $c['api_key'],
            'x-api-version' => $this->apiVersion(),
            'Content-Type' => 'application/json',
        ];

        try {
            $req = Http::timeout(25)->acceptJson()->withHeaders($headers);
            if (!empty($query)) $req = $req->withQueryParameters($query);
            $resp = $req->send(strtoupper($method), $url, empty($payload) ? [] : ['json' => $payload]);
            $json = $resp->json();
            if (!is_array($json)) $json = ['raw' => $resp->body()];

            // retry once on auth failure
            if ($resp->status() === 401 && $retry > 0) {
                $this->authenticate(true);
                return $this->request($method, $path, $payload, $query, $retry - 1);
            }

            return [
                'ok' => $resp->successful(),
                'status' => $resp->status(),
                'data' => $json,
                'unavailable' => $resp->serverError() || $resp->status() === 429,
            ];
        } catch (\Throwable $e) {
            Log::warning('Sandbox KYC request error', ['path' => $path, 'error' => $e->getMessage()]);
            return ['ok' => false, 'unavailable' => true, 'message' => 'Sandbox service unavailable'];
        }
    }

    public function safeMeta($data): array
    {
        if (!is_array($data)) return [];
        $keep = ['code', 'message', 'timestamp', 'transaction_id'];
        $out = [];
        foreach ($keep as $k) if (array_key_exists($k, $data)) $out[$k] = $data[$k];
        if (isset($data['data']) && is_array($data['data'])) {
            // keep small, non-sensitive summary keys if present
            foreach (['status', 'pan', 'gstin', 'account_exists', 'name_at_bank', 'name_match', 'aadhaar_seeding_status', 'message'] as $k) {
                if (isset($data['data'][$k])) $out['data_'.$k] = $data['data'][$k];
            }
        }
        return $out;
    }
}
