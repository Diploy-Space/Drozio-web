<?php

namespace Modules\Courier\app\Http\Controllers\RestAPI\v6\Seller;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Modules\ApiPlayground\app\Attributes\ApiDoc;
use Modules\Courier\app\Exceptions\CourierException;
use Modules\Courier\app\Http\Concerns\PresentsTrackingEvents;
use Modules\Courier\app\Http\Requests\Admin\EstimateCourierChargeRequest;
use Modules\Courier\app\Http\Requests\Admin\ReviseCourierDetailsRequest;
use Modules\Courier\app\Http\Requests\Admin\SendToCourierRequest;
use Modules\Courier\app\Services\New\CourierService;
use Modules\Courier\app\Services\New\ProviderRegistry;

class CourierDispatchController extends Controller
{
    use PresentsTrackingEvents;

    private const HOST_ORDER_REFERENCE_HINT = 'the order id; its business module picks the carrier account and the on/off switch that gates this call';

    public function __construct(
        protected readonly CourierService $courier,
        private readonly ProviderRegistry $providers,
    ) {}

    #[ApiDoc(
        summary: 'Dispatch an order to a delivery partner',
        description: 'Books a shipment with the selected courier provider for the given order.',
        tag: 'Delivery Partner',
        hints: ['host_order_reference' => self::HOST_ORDER_REFERENCE_HINT],
        status: [401, 403, 404, 422],
        guard: 'seller',
    )]
    public function store(SendToCourierRequest $request): JsonResponse
    {
        try {
            $shipment = $this->courier->ship(
                $request->toOrderData(),
                $request->selectedProvider(),
                $request->replacesExistingShipment(),
            );
        } catch (CourierException $exception) {
            return $this->failed($exception);
        }

        return response()->json([
            'message' => translate('Sent to courier').'. '.translate('Consignment').': '.$shipment->consignmentId,
            'data' => $this->courier->shipmentDetailsFor(
                $shipment->hostOrderReference ?? (string) $request->input('host_order_reference'),
            ),
        ], 201);
    }

    #[ApiDoc(
        summary: 'Revise an order\'s courier dispatch details',
        description: 'Updates the recipient and parcel details already sent to the delivery partner for this order.',
        tag: 'Delivery Partner',
        hints: ['host_order_reference' => self::HOST_ORDER_REFERENCE_HINT],
        status: [401, 403, 404, 422],
        guard: 'seller',
    )]
    public function revise(ReviseCourierDetailsRequest $request): JsonResponse
    {
        try {
            $shipment = $this->courier->reviseDispatchDetails($request->toOrderData(), $this->registry()->owner());
        } catch (CourierException $exception) {
            return $this->failed($exception);
        }

        return response()->json([
            'message' => translate('Courier details updated successfully'),
            'data' => $this->courier->shipmentDetailsFor((string) $shipment->host_order_reference),
        ]);
    }

    #[ApiDoc(
        summary: 'Estimate an order\'s delivery charge',
        description: 'Asks the selected delivery partner for a delivery-charge quote without booking a shipment; carriers that cannot quote report `supported: false`.',
        tag: 'Delivery Partner',
        query: ['host_order_reference' => 'nullable|string'],
        hints: ['host_order_reference' => self::HOST_ORDER_REFERENCE_HINT],
        status: [401, 403, 404, 422],
        guard: 'seller',
    )]
    public function estimate(EstimateCourierChargeRequest $request): JsonResponse
    {
        try {
            $quote = $this->courier->estimate(quote: $request->toQuoteData(), provider: $request->selectedProvider());
        } catch (CourierException $exception) {
            return $this->failed($exception);
        }

        if ($quote === null) {
            return response()->json(['data' => ['supported' => false]]);
        }

        return response()->json(['data' => [
            'supported' => true,
            'total' => $quote->totalCost,
            'currency' => $quote->currency,
        ]]);
    }

    #[ApiDoc(
        summary: 'Track a shipment\'s delivery status',
        description: 'The current status and event timeline for a consignment already booked with a delivery partner.',
        tag: 'Delivery Partner',
        status: [401, 403, 404],
        guard: 'seller',
    )]
    public function track(string $consignmentId): JsonResponse
    {
        $owner = $this->registry()->owner();

        try {
            $status = $this->presentShipmentStatus($this->courier->refreshStatus($consignmentId, $owner));
            $events = $this->courier->track($consignmentId, $owner);
        } catch (CourierException $exception) {
            return $this->failed($exception, 404);
        }

        return response()->json([
            'status' => $status,
            'events' => $this->presentTrackingEvents($events),
        ]);
    }

    protected function registry(): ProviderRegistry
    {
        return $this->providers;
    }

    protected function failed(CourierException $exception, int $status = 422): JsonResponse
    {
        return response()->json(['message' => $exception->getMessage()], $status);
    }
}
