<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class RecaptchaService
{
    public const FALLBACK_SESSION_KEY = 'default_captcha_required';

    private const MINIMUM_SCORE = 0.5;

    public static function verify(string $token, ?string $action = null): bool
    {
        $secretKey = getWebConfig(name: 'recaptcha')['secret_key'];

        $response = Http::asForm()->post('https://www.google.com/recaptcha/api/siteverify', [
            'secret' => $secretKey,
            'response' => $token,
            'remoteip' => request()->ip(),
        ]);

        $data = $response->json();

        if (! ($data['success'] ?? false)) {
            return false;
        }

        if (isset($data['score']) && $data['score'] < self::MINIMUM_SCORE) {
            return false;
        }

        return $action === null || ! isset($data['action']) || $data['action'] === $action;
    }

    public static function verificationStatus(object|array $request, string $session, ?string $action = 'default', ?bool $firebase = false): array
    {
        $firebaseOTPVerification = getWebConfig(name: 'firebase_otp_verification') ?? [];
        if ($firebase && $firebaseOTPVerification && $firebaseOTPVerification['status']) {
            if (empty($request['g-recaptcha-response'])) {
                return self::failed(message: translate('ReCAPTCHA failed'));
            }

            return self::verified();
        }

        $recaptcha = getWebConfig(name: 'recaptcha');
        if (isset($recaptcha) && $recaptcha['status'] == 1 && ! $request['default_captcha_value']) {
            $token = $request['g-recaptcha-response'];
            if (empty($token) || ! self::verify(token: $token, action: $action)) {
                session()->put(self::FALLBACK_SESSION_KEY, true);

                return self::failed(message: translate('ReCAPTCHA verification failed'), fallback: true);
            }
        } elseif (strtolower(session($session)) != strtolower($request['default_captcha_value'])) {
            return self::failed(message: translate('ReCAPTCHA failed'));
        }

        session()->forget($session);

        return self::verified();
    }

    private static function verified(): array
    {
        return [
            'status' => true,
            'message' => translate('ReCAPTCHA verification successful'),
            'fallback' => false,
        ];
    }

    private static function failed(string $message, bool $fallback = false): array
    {
        return [
            'status' => false,
            'message' => $message,
            'fallback' => $fallback,
        ];
    }
}
