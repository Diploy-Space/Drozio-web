<?php

namespace App\Http\Controllers\RestAPI\v6;

use App\Http\Controllers\Controller;
use App\Traits\ApiResponseTrait;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;

abstract class BaseApiController extends Controller
{
    use ApiResponseTrait;

    protected const LIST_DEFAULT_LIMIT = 10;

    protected const LIST_DEFAULT_OFFSET = 1;

    protected const LIST_LIMIT_ALL = 'all';

    protected const LIST_MAX_LIMIT = 100;

    /** @return array{limit: int, offset: int} */
    protected function listArgs(Request $request, int $defaultLimit = self::LIST_DEFAULT_LIMIT, ?int $maxLimit = null): array
    {
        $limit = $this->positiveInt(value: $request->query('limit'), fallback: $defaultLimit);

        return [
            'limit' => $maxLimit === null ? $limit : min($limit, $maxLimit),
            'offset' => $this->positiveInt(value: $request->query('offset'), fallback: self::LIST_DEFAULT_OFFSET),
        ];
    }

    /** @return array{limit: int|string, offset: int} */
    protected function listArgsAllowingAll(Request $request, int $defaultLimit = self::LIST_DEFAULT_LIMIT): array
    {
        $args = $this->listArgs(request: $request, defaultLimit: $defaultLimit);

        if ($request->query('limit') === self::LIST_LIMIT_ALL) {
            $args['limit'] = self::LIST_LIMIT_ALL;
        }

        return $args;
    }

    private function positiveInt(mixed $value, int $fallback): int
    {
        return filter_var($value, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]) ?: $fallback;
    }

    protected function paramsFormatter(array $params): array
    {
        return array_filter($params, static fn ($value) => $value !== null && $value !== '');
    }

    protected function paginateFormatter(mixed $collection): ?array
    {
        if (! $collection instanceof LengthAwarePaginator) {
            return null;
        }

        return [
            'current_page' => $collection->currentPage(),
            'last_page' => $collection->lastPage(),
            'per_page' => (int) $collection->perPage(),
            'total' => $collection->total(),
            'from' => $collection->firstItem(),
            'to' => $collection->lastItem(),
        ];
    }
}
