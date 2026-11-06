<?php

namespace App\Services\New;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class FirebaseOtpService
{
    private const SESSION_CACHE_PREFIX = 'v6:firebase-otp-session:';

    private const CONNECT_TIMEOUT = 2;

    private const REQUEST_TIMEOUT = 4;

    private const VERIFY_ENDPOINT = 'https://identitytoolkit.googleapis.com/v1/accounts:signInWithPhoneNumber';

    private const SEND_CODE_ENDPOINT = 'https://identitytoolkit.googleapis.com/v1/accounts:sendVerificationCode';

    /**
     * @return array{sent: bool, session_info: ?string, error: ?string}
     * @throws ConnectionException
     */
    public function sendPhoneOtp(string $apiKey, string $phoneNumber, ?string $recaptchaToken = null): array
    {
        if ($apiKey === '') {
            return ['sent' => false, 'session_info' => null, 'error' => 'missing_api_key'];
        }

        $response = Http::post(self::SEND_CODE_ENDPOINT.'?key='.$apiKey, [
            'phoneNumber' => $phoneNumber,
            'recaptchaToken' => $recaptchaToken,
        ]);

        $body = $response->json();

        return [
            'sent' => $response->successful(),
            'session_info' => trim($body['sessionInfo'] ?? ''),
            'error' => $body['error']['message'] ?? null,
        ];
    }

    public function rememberSession(string $identity, string $sessionInfo, int $ttlSeconds): void
    {
        Cache::put(self::SESSION_CACHE_PREFIX.sha1($identity), $sessionInfo, $ttlSeconds);
    }

    public function rememberedSession(string $identity): ?string
    {
        return Cache::get(self::SESSION_CACHE_PREFIX.sha1($identity));
    }

    public function forgetSession(string $identity): void
    {
        Cache::forget(self::SESSION_CACHE_PREFIX.sha1($identity));
    }

    /**
     * @return array{verified: bool, phone_number: ?string, error: ?string}
     * @throws ConnectionException
     */
    public function verifyPhoneOtp(string $apiKey, string $sessionInfo, string $phoneNumber, string $code): array
    {
        if ($apiKey === '') {
            return ['verified' => false, 'phone_number' => null, 'error' => 'missing_api_key'];
        }

        $response = Http::connectTimeout(self::CONNECT_TIMEOUT)
            ->timeout(self::REQUEST_TIMEOUT)
            ->post(self::VERIFY_ENDPOINT.'?key='.$apiKey, [
                'sessionInfo' => $sessionInfo,
                'phoneNumber' => $phoneNumber,
                'code' => $code,
            ]);

        $body = $response->json();

        return [
            'verified' => $response->successful() && ! isset($body['error']),
            'phone_number' => $body['phoneNumber'] ?? null,
            'error' => $body['error']['message'] ?? null,
        ];
    }
}
