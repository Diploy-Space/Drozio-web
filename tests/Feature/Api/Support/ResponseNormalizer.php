<?php

namespace Tests\Feature\Api\Support;

/**
 * Replaces values that change between runs with stable placeholders so a
 * snapshot only fails when the response contract changes.
 *
 * Keys keep their position and nulls stay null, so a key disappearing, being
 * renamed, or flipping to null is still a snapshot failure.
 */
final class ResponseNormalizer
{
    private const ID_PLACEHOLDER = '<id>';

    private const TIMESTAMP_PLACEHOLDER = '<timestamp>';

    private const TOKEN_PLACEHOLDER = '<token>';

    private const VOLATILE_PLACEHOLDER = '<volatile>';

    private const TOKEN_KEYS = [
        'token',
        'access_token',
        'refresh_token',
        'auth_token',
        'temporary_token',
        'remember_token',
        'api_token',
        'cm_firebase_token',
        'otp',
        'guest_id',
        'session_id',
        'password',
    ];

    private const TIMESTAMP_KEYS = [
        'date',
        'time',
        'datetime',
        'date_time',
        'timestamp',
        'order_date',
        'delivery_date',
        'expected_delivery_date',
        'order_place_date',
        'last_login',
    ];

    private const DATE_PATTERNS = [
        '/^\d{4}-\d{2}-\d{2}[T ]\d{2}:\d{2}:\d{2}/',
        '/^\d{4}-\d{2}-\d{2}$/',
    ];

    /**
     * @param  array<int, string>  $volatileKeys  extra keys this endpoint makes unstable (generated codes, slugs, file names)
     */
    public function __construct(private readonly array $volatileKeys = []) {}

    public function normalize(mixed $value): mixed
    {
        if (is_array($value)) {
            $normalized = [];

            foreach ($value as $key => $item) {
                $normalized[$key] = is_string($key)
                    ? $this->normalizeKeyed($key, $item)
                    : $this->normalize($item);
            }

            return $normalized;
        }

        return $this->normalizeScalar($value);
    }

    private function normalizeKeyed(string $key, mixed $value): mixed
    {
        if (is_array($value)) {
            return $this->isIdKey($key)
                ? array_map(fn ($item) => is_array($item) ? $this->normalize($item) : self::ID_PLACEHOLDER, $value)
                : $this->normalize($value);
        }

        if ($value === null) {
            return null;
        }

        if (in_array($key, $this->volatileKeys, true)) {
            return self::VOLATILE_PLACEHOLDER;
        }

        if (in_array($key, self::TOKEN_KEYS, true)) {
            return self::TOKEN_PLACEHOLDER;
        }

        if ($this->isIdKey($key)) {
            return self::ID_PLACEHOLDER;
        }

        if ($this->isTimestampKey($key)) {
            return self::TIMESTAMP_PLACEHOLDER;
        }

        return $this->normalizeScalar($value);
    }

    private function normalizeScalar(mixed $value): mixed
    {
        if (is_string($value) && $this->looksLikeDate($value)) {
            return self::TIMESTAMP_PLACEHOLDER;
        }

        return $value;
    }

    private function isIdKey(string $key): bool
    {
        return $key === 'id' || str_ends_with($key, '_id') || str_ends_with($key, '_ids');
    }

    private function isTimestampKey(string $key): bool
    {
        return str_ends_with($key, '_at') || in_array($key, self::TIMESTAMP_KEYS, true);
    }

    private function looksLikeDate(string $value): bool
    {
        foreach (self::DATE_PATTERNS as $pattern) {
            if (preg_match($pattern, $value) === 1) {
                return true;
            }
        }

        return false;
    }
}
