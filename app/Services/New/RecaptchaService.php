<?php

namespace App\Services\New;

use Illuminate\Support\Facades\Http;

class RecaptchaService
{
    public const REASON_TOKEN_MISSING = 'token_missing';

    public const REASON_VERIFICATION_FAILED = 'verification_failed';

    public const REASON_SCORE_TOO_LOW = 'score_too_low';

    public const REASON_ACTION_MISMATCH = 'action_mismatch';

    private const VERIFY_ENDPOINT = 'https://www.google.com/recaptcha/api/siteverify';

    private const MINIMUM_SCORE = 0.5;

    /** @return array{verified: bool, reason: ?string} */
    public function verifyToken(string $secretKey, ?string $token, ?string $action = null, ?string $remoteIp = null): array
    {
        if (empty($token)) {
            return ['verified' => false, 'reason' => self::REASON_TOKEN_MISSING];
        }

        $result = Http::asForm()->post(self::VERIFY_ENDPOINT, [
            'secret' => $secretKey,
            'response' => $token,
            'remoteip' => $remoteIp,
        ])->json();

        if (! ($result['success'] ?? false)) {
            return ['verified' => false, 'reason' => self::REASON_VERIFICATION_FAILED];
        }

        if (isset($result['score']) && $result['score'] < self::MINIMUM_SCORE) {
            return ['verified' => false, 'reason' => self::REASON_SCORE_TOO_LOW];
        }

        if ($action !== null && isset($result['action']) && $result['action'] !== $action) {
            return ['verified' => false, 'reason' => self::REASON_ACTION_MISMATCH];
        }

        return ['verified' => true, 'reason' => null];
    }
}
