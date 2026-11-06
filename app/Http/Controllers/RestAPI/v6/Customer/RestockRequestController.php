<?php

namespace App\Http\Controllers\RestAPI\v6\Customer;

use App\Events\RequestProductRestockEvent;
use App\Http\Controllers\RestAPI\v6\BaseApiController;
use App\Http\Requests\Customer\RestockRequest\AddRequest;
use App\Http\Requests\Customer\RestockRequest\DeleteRequest;
use App\Http\Resources\V6\Customer\RestockRequestResource;
use App\Services\New\GuestUserService;
use App\Services\New\ProductRetailService;
use App\Services\New\RestockProductCustomerService;
use App\Services\New\RestockProductService;
use App\Traits\CustomerOwnerTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Modules\ApiPlayground\app\Attributes\ApiDoc;

class RestockRequestController extends BaseApiController
{
    use CustomerOwnerTrait;

    private const AUTHOR_SUMMARY_COUNT_CAP = 100;

    public function __construct(
        private readonly RestockProductService $restockProductService,
        private readonly RestockProductCustomerService $restockProductCustomerService,
        private readonly ProductRetailService $productRetailService,
        private readonly GuestUserService $guestUserService,
    ) {}

    #[ApiDoc(
        summary: 'Restock requests of the authenticated customer',
        tag: 'Restock Request',
        query: ['limit' => 'nullable|integer', 'offset' => 'nullable|integer'],
        status: [401],
    )]
    public function getList(Request $request): JsonResponse
    {
        $limit = $request->query('limit');

        $restockRequests = $this->restockProductService->getList(
            dataLimit: $limit === null ? 'all' : (int) $limit,
            page: (int) $request->query('offset', 1),
            relations: $this->listRelations(),
            withCount: ['restockProductCustomers'],
            filters: [RestockProductService::FILTER_CUSTOMER_ID => $request->user('api')->id],
            sortBy: RestockProductService::SORT_RECENTLY_UPDATED,
        );

        return $this->responseFormatter(
            config: config('response.data_fetched'),
            content: [
                'data' => RestockRequestResource::collection($restockRequests),
                'params' => $this->paramsFormatter(['limit' => $limit]),
                'pagination' => $this->paginateFormatter($restockRequests),
            ],
        );
    }

    #[ApiDoc(
        summary: 'Ask to be notified when an out-of-stock product variant is back',
        description: 'Send a bearer token. A guest_id alone is refused, as in the customer app. Variant choices are posted as color and choice_* fields.',
        tag: 'Restock Request',
        status: [401, 403, 404, 409, 422],
        auth: false,
        guard: 'api',
    )]
    public function create(AddRequest $request): JsonResponse
    {
        $owner = $this->resolveCustomerOwner($request);

        if ($owner === null) {
            return $this->customerOwnerNotResolved();
        }

        if ($owner['is_guest']) {
            return $this->responseFormatter(config: config('response.restock_request_sign_in_required'));
        }

        $product = $this->productRetailService->findActiveBy(filters: ['id' => $request['id']], relations: ['seller']);

        if ($product === null) {
            return $this->responseFormatter(config: config('response.restock_request_product_not_found'));
        }

        $variant = $this->restockProductService->buildVariantCode(
            colorName: $request->getColorName(),
            choiceOptions: json_decode((string) $product['choice_options']) ?? [],
            choiceValues: $request->all(),
        );

        $restockRequest = DB::transaction(fn (): ?Model => $this->createRestockRequest(
            productId: $product->id,
            variant: $variant,
            customerId: (int) $owner['id'],
        ));

        if ($restockRequest === null) {
            return $this->responseFormatter(config: config('response.restock_request_already_sent'));
        }

        if ($product['added_by'] === 'seller' && $product->seller?->cm_firebase_token) {
            $this->dispatchAuthorNotification();
        }

        return $this->responseFormatter(
            config: config('response.restock_request_sent'),
            content: ['topic' => getRestockProductFCMTopic(restockRequest: $restockRequest)],
        );
    }

    #[ApiDoc(
        summary: 'Remove one restock request, or all of them, for the authenticated customer',
        description: 'Send id (the restock request id) to remove one, or type=all to remove every request.',
        tag: 'Restock Request',
        status: [401, 404, 422],
    )]
    public function delete(DeleteRequest $request): JsonResponse
    {
        $params = $request->isRemovingAll()
            ? ['customer_id' => $request->user('api')->id]
            : ['restock_product_id' => (int) $request['id'], 'customer_id' => $request->user('api')->id];

        if (! $request->isRemovingAll() && $this->restockProductCustomerService->findBy(params: $params) === null) {
            return $this->responseFormatter(config: config('response.restock_request_not_found'));
        }

        DB::transaction(function () use ($params): void {
            $this->restockProductCustomerService->deleteBy(params: $params);
            $this->restockProductService->deleteWithoutCustomers();
        });

        return $this->responseFormatter(config: config('response.restock_request_removed'));
    }

    protected function guestUserService(): GuestUserService
    {
        return $this->guestUserService;
    }

    private function listRelations(): array
    {
        return [
            'product' => fn (BelongsTo $product) => $product->active()->withCount([
                'reviews' => fn ($reviews) => $reviews->active()->whereNull('delivery_man_id'),
            ]),
            'product.rating',
        ];
    }

    private function createRestockRequest(int $productId, string $variant, int $customerId): ?Model
    {
        $restockRequest = $this->restockProductService->firstOrCreateForVariant(productId: $productId, variant: $variant);
        $customerRequest = [
            'restock_product_id' => $restockRequest->id,
            'customer_id' => $customerId,
            'variant' => $variant,
        ];

        if ($this->restockProductCustomerService->findBy(params: $customerRequest) !== null) {
            return null;
        }

        $this->restockProductCustomerService->create(data: $customerRequest);
        $this->restockProductService->update(id: $restockRequest->id, data: ['updated_at' => now()]);

        return $restockRequest;
    }

    private function dispatchAuthorNotification(): void
    {
        $summary = $this->restockProductService->getAuthorRestockSummary(
            addedBy: RestockProductService::ADDED_BY_IN_HOUSE,
            sellerId: null,
        );

        event(new RequestProductRestockEvent(key: 'message_from_customer', data: $this->buildAuthorNotification(summary: $summary)));
    }

    private function buildAuthorNotification(array $summary): array
    {
        if ($summary['product_count'] === 1) {
            $latest = $summary['latest'];
            $count = $latest?->restock_product_customers_count ?? 0;

            return [
                'title' => $latest?->product?->name ?? '',
                'body' => $count < self::AUTHOR_SUMMARY_COUNT_CAP
                    ? translate('This product has').' '.$count.' '.translate('Restock Request')
                    : translate('This product has').' 99+ '.translate('Restock Request'),
                'image' => getStorageImages(path: $latest?->product?->thumbnail_full_url ?? '', type: 'product'),
                'firebase_token' => null,
            ];
        }

        if ($summary['product_count'] > 1) {
            return [
                'title' => translate('Restock Request'),
                'body' => ($summary['product_count'] < self::AUTHOR_SUMMARY_COUNT_CAP ? $summary['product_count'] : '99 +').' '.translate('More products have restock request'),
                'image' => dynamicAsset(path: 'public/assets/back-end/img/icons/restock-request-icon.svg'),
                'firebase_token' => null,
            ];
        }

        return [];
    }
}
