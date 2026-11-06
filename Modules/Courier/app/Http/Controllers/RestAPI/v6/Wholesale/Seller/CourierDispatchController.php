<?php

namespace Modules\Courier\app\Http\Controllers\RestAPI\v6\Wholesale\Seller;

use Illuminate\Http\JsonResponse;
use Modules\ApiPlayground\app\Attributes\ApiDoc;
use Modules\Courier\app\Http\Controllers\RestAPI\v6\Seller\CourierDispatchController as RetailCourierDispatchController;
use Modules\Courier\app\Http\Requests\Admin\EstimateCourierChargeRequest;
use Modules\Courier\app\Http\Requests\Admin\ReviseCourierDetailsRequest;
use Modules\Courier\app\Http\Requests\Admin\SendToCourierRequest;
use Modules\Courier\app\ValueObjects\CourierOwner;

class CourierDispatchController extends RetailCourierDispatchController
{
    private const HOST_ORDER_REFERENCE_HINT = 'the wholesale order id this shipment belongs to; a retail order is refused here';

    #[ApiDoc(
        summary: 'Dispatch a wholesale order to a delivery partner',
        description: 'Books a shipment with the selected courier provider for the given wholesale order, using the seller\'s wholesale carrier account.',
        tag: 'Wholesale Delivery Partner',
        hints: ['host_order_reference' => self::HOST_ORDER_REFERENCE_HINT],
        status: [401, 403, 404, 422],
        guard: 'seller',
    )]
    public function store(SendToCourierRequest $request): JsonResponse
    {
        return $this->refusedForRetailOrder() ?? parent::store($request);
    }

    #[ApiDoc(
        summary: 'Revise a wholesale order\'s courier dispatch details',
        description: 'Updates the recipient and parcel details already sent to the delivery partner for this wholesale order.',
        tag: 'Wholesale Delivery Partner',
        hints: ['host_order_reference' => self::HOST_ORDER_REFERENCE_HINT],
        status: [401, 403, 404, 422],
        guard: 'seller',
    )]
    public function revise(ReviseCourierDetailsRequest $request): JsonResponse
    {
        return $this->refusedForRetailOrder() ?? parent::revise($request);
    }

    #[ApiDoc(
        summary: 'Estimate a wholesale order\'s delivery charge',
        description: 'Asks the selected delivery partner for a delivery-charge quote without booking a shipment; carriers that cannot quote report `supported: false`.',
        tag: 'Wholesale Delivery Partner',
        query: ['host_order_reference' => 'nullable|string'],
        hints: ['host_order_reference' => self::HOST_ORDER_REFERENCE_HINT],
        status: [401, 403, 404, 422],
        guard: 'seller',
    )]
    public function estimate(EstimateCourierChargeRequest $request): JsonResponse
    {
        return $this->refusedForRetailOrder() ?? parent::estimate($request);
    }

    #[ApiDoc(
        summary: 'Track a wholesale shipment\'s delivery status',
        description: 'The current status and event timeline for a consignment already booked with a delivery partner for a wholesale order.',
        tag: 'Wholesale Delivery Partner',
        status: [401, 403, 404],
        guard: 'seller',
    )]
    public function track(string $consignmentId): JsonResponse
    {
        return parent::track($consignmentId);
    }

    private function refusedForRetailOrder(): ?JsonResponse
    {
        if ($this->registry()->owner()->businessModule === CourierOwner::BUSINESS_MODULE_WHOLESALE) {
            return null;
        }

        return response()->json([
            'message' => translate('This order is not a wholesale order'),
        ], 404);
    }
}
