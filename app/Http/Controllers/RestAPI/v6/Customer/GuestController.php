<?php

namespace App\Http\Controllers\RestAPI\v6\Customer;

use App\Http\Controllers\RestAPI\v6\BaseApiController;
use App\Services\New\GuestUserService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\ApiPlayground\app\Attributes\ApiDoc;

class GuestController extends BaseApiController
{
    public function __construct(private readonly GuestUserService $guestUserService) {}

    #[ApiDoc(summary: 'Issue a guest id for an anonymous shopper', tag: 'Guest')]
    public function create(Request $request): JsonResponse
    {
        $guest = $this->guestUserService->create(data: ['ip_address' => $request->ip()]);

        return $this->responseFormatter(
            config: config('response.data_fetched'),
            content: ['guest_id' => $guest?->id],
        );
    }
}
