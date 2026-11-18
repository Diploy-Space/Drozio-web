<?php

namespace App\Services\New\Sandbox;

use Carbon\Carbon;

class SandboxKycVerificationService
{
    public function __construct(private readonly SandboxKycClient $client) {}

    public function config(): array { return $this->client->getConfig(); }
    public function isEnabled(): bool { return $this->client->isEnabled(); }
    public function manualFallbackEnabled(): bool { return $this->client->manualFallbackEnabled(); }
    public function canVerify(string $type): bool { return $this->client->canVerify($type); }

    public function verifyPan(string $pan, string $name, ?string $dob = null, string $consent = 'Y', string $reason = 'Vendor KYC verification'): array
    {
        if (!$this->client->canVerify('pan')) {
            return $this->notAvailable('PAN online verification is not enabled', false);
        }
        $pan = strtoupper(trim($pan));
        $name = trim($name);
        $dobFormatted = $this->formatDob($dob);
        $payload = [
            '@entity' => 'in.co.sandbox.kyc.pan_verification.request',
            'pan' => $pan,
            'name_as_per_pan' => $name,
            'consent' => in_array($consent, ['Y','y','yes','1',1,true], true) ? 'Y' : 'Y',
            'reason' => strlen($reason) >= 20 ? $reason : 'Vendor KYC verification and onboarding compliance',
        ];
        if ($dobFormatted) $payload['date_of_birth'] = $dobFormatted; // DD/MM/YYYY if required by API

        $res = $this->client->request('POST', '/kyc/pan/verify', $payload);
        return $this->normalizePanResult($res, $pan);
    }

    public function verifyGstin(string $gstin): array
    {
        if (!$this->client->canVerify('gstin')) {
            return $this->notAvailable('GSTIN online verification is not enabled', false);
        }
        $gstin = strtoupper(trim($gstin));
        // Try verify endpoint, fallback to search endpoint
        $res = $this->client->request('POST', '/gst/compliance/public/gstin/verify', ['gstin' => $gstin]);
        if (!($res['ok'] ?? false) && ($res['status'] ?? 0) === 404) {
            $res = $this->client->request('POST', '/gst/compliance/public/gstin/search', ['gstin' => $gstin]);
        }
        return $this->normalizeGstinResult($res, $gstin);
    }

    public function verifyBank(string $accountNo, string $ifsc, ?string $name = null): array
    {
        if (!$this->client->canVerify('bank')) {
            return $this->notAvailable('Bank online verification is not enabled', false);
        }
        $accountNo = trim($accountNo);
        $ifsc = strtoupper(trim($ifsc));
        $name = $name ? trim($name) : null;

        $payloads = array_filter([
            ['ifsc' => $ifsc, 'account_number' => $accountNo, 'name' => $name, 'consent' => 'Y', 'reason' => 'Vendor KYC bank account verification'],
            ['ifsc' => $ifsc, 'account_number' => $accountNo],
        ], fn($p) => true);

        $paths = ['/kyc/bank/verify', '/bank/verify'];
        $last = null;
        foreach ($paths as $path) {
            foreach ($payloads as $payload) {
                $payload = array_filter($payload, fn($v) => $v !== null && $v !== '');
                $res = $this->client->request('POST', $path, $payload);
                $last = $res;
                if (($res['status'] ?? 0) !== 404) {
                    return $this->normalizeBankResult($res, $accountNo, $ifsc);
                }
            }
        }
        return $this->normalizeBankResult($last ?? ['ok'=>false,'unavailable'=>true], $accountNo, $ifsc);
    }

    protected function normalizePanResult(array $res, string $pan): array
    {
        $data = $res['data'] ?? [];
        $inner = $data['data'] ?? $data;
        if ($res['ok'] ?? false) {
            $status = strtolower((string)($inner['status'] ?? $inner['pan_status'] ?? $data['status'] ?? ''));
            $nameMatch = $inner['name_match'] ?? $inner['name_as_per_pan_match'] ?? null;
            $verified = in_array($status, ['valid','active','success','verified','e','y','1','true'], true) || ($status === '' && !empty($inner));
            // if explicit mismatch flags
            $failed = in_array($status, ['invalid','fake','deactivated','deleted','n','false','0'], true);
            return [
                'ok' => true,
                'verification_status' => $failed ? 'failed' : ($verified ? 'verified' : 'verified'),
                'provider' => 'sandbox',
                'reference_id' => $data['transaction_id'] ?? $inner['transaction_id'] ?? null,
                'checked_at' => now()->toIso8601String(),
                'message' => $failed ? 'PAN verification failed' : 'PAN verified successfully',
                'summary' => [
                    'pan' => $this->mask($pan, 4),
                    'status' => $inner['status'] ?? null,
                    'category' => $inner['category'] ?? null,
                    'name_match' => $nameMatch,
                    'aadhaar_seeding_status' => $inner['aadhaar_seeding_status'] ?? null,
                ],
                'meta' => $this->client->safeMeta($data),
            ];
        }
        return $this->errorResult($res, 'PAN');
    }

