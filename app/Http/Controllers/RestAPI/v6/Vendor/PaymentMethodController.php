<?php

namespace App\Http\Controllers\RestAPI\v6\Vendor;

use App\Http\Controllers\RestAPI\v6\BaseApiController;
use App\Http\Requests\Vendor\PaymentInfo\AddRequest;
use App\Http\Requests\Vendor\PaymentInfo\StatusUpdateRequest;
use App\Http\Requests\Vendor\PaymentInfo\UpdateRequest;
use App\Http\Resources\V6\Vendor\PaymentMethodResource;
use App\Services\New\VendorWithdrawMethodInfoService;
use App\Services\New\WithdrawalMethodService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\ApiPlayground\app\Attributes\ApiDoc;

class PaymentMethodController extends BaseApiController
{
    public function __construct(
        private readonly VendorWithdrawMethodInfoService $vendorWithdrawMethodInfoService,
        private readonly WithdrawalMethodService $withdrawalMethodService,
    ) {}

    #[ApiDoc(
        summary: 'Payout methods the vendor has saved',
        tag: 'Payment Method (Vendor)',
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

        $methods = $this->vendorWithdrawMethodInfoService->getList(
            searchValue: $args['search'],
            dataLimit: $limit,
            page: $offset,
            relations: ['withdraw_method'],
            filters: $this->scope($request),
        );

        return $this->responseFormatter(
            config: config('response.data_fetched'),
            content: [
                'data' => PaymentMethodResource::collection($methods),
                'params' => $this->paramsFormatter($args),
                'pagination' => $this->paginateFormatter($methods),
            ],
        );
    }

    #[ApiDoc(
        summary: 'Save a payout method',
        tag: 'Payment Method (Vendor)',
        status: [201, 401, 403, 422],
        guard: 'seller',
    )]
    public function store(AddRequest $request): JsonResponse
    {
        $method = $this->vendorWithdrawMethodInfoService->create(
            data: $request->all(),
            methodFields: $this->methodFields(id: $request['withdraw_method_id']),
            userId: (int) $request->attributes->get('seller')->id,
        );

        return $this->responseFormatter(
            config: config('response.payment_information_added_successfully'),
            content: ['data' => new PaymentMethodResource($method)],
        );
    }

    #[ApiDoc(
        summary: 'Update one of the vendor own payout methods',
        tag: 'Payment Method (Vendor)',
        status: [401, 403, 404, 422],
        guard: 'seller',
    )]
    public function update(UpdateRequest $request, string $id): JsonResponse
    {
        $method = $this->vendorWithdrawMethodInfoService->update(
            id: (int) $id,
            data: $request->all(),
            methodFields: $this->methodFields(id: $request['withdraw_method_id']),
            userId: (int) $request->attributes->get('seller')->id,
        );

        return $method === null
            ? $this->notFound()
            : $this->responseFormatter(
                config: config('response.payment_information_updated_successfully'),
                content: ['data' => new PaymentMethodResource($method),
                ],
            );
    }

    #[ApiDoc(
        summary: 'Turn one of the vendor own payout methods on or off',
        tag: 'Payment Method (Vendor)',
        status: [401, 403, 404, 422],
        guard: 'seller',
    )]
    public function updateStatus(StatusUpdateRequest $request, string $id): JsonResponse
    {
        $updated = $this->vendorWithdrawMethodInfoService->updateStatus(
            id: (int) $id,
            status: (bool) $request['status'],
            userId: (int) $request->attributes->get('seller')->id,
        );

        return $updated
            ? $this->responseFormatter(config: config('response.payment_method_status_updated_successfully'))
            : $this->notFound();
    }

    #[ApiDoc(
        summary: 'Make one of the vendor own payout methods the default',
        description: 'Clears the flag on the vendor other methods first, so exactly one stays default.',
        tag: 'Payment Method (Vendor)',
        status: [401, 403, 404],
        guard: 'seller',
    )]
    public function updateDefault(Request $request, string $id): JsonResponse
    {
        $updated = $this->vendorWithdrawMethodInfoService->markAsDefault(
            id: (int) $id,
            userId: (int) $request->attributes->get('seller')->id,
        );

        return $updated
            ? $this->responseFormatter(config: config('response.default_payment_method_updated_successfully'))
            : $this->notFound();
    }

    #[ApiDoc(
        summary: 'Delete one of the vendor own payout methods',
        tag: 'Payment Method (Vendor)',
        status: [401, 403, 404],
        guard: 'seller',
    )]
    public function delete(Request $request, string $id): JsonResponse
    {
        $deleted = $this->vendorWithdrawMethodInfoService->delete(
            id: (int) $id,
            userId: (int) $request->attributes->get('seller')->id,
        );

        return $deleted
            ? $this->responseFormatter(config: config('response.payment_information_deleted_successfully'))
            : $this->notFound();
    }

    /** @return array<string, mixed> */
    private function scope(Request $request): array
    {
        return ['user_id' => $request->attributes->get('seller')->id];
    }

    /** @return array<int, mixed> */
    private function methodFields(mixed $id): array
    {
        return $this->withdrawalMethodService->find(id: $id)?->method_fields ?? [];
    }

    private function notFound(): JsonResponse
    {
        return $this->responseFormatter(config: config('response.payment_method_not_found'));
    }
}
