<?php

namespace App\Http\Controllers\RestAPI\v6\Erp;

use App\Http\Controllers\RestAPI\v6\BaseApiController;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\ApiPlayground\app\Attributes\ApiDoc;

class PingController extends BaseApiController
{
    #[ApiDoc(
        summary: 'Confirm the ERP API key and secret are accepted',
        description: 'Send X-API-KEY (or a Bearer token) and X-API-SECRET. Returns the matched token name and the connection time.',
        tag: 'ERP Integration',
        status: [401],
        guard: 'erp',
    )]
    public function ping(Request $request): JsonResponse
    {
        return $this->responseFormatter(
            config: config('response.erp_connection_successful'),
            content: [
                'data' => [
                    'token_name' => $request->attributes->get('erp_token')->name,
                    'connected_at' => now()->toIso8601String(),
                ],
            ],
        );
    }
}
