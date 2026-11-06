<?php

namespace App\Http\Controllers\RestAPI\v6\Deliveryman;

use App\Http\Controllers\RestAPI\v6\BaseApiController;
use App\Http\Resources\V6\Vendor\EmergencyContactResource;
use App\Services\New\EmergencyContactService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\ApiPlayground\app\Attributes\ApiDoc;

class EmergencyContactController extends BaseApiController
{
    public function __construct(private readonly EmergencyContactService $emergencyContactService) {}

    #[ApiDoc(
        summary: 'Emergency contact list of the shop the delivery man works for',
        tag: 'Emergency Contact',
        query: ['limit' => 'nullable|string', 'offset' => 'nullable|integer'],
    )]
    public function getList(Request $request): JsonResponse
    {
        $limit = $request->query('limit', DEFAULT_DATA_LIMIT);
        $offset = (int) $request->query('offset', 1);

        $contacts = $this->emergencyContactService->getList(
            dataLimit: $limit,
            page: $offset,
            filters: [
                'user_id' => (int) $request->attributes->get('delivery_man')->seller_id,
                'status' => 1,
            ],
        );

        return $this->responseFormatter(
            config: config('response.data_fetched'),
            content: [
                'data' => EmergencyContactResource::collection($contacts),
                'pagination' => $this->paginateFormatter($contacts),
            ],
        );
    }
}
