<?php

namespace App\Services\New;

use Firebase\JWT\JWT;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;

class SocialProfileService
{
    public const MEDIUM_GOOGLE = 'google';

    public const MEDIUM_FACEBOOK = 'facebook';

    public const MEDIUM_APPLE = 'apple';

    private const CONNECT_TIMEOUT = 2;

    private const REQUEST_TIMEOUT = 4;

    private const APPLE_AUDIENCE = 'https://appleid.apple.com';

    /**
     * @return array<int, string>
     */
    public static function mediums(): array
    {
        return [self::MEDIUM_GOOGLE, self::MEDIUM_FACEBOOK, self::MEDIUM_APPLE];
    }

    /**
     * @return array{social_id: string, email: ?string, f_name: string, l_name: string}|null
     */
    public function fetchProfile(string $medium, string $token, ?string $uniqueId = null, array $appleConfig = []): ?array
    {
        $profile = match ($medium) {
            self::MEDIUM_GOOGLE => $this->googleProfile(token: $token),
            self::MEDIUM_FACEBOOK => $this->facebookProfile(token: $token, uniqueId: (string) $uniqueId),
            self::MEDIUM_APPLE => $this->appleProfile(uniqueId: (string) $uniqueId, config: $appleConfig),
            default => null,
        };

        return $profile === null ? null : $this->normalize(profile: $profile);
    }

    private function googleProfile(string $token): ?array
    {
        $response = $this->request()->get('https://www.googleapis.com/oauth2/v1/userinfo', ['access_token' => $token]);

        return $response->successful() ? $response->json() : null;
    }

    private function facebookProfile(string $token, string $uniqueId): ?array
    {
        $response = $this->request()->get('https://graph.facebook.com/'.$uniqueId, [
            'access_token' => $token,
            'fields' => 'name,email',
        ]);

        return $response->successful() ? $response->json() : null;
    }

    private function appleProfile(string $uniqueId, array $config): ?array
    {
        if (($config['service_file'] ?? '') === '' || ($config['team_id'] ?? '') === '') {
            return null;
        }

        $keyPath = dynamicStorage(path: 'storage/app/public/apple-login/'.$config['service_file']);

        if (! is_readable($keyPath)) {
            return null;
        }

        $clientSecret = JWT::encode([
            'iss' => $config['team_id'],
            'iat' => strtotime('now'),
            'exp' => strtotime('+60 days'),
            'aud' => self::APPLE_AUDIENCE,
            'sub' => $config['client_id'] ?? '',
        ], file_get_contents($keyPath), 'ES256', $config['key_id'] ?? '');

        $response = $this->request()->asForm()->post(self::APPLE_AUDIENCE.'/auth/token', [
            'grant_type' => 'authorization_code',
            'code' => $uniqueId,
            'redirect_uri' => $config['redirect_url'] ?? '',
            'client_id' => $config['client_id'] ?? '',
            'client_secret' => $clientSecret,
        ]);

        if (! $response->successful() || ! isset($response['id_token'])) {
            return null;
        }

        $claims = json_decode(base64_decode(explode('.', $response['id_token'])[1] ?? ''), true);

        return is_array($claims) ? $claims + ['id' => $claims['sub'] ?? null] : null;
    }

    private function normalize(array $profile): ?array
    {
        $socialId = $profile['id'] ?? $profile['sub'] ?? null;

        if ($socialId === null) {
            return null;
        }

        $name = trim((string) ($profile['name'] ?? strstr((string) ($profile['email'] ?? ''), '@', true)));
        $parts = array_values(array_filter(explode(' ', $name)));

        return [
            'social_id' => (string) $socialId,
            'email' => $profile['email'] ?? null,
            'f_name' => count($parts) > 1 ? implode(' ', array_slice($parts, 0, -1)) : ($parts[0] ?? ''),
            'l_name' => count($parts) > 1 ? end($parts) : '',
        ];
    }

    private function request(): PendingRequest
    {
        return Http::connectTimeout(self::CONNECT_TIMEOUT)->timeout(self::REQUEST_TIMEOUT);
    }
}