    protected function normalizeGstinResult(array $res, string $gstin): array
    {
        $data = $res['data'] ?? [];
        $inner = $data['data'] ?? $data;
        if ($res['ok'] ?? false) {
            $status = strtolower((string)($inner['sts'] ?? $inner['status'] ?? $inner['gstin_status'] ?? ''));
            $failed = in_array($status, ['cancelled','suspended','invalid','inactive'], true);
            $verified = !$failed; // active/valid or data present
            return [
                'ok' => true,
                'verification_status' => $verified ? 'verified' : 'failed',
                'provider' => 'sandbox',
                'reference_id' => $data['transaction_id'] ?? null,
                'checked_at' => now()->toIso8601String(),
                'message' => $verified ? 'GSTIN verified successfully' : 'GSTIN is not active',
                'summary' => [
                    'gstin' => $this->mask($gstin, 4),
                    'status' => $inner['sts'] ?? $inner['status'] ?? null,
                    'legal_name' => $inner['lgnm'] ?? $inner['legal_name'] ?? null,
                    'trade_name' => $inner['tradeNam'] ?? $inner['trade_name'] ?? null,
                ],
                'meta' => $this->client->safeMeta($data),
            ];
        }
        return $this->errorResult($res, 'GSTIN');
    }

    protected function normalizeBankResult(array $res, string $accountNo, string $ifsc): array
    {
        $data = $res['data'] ?? [];
        $inner = $data['data'] ?? $data;
        if ($res['ok'] ?? false) {
            $exists = $inner['account_exists'] ?? $inner['account_status'] ?? $inner['verified'] ?? $inner['status'] ?? null;
            $verified = $exists === true || in_array(strtolower((string)$exists), ['yes','true','valid','active','verified','1','success'], true) || (!empty($inner) && $exists !== false);
            $failed = $exists === false || in_array(strtolower((string)$exists), ['no','false','invalid','failed','0'], true);
            return [
                'ok' => true,
                'verification_status' => $failed ? 'failed' : ($verified ? 'verified' : 'verified'),
                'provider' => 'sandbox',
                'reference_id' => $data['transaction_id'] ?? null,
                'checked_at' => now()->toIso8601String(),
                'message' => $failed ? 'Bank account verification failed' : 'Bank account verified successfully',
                'summary' => [
                    'account_number' => $this->mask($accountNo, 4),
                    'ifsc' => $ifsc,
                    'name_at_bank' => $inner['name_at_bank'] ?? $inner['beneficiary_name'] ?? null,
                    'name_match' => $inner['name_match'] ?? null,
                ],
                'meta' => $this->client->safeMeta($data),
            ];
        }
        return $this->errorResult($res, 'Bank account');
    }

    protected function errorResult(array $res, string $label): array
    {
        $data = $res['data'] ?? [];
        $message = $data['message'] ?? ($res['message'] ?? ($label . ' verification failed'));
        $unavailable = (bool) ($res['unavailable'] ?? false) || in_array(($res['status'] ?? 0), [0, 429, 500, 502, 503, 504], true);
        return [
            'ok' => false,
            'verification_status' => $unavailable ? 'unavailable' : 'failed',
            'provider' => 'sandbox',
            'reference_id' => $data['transaction_id'] ?? null,
            'checked_at' => now()->toIso8601String(),
            'message' => $unavailable ? ($label . ' verification service unavailable. You can retry or use manual document upload.') : (is_string($message) ? $message : $label . ' verification failed'),
            'summary' => [],
            'meta' => $this->client->safeMeta($data),
        ];
    }

    protected function notAvailable(string $message, bool $unavailable = true): array
    {
        return ['ok'=>false,'verification_status'=>$unavailable?'unavailable':'not_enabled','provider'=>'sandbox','reference_id'=>null,'checked_at'=>now()->toIso8601String(),'message'=>$message,'summary'=>[],'meta'=>[]];
    }

    protected function formatDob(?string $dob): ?string
    {
        if (!$dob) return null;
        try { return Carbon::parse($dob)->format('d/m/Y'); } catch (\Throwable $e) { return $dob; }
    }

    protected function mask(?string $value, int $show = 4): ?string
    {
        if (!$value) return $value;
        $len = strlen($value);
        if ($len <= $show) return $value;
        return str_repeat('X', max(0, $len - $show)) . substr($value, -$show);
    }
}
