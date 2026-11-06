<?php

namespace App\Http\Controllers\RestAPI\v6\Vendor;

use App\Http\Controllers\RestAPI\v6\BaseApiController;
use App\Http\Requests\Vendor\EmergencyContact\AddRequest;
use App\Http\Requests\Vendor\EmergencyContact\StatusUpdateRequest;
use App\Http\Requests\Vendor\EmergencyContact\UpdateRequest;
use App\Http\Resources\V6\Vendor\EmergencyContactResource;
use App\Services\New\EmergencyContactService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\ApiPlayground\app\Attributes\ApiDoc;

class EmergencyContactController extends BaseApiController
{
    public function __construct(
        private readonly EmergencyContactService $emergencyContactService,
    ) {}

    #[ApiDoc(
        summary: 'Emergency contacts the vendor has saved',
        tag: 'Delivery Man (Vendor)',
        query: ['limit' => 'nullable|string', 'offset' => 'nullable|integer', 'search' => 'nullable|string'],
        status: [401, 403],
        guard: 'seller',
    )]
    public function getList(Request $request): JsonResponse
    {
        $args = [

            'search' => $request->query('search'),

        ];
        $limit = $request->query('limit', DEFAULT_DATA_LIMIT);
        $offset = (int) $request->query('offset', 1);

        $contacts = $this->emergencyContactService->getList(
            searchValue: $args['search'],
            dataLimit: $limit,
            page: $offset,
            filters: $this->scope($request),
        );

        return $this->responseFormatter(
            config: config('response.data_fetched'),
            content: [
                'data' => EmergencyContactResource::collection($contacts),
                'params' => $this->paramsFormatter($args),
                'pagination' => $this->paginateFormatter($contacts),
            ],
        );
    }

    #[ApiDoc(
        summary: 'Add an emergency contact',
        tag: 'Delivery Man (Vendor)',
        status: [201, 401, 403, 422],
        guard: 'seller',
    )]
    public function store(AddRequest $request): JsonResponse
    {
        $contact = $this->emergencyContactService->create(
            data: $request->validated() + ['user_id' => $request->attributes->get('seller')->id],
        );

        return $this->responseFormatter(
            config: config('response.emergency_contact_added_successfully'),
            content: ['data' => new EmergencyContactResource($contact)],
        );
    }

    #[ApiDoc(
        summary: 'Update one of the vendor emergency contacts',
        tag: 'Delivery Man (Vendor)',
        status: [401, 403, 404, 422],
        guard: 'seller',
    )]
    public function update(UpdateRequest $request, string $id): JsonResponse
    {
        $contact = $this->emergencyContactService->updateBy(
            params: $this->scope($request) + ['id' => (int) $id],
            data: $request->validated(),
        );

        return $contact === null
            ? $this->notFound()
            : $this->responseFormatter(
                config: config('response.emergency_contact_updated_successfully'),
                content: ['data' => new EmergencyContactResource($contact),
                ],
            );
    }

    #[ApiDoc(
        summary: 'Turn one of the vendor emergency contacts on or off',
        tag: 'Delivery Man (Vendor)',
        status: [401, 403, 404, 422],
        guard: 'seller',
    )]
    public function updateStatus(StatusUpdateRequest $request, string $id): JsonResponse
    {
        $contact = $this->emergencyContactService->updateBy(
            params: $this->scope($request) + ['id' => (int) $id],
            data: ['status' => (int) $request['status']],
        );

        return $contact === null
            ? $this->notFound()
            : $this->responseFormatter(config: config('response.emergency_contact_status_updated_successfully'));
    }

    #[ApiDoc(
        summary: 'Delete one of the vendor emergency contacts',
        tag: 'Delivery Man (Vendor)',
        status: [401, 403, 404],
        guard: 'seller',
    )]
    public function delete(Request $request, string $id): JsonResponse
    {
        $contact = $this->emergencyContactService->findBy(params: $this->scope($request) + ['id' => (int) $id]);

        if ($contact === null) {
            return $this->notFound();
        }

        $this->emergencyContactService->delete(id: $contact->id);

        return $this->responseFormatter(config: config('response.emergency_contact_deleted_successfully'));
    }

    /** @return array<string, mixed> */
    private function scope(Request $request): array
    {
        return ['user_id' => $request->attributes->get('seller')->id];
    }

    private function notFound(): JsonResponse
    {
        return $this->responseFormatter(config: config('response.emergency_contact_not_found'));
    }
}
