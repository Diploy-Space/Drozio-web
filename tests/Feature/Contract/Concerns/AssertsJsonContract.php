<?php

namespace Tests\Feature\Contract\Concerns;

use Illuminate\Testing\TestResponse;
use PHPUnit\Framework\Assert;

/**
 * Contract assertions: verify the exact JSON key structure (via assertJsonStructure)
 * AND the runtime type of every field. assertJsonStructure alone does not check
 * types, so types are asserted separately against the decoded payload.
 *
 * Type spec grammar (per dot-path, '*' wildcards a list index):
 *   'string' | 'integer' | 'double' | 'boolean' | 'array' | 'null'
 *   'number'  = integer|double
 *   unions with '|', e.g. 'integer|null', 'string|null'
 *
 * Types are recorded from the CURRENT response shape (bugs included), not an
 * idealized contract.
 */
trait AssertsJsonContract
{
    protected function assertJsonContract(TestResponse $response, array $structure, array $types, int $status = 200): void
    {
        $response->assertStatus($status);
        $response->assertJsonStructure($structure);

        $json = $response->json();
        foreach ($types as $path => $expected) {
            foreach ($this->resolveContractPaths($json, $path) as $resolved => $value) {
                Assert::assertTrue(
                    $this->contractTypeMatches($value, $expected),
                    "Field [{$resolved}] expected type [{$expected}], got [" . $this->contractTypeOf($value) . "]."
                );
            }
        }
    }

    /** Expand a dot-path (with '*' list wildcards) to concrete path => value pairs. */
    private function resolveContractPaths(mixed $data, string $path): array
    {
        $segments = explode('.', $path);
        $results = ['' => $data];

        foreach ($segments as $segment) {
            $next = [];
            foreach ($results as $prefix => $current) {
                if ($segment === '*') {
                    if (! is_array($current)) {
                        continue;
                    }
                    foreach ($current as $key => $child) {
                        $next[$prefix === '' ? (string) $key : "{$prefix}.{$key}"] = $child;
                    }
                    continue;
                }
                if (is_array($current) && array_key_exists($segment, $current)) {
                    $next[$prefix === '' ? $segment : "{$prefix}.{$segment}"] = $current[$segment];
                }
            }
            $results = $next;
        }

        return $results;
    }

    private function contractTypeMatches(mixed $value, string $expected): bool
    {
        foreach (explode('|', $expected) as $option) {
            if ($this->contractSingleTypeMatches($value, trim($option))) {
                return true;
            }
        }
        return false;
    }

    private function contractSingleTypeMatches(mixed $value, string $type): bool
    {
        return match ($type) {
            'string'  => is_string($value),
            'integer' => is_int($value),
            'double', 'float' => is_float($value),
            'number'  => is_int($value) || is_float($value),
            'boolean' => is_bool($value),
            'array'   => is_array($value),
            'null'    => is_null($value),
            default   => false,
        };
    }

    private function contractTypeOf(mixed $value): string
    {
        return match (true) {
            is_int($value)   => 'integer',
            is_float($value) => 'double',
            is_string($value)=> 'string',
            is_bool($value)  => 'boolean',
            is_array($value) => 'array',
            is_null($value)  => 'null',
            default          => gettype($value),
        };
    }
}
