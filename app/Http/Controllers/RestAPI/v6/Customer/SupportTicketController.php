<?php

namespace App\Http\Controllers\RestAPI\v6\Customer;

use App\Http\Controllers\RestAPI\v6\BaseApiController;
use App\Http\Requests\Customer\SupportTicket\AddRequest;
use App\Http\Requests\Customer\SupportTicket\ReplyRequest;
use App\Http\Resources\V6\Customer\SupportTicketConversationResource;
use App\Http\Resources\V6\Customer\SupportTicketResource;
use App\Services\New\SupportTicket\SupportTicketConvService;
use App\Services\New\SupportTicket\SupportTicketService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Modules\ApiPlayground\app\Attributes\ApiDoc;

class SupportTicketController extends BaseApiController
{
    public function __construct(
        private readonly SupportTicketService $supportTicketService,
        private readonly SupportTicketConvService $supportTicketConvService,
    ) {}

    #[ApiDoc(
        summary: 'Support tickets the signed-in customer opened',
        tag: 'Support Ticket',
        query: ['limit' => 'nullable|string', 'offset' => 'nullable|integer', 'search' => 'nullable|string'],
        status: [401],
        guard: 'api',
    )]
    public function getList(Request $request): JsonResponse
    {
        $args = [
            'search' => $request->query('search'),
        ];
        $limit = $request->query('limit', DEFAULT_DATA_LIMIT);
        $offset = (int) $request->query('offset', 1);

        $tickets = $this->supportTicketService->getList(
            searchValue: $args['search'],
            dataLimit: $limit,
            page: $offset,
            withCount: ['conversations'],
            filters: ['customer_id' => $this->customerId($request)],
        );

        return $this->responseFormatter(
            config: config('response.data_fetched'),
            content: [
                'data' => SupportTicketResource::collection($tickets),
                'params' => $this->paramsFormatter($args),
                'pagination' => $this->paginateFormatter($tickets),
            ],
        );
    }

    #[ApiDoc(
        summary: 'Open a support ticket',
        description: 'Attachments are optional and re-encoded to webp. The ticket opens with status pending.',
        tag: 'Support Ticket',
        status: [201, 401, 422],
        guard: 'api',
    )]
    public function create(AddRequest $request): JsonResponse
    {
        $ticket = $this->supportTicketService->create(
            data: $request->validated() + ['customer_id' => $this->customerId($request)],
            attachments: $request->file('image') ?? [],
        );

        return $this->responseFormatter(
            config: config('response.support_ticket_created'),
            content: ['data' => new SupportTicketResource($ticket)],
        );
    }

    #[ApiDoc(
        summary: 'Conversation on one support ticket',
        description: 'The ticket itself is returned alongside the replies, so the opening message is not faked as a conversation row.',
        tag: 'Support Ticket',
        query: ['limit' => 'nullable|string', 'offset' => 'nullable|integer'],
        status: [401, 404],
        guard: 'api',
    )]
    public function getConversations(Request $request, string|int $id): JsonResponse
    {
        $ticket = $this->findOwnedTicket($request, $id);

        if ($ticket === null) {
            return $this->ticketNotFound();
        }

        $args = [
            'search' => $request->query('search'),
        ];

        $limit = $request->query('limit', DEFAULT_DATA_LIMIT);

        $offset = (int) $request->query('offset', 1);

        $conversations = $this->supportTicketConvService->getList(
            dataLimit: $limit,
            page: $offset,
            filters: ['support_ticket_id' => $ticket->id],
        );

        return $this->responseFormatter(
            config: config('response.data_fetched'),
            content: [
                'data' => SupportTicketConversationResource::collection($conversations),
                'ticket' => new SupportTicketResource($ticket),
                'params' => $this->paramsFormatter($args),
                'pagination' => $this->paginateFormatter($conversations),
            ],
        );
    }

    #[ApiDoc(
        summary: 'Reply to a support ticket',
        description: 'Re-opens a closed ticket and appends the reply in one transaction.',
        tag: 'Support Ticket',
        status: [201, 401, 404, 422],
        guard: 'api',
    )]
    public function reply(ReplyRequest $request, string|int $id): JsonResponse
    {
        $ticket = $this->findOwnedTicket($request, $id);

        if ($ticket === null) {
            return $this->ticketNotFound();
        }

        $conversation = DB::transaction(function () use ($request, $ticket) {
            $this->supportTicketService->reopen(id: $ticket->id);

            return $this->supportTicketConvService->create(
                data: [
                    'support_ticket_id' => $ticket->id,
                    'customer_message' => $request['message'],
                ],
                attachments: $request->file('image') ?? [],
            );
        });

        return $this->responseFormatter(
            config: config('response.support_ticket_replied'),
            content: ['data' => new SupportTicketConversationResource($conversation)],
        );
    }

    #[ApiDoc(
        summary: 'Close a support ticket',
        tag: 'Support Ticket',
        status: [401, 404],
        guard: 'api',
    )]
    public function close(Request $request, string|int $id): JsonResponse
    {
        $ticket = $this->findOwnedTicket($request, $id);

        if ($ticket === null) {
            return $this->ticketNotFound();
        }

        $this->supportTicketService->close(id: $ticket->id);

        return $this->responseFormatter(config: config('response.support_ticket_closed'));
    }

    private function findOwnedTicket(Request $request, string|int $id): mixed
    {
        return $this->supportTicketService->find(id: $id, customerId: $this->customerId($request));
    }

    private function ticketNotFound(): JsonResponse
    {
        return $this->responseFormatter(config: config('response.support_ticket_not_found'));
    }

    private function customerId(Request $request): int
    {
        return (int) $request->user()->id;
    }
}
