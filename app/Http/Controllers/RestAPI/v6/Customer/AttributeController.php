<?php

namespace App\Http\Controllers\RestAPI\v6\Customer;

use App\Http\Controllers\RestAPI\v6\BaseApiController;
use App\Http\Resources\V6\Customer\AttributeResource;
use App\Services\New\AttributeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\ApiPlayground\app\Attributes\ApiDoc;

class AttributeController extends BaseApiController
{
    public function __construct(private readonly AttributeService $attributeService) {}

    #[ApiDoc(
        summary: 'Attribute list',
        tag: 'Attribute',
        query: ['limit' => 'nullable|string', 'offset' => 'nullable|integer', 'search' => 'nullable|string', 'sort_by' => 'nullable|string'],
    )]
    public function getList(Request $request): JsonResponse
    {
        $search = $request->query('search');
        $sortBy = $request->query('sort_by');
        $limit = $request->query('limit', DEFAULT_DATA_LIMIT);
        $page = (int) $request->query('offset', 1);

        $attributes = $this->attributeService->getList(
            searchValue: $search,
            dataLimit: $limit,
            page: $page,
            relations: ['translations' => fn ($query) => $query->where('locale', app()->getLocale())->where('key', 'name')],
            sortBy: $sortBy,
        );

        return $this->responseFormatter(
            config: config('response.data_fetched'),
            content: [
                'data' => AttributeResource::collection($attributes),
                'params' => $this->paramsFormatter([
                    'search' => $search,
                    'sort_by' => $sortBy,
                ]),
                'pagination' => $this->paginateFormatter($attributes),
            ],
        );
    }
}
