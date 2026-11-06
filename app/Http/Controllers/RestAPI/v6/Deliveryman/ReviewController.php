<?php

namespace App\Http\Controllers\RestAPI\v6\Deliveryman;

use App\Http\Controllers\RestAPI\v6\BaseApiController;
use App\Http\Requests\Deliveryman\Review\SavedStatusRequest;
use App\Http\Resources\V6\Deliveryman\ReviewListResource;
use App\Services\New\ReviewService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\ApiPlayground\app\Attributes\ApiDoc;

class ReviewController extends BaseApiController
{
    public function __construct(private readonly ReviewService $reviewService) {}

    #[ApiDoc(
        summary: 'Reviews customers left for the delivery man',
        description: 'Pass is_saved=1 for the bookmarked ones or is_saved=0 for the rest; omit it for all.',
        tag: 'Delivery-man',
        query: ['is_saved' => 'nullable|boolean', 'limit' => 'nullable|string', 'offset' => 'nullable|integer'],
        status: [401, 403],
        guard: 'deliveryman',
    )]
    public function getList(Request $request): JsonResponse
    {
        $deliveryman = $request->attributes->get('delivery_man');
        $args = [

            'search' => $request->query('search'),

            'is_saved' => $request->query('is_saved'),

        ];
        $limit = $request->query('limit', DEFAULT_DATA_LIMIT);
        $offset = (int) $request->query('offset', 1);

        $filters = ['delivery_man_id' => $deliveryman->id];

        if ($args['is_saved'] !== null) {
            $filters['is_saved'] = (int) filter_var($args['is_saved'], FILTER_VALIDATE_BOOLEAN);
        }

        $reviews = $this->reviewService->getList(
            dataLimit: $limit,
            page: $offset,
            relations: ['customer:id,f_name,l_name,image', 'reply'],
            filters: $filters,
            sortBy: ReviewService::SORT_RECENTLY_UPDATED,
        );

        return $this->responseFormatter(
            config: config('response.data_fetched'),
            content: [
                'data' => ReviewListResource::collection($reviews),
                'params' => $this->paramsFormatter($args),
                'pagination' => $this->paginateFormatter($reviews),
            ],
        );
    }

    #[ApiDoc(
        summary: 'Bookmark or un-bookmark a review',
        tag: 'Delivery-man',
        status: [401, 403, 404, 422],
        guard: 'deliveryman',
    )]
    public function updateSavedStatus(SavedStatusRequest $request, string|int $id): JsonResponse
    {
        $deliveryman = $request->attributes->get('delivery_man');

        $review = $this->reviewService->findBy(params: [
            'id' => $id,
            'delivery_man_id' => $deliveryman->id,
        ]);

        if ($review === null) {
            return $this->responseFormatter(config: config('response.review_not_found'));
        }

        $this->reviewService->updateSavedStatus(id: $review->id, isSaved: $request->boolean('is_saved'));

        return $this->responseFormatter(config: config('response.review_updated'));
    }
}
