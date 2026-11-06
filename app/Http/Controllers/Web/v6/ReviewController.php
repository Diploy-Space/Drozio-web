<?php

namespace App\Http\Controllers\Web\v6;

use App\Http\Controllers\Controller;
use App\Http\Requests\Customer\Review\AddRequest;
use App\Http\Requests\Customer\Review\DeleteImageRequest;
use App\Http\Requests\Customer\Review\DeliverymanAddRequest;
use App\Services\New\OrderService;
use App\Services\New\ReviewService;
use Devrabiul\ToastMagic\Facades\ToastMagic;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Arr;

class ReviewController extends Controller
{
    public function __construct(
        private readonly ReviewService $reviewService,
        private readonly OrderService $orderService,
    ) {}

    public function store(AddRequest $request): RedirectResponse
    {
        $images = Arr::wrap($request->file('fileUpload') ?? []);
        $disallowedExtension = $this->reviewService->hasDisallowedExtension(files: $images);

        if ($disallowedExtension !== null) {
            ToastMagic::error(translate('Invalid file format given').$disallowedExtension);

            return redirect()->back();
        }

        $this->reviewService->createOrUpdateForProduct(
            customerId: (int) auth('customer')->id(),
            productId: $request['product_id'],
            orderId: $request['order_id'],
            comment: $request['comment'],
            rating: (int) $request['rating'],
            reviewId: $request['review_id'],
            images: $images,
        );

        ToastMagic::success(translate('Review added successfully'));

        return redirect()->back();
    }

    public function storeDeliverymanReview(DeliverymanAddRequest $request): RedirectResponse
    {
        $customerId = (int) auth('customer')->id();

        $order = $this->orderService->findBy(params: [
            'id' => $request['order_id'],
            'customer_id' => $customerId,
            'payment_status' => OrderService::PAYMENT_STATUS_PAID,
        ], relations: []);

        if ($order?->delivery_man_id === null) {
            ToastMagic::error(translate('Invalid review'));

            return redirect('/');
        }

        $this->reviewService->upsertDeliverymanReview(
            customerId: $customerId,
            deliveryManId: (int) $order->delivery_man_id,
            orderId: $request['order_id'],
            comment: $request['comment'],
            rating: (int) $request['rating'],
        );

        ToastMagic::success(translate('Review added successfully'));

        return back();
    }

    public function deleteImage(DeleteImageRequest $request): JsonResponse
    {
        $this->reviewService->removeAttachment(reviewId: $request['id'], fileName: $request['name']);

        return response()->json(['message' => translate('Review image removed successfully')], 200);
    }
}
