<?php

namespace App\Builder;

use App\Models\Contact;
use Illuminate\Support\Facades\Http;
use Modules\Builder\app\Contracts\ContactProvider as ContactProviderContract;

/**
 * 6Valley host adapter for ContactProvider.
 *
 * Writes storefront contact messages into the same `contacts` table the host
 * contact page uses, so admins see all enquiries in one place. reCAPTCHA
 * mirrors the host: when Google reCAPTCHA is enabled in business settings the
 * v3 token from the storefront is verified against Google; when off the check
 * is skipped (the blade-only session captcha is not ported — inappropriate for
 * an SPA).
 */
class ContactProvider implements ContactProviderContract
{
    public function submit(array $payload): array
    {
        $recaptchaError = $this->verifyRecaptcha(
            $payload['recaptchaToken'] ?? null,
            $payload['ip'] ?? null,
        );
        if ($recaptchaError) {
            return ['success' => false, 'errors' => [$recaptchaError]];
        }

        try {
            Contact::create([
                'name' => (string) ($payload['name'] ?? ''),
                'email' => (string) ($payload['email'] ?? ''),
                'subject' => (string) ($payload['subject'] ?? ''),
                'message' => (string) ($payload['message'] ?? ''),
            ]);
        } catch (\Throwable) {
            return ['success' => false, 'errors' => [[
                'code' => 'persist',
                'message' => translate('Could not send your message') ?: 'Could not send your message.',
            ]]];
        }

        return ['success' => true];
    }

    public function recaptchaSiteKey(): ?string
    {
        $settings = getWebConfig(name: 'recaptcha');
        if (! \is_array($settings) || (int) ($settings['status'] ?? 0) !== 1) {
            return null;
        }
        $key = (string) ($settings['site_key'] ?? '');

        return $key !== '' ? $key : null;
    }

    /**
     * Returns null when reCAPTCHA is disabled OR verification passes; an
     * errors[] entry when it fails. A network/exception failure is treated as
     * a rejection but soft-reported so the admin can see Google's response in
     * the log without surfacing detail to the user.
     */
    private function verifyRecaptcha(?string $token, ?string $ip): ?array
    {
        $settings = getWebConfig(name: 'recaptcha');
        if (! \is_array($settings) || (int) ($settings['status'] ?? 0) !== 1) {
            return null;
        }

        $base = translate('ReCAPTCHA failed') ?: 'ReCAPTCHA failed.';

        $secret = (string) ($settings['secret_key'] ?? '');
        if (! $token || $secret === '') {
            return ['code' => 'recaptcha', 'message' => $base];
        }

        try {
            $response = Http::asForm()->timeout(8)->post('https://www.google.com/recaptcha/api/siteverify', [
                'secret' => $secret,
                'response' => $token,
                'remoteip' => $ip ?? '',
            ]);

            if (! $response->successful() || ($response->json()['success'] ?? false) !== true) {
                return ['code' => 'recaptcha', 'message' => $base];
            }
        } catch (\Throwable $exception) {
            report($exception);

            return ['code' => 'recaptcha', 'message' => $base];
        }

        return null;
    }
}
