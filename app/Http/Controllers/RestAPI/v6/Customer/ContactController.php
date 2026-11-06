<?php

namespace App\Http\Controllers\RestAPI\v6\Customer;

use App\Http\Controllers\RestAPI\v6\BaseApiController;
use App\Http\Requests\Customer\Contact\AddRequest;
use App\Services\New\ContactService;
use Illuminate\Http\JsonResponse;
use Modules\ApiPlayground\app\Attributes\ApiDoc;

class ContactController extends BaseApiController
{
    public function __construct(private readonly ContactService $contactService) {}

    #[ApiDoc(summary: 'Send a contact message', tag: 'Contact')]
    public function store(AddRequest $request): JsonResponse
    {
        $this->contactService->create(data: $request->validated());

        return $this->responseFormatter(config: config('response.message_sent'));
    }
}
