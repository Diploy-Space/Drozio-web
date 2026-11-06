<?php

namespace App\Http\Controllers\Web\v6;

use App\Enums\BusinessModule;
use App\Http\Controllers\Controller;
use App\Http\Requests\Customer\HelpAndSupport\SupportTicketAddRequest;
use App\Http\Requests\Customer\HelpAndSupport\SupportTicketCommentRequest;
use App\Services\New\SupportTicket\SupportTicketConvService;
use App\Services\New\SupportTicket\SupportTicketService;
use Devrabiul\ToastMagic\Facades\ToastMagic;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;

class SupportTicketController extends Controller
{
    private const MODULE_TICKET_LIST_ROUTES = [
        BusinessModule::WHOLESALE => 'wholesale.tickets.index',
    ];

    private const LIST_LIMIT = 10;

    private const PRIORITY_TONES = [
        'urgent' => 'danger',
        SupportTicketService::PRIORITY_HIGH => 'warning',
        SupportTicketService::PRIORITY_MEDIUM => 'success',
        SupportTicketService::PRIORITY_LOW => 'info',
    ];

    private const STATUS_TONES = [
        SupportTicketService::STATUS_PENDING => 'warning',
        SupportTicketService::STATUS_OPEN => 'success',
        SupportTicketService::STATUS_CLOSED => 'muted',
    ];

    private const WORD_ICON_EXTENSIONS = ['.pdf', '.doc', '.docx', '.txt'];

    public function __construct(
        private readonly SupportTicketService $supportTicketService,
        private readonly SupportTicketConvService $supportTicketConvService,
    ) {}

    public function index(): View|RedirectResponse
    {
        if (! auth('customer')->check()) {
            return redirect()->route('home');
        }

        $supportTickets = $this->supportTicketService->getList(
            dataLimit: self::LIST_LIMIT,
            filters: [
                'customer_id' => (int) auth('customer')->id(),
                'business_module' => BusinessModule::RETAIL,
            ],
        );

        return view(themeViewName(key: 'account_tickets'), [
            'supportTickets' => $supportTickets,
            'ticketStoreUrl' => route('ticket-submit'),
            'ticketDescriptionMaxLength' => SupportTicketService::DESCRIPTION_MAX_LENGTH,
            'moduleTabs' => customerProfileModuleTabs(menuKey: 'tickets', activeModule: BusinessModule::RETAIL),
        ]);
    }

    public function store(SupportTicketAddRequest $request): RedirectResponse
    {
        $this->supportTicketService->create(
            data: [
                'business_module' => BusinessModule::RETAIL,
                'customer_id' => auth('customer')->check() ? (int) auth('customer')->id() : null,
                'subject' => $request['ticket_subject'],
                'type' => $request['ticket_type'],
                'priority' => $request['ticket_priority'],
                'description' => $request['ticket_description'],
                'status' => SupportTicketService::STATUS_OPEN,
            ],
            attachments: $request->file('image') ?? [],
        );

        return back();
    }

    public function show(string $id): View|RedirectResponse
    {
        $ticket = $this->supportTicketService->findWithConversations(
            id: $id,
            customerId: $this->customerId(),
            relations: ['conversations.adminInfo'],
        );

        if ($ticket === null) {
            ToastMagic::warning(translate('Invalid ticket'));

            return back();
        }

        return view(themeViewName(key: 'ticket_view'), [
            'ticket' => $ticket,
            'ticketHeader' => $this->getTicketHeader(ticket: $ticket),
            'messages' => $this->getMessageRows(ticket: $ticket),
        ]);
    }

    public function reply(SupportTicketCommentRequest $request, string $id): RedirectResponse
    {
        $attachments = $this->getAttachments($request);

        if ($attachments === [] && empty($request['comment'])) {
            ToastMagic::error(translate('Type something').'!');

            return back();
        }

        $ticket = $this->findOwnedTicket(id: $id);

        if ($ticket === null) {
            ToastMagic::warning(translate('Invalid ticket'));

            return back();
        }

        DB::transaction(function () use ($ticket, $request, $attachments): void {
            $this->supportTicketService->reopen(id: $ticket->id);

            $this->supportTicketConvService->create(
                data: [
                    'support_ticket_id' => $ticket->id,
                    'customer_message' => $request['comment'],
                ],
                attachments: $attachments,
            );
        });

        ToastMagic::success(translate('Message sent successfully').'!');

        return back();
    }

    public function close(string $id): RedirectResponse
    {
        $ticket = $this->findOwnedTicket(id: $id);

        if ($ticket !== null) {
            $this->supportTicketService->close(id: $ticket->id);

            ToastMagic::success(translate('Ticket closed').'!');
        }

        return redirect($this->getTicketListUrl(businessModule: $ticket?->business_module));
    }

    public function delete(string $id): RedirectResponse
    {
        $ticket = $this->findOwnedTicket(id: $id);

        if ($ticket !== null) {
            DB::transaction(function () use ($ticket): void {
                $this->supportTicketConvService->deleteBy(params: ['support_ticket_id' => $ticket->id]);

                $this->supportTicketService->delete(id: $ticket->id);
            });
        }

        return back();
    }

    /**
     * @return array<string, mixed>
     */
    private function getTicketHeader(Model $ticket): array
    {
        $customer = auth('customer')->user();
        $priority = strtolower((string) $ticket['priority']);
        $status = (string) $ticket['status'];

        $typeLabels = [
            'Website problem' => translate('Website problem'),
            'Partner request' => translate('Partner request'),
            'Complaint' => translate('Complaint'),
            'Information inquiry' => translate('Information inquiry'),
        ];

        $priorityLabels = [
            'urgent' => translate('Urgent'),
            SupportTicketService::PRIORITY_HIGH => translate('High'),
            SupportTicketService::PRIORITY_MEDIUM => translate('Medium'),
            SupportTicketService::PRIORITY_LOW => translate('Low'),
        ];

        $statusLabels = [
            SupportTicketService::STATUS_PENDING => translate('Pending'),
            SupportTicketService::STATUS_OPEN => translate('Open'),
            SupportTicketService::STATUS_CLOSED => translate('Closed'),
        ];

        return [
            'customerName' => trim($customer?->f_name.' '.$customer?->l_name),
            'customerEmail' => $customer?->email,
            'customerAvatar' => getStorageImages(path: $customer?->image_full_url, type: 'avatar'),
            'createdAt' => $ticket->created_at?->format('M d, Y h:i A'),
            'typeLabel' => $typeLabels[$ticket['type']] ?? $ticket['type'],
            'priorityLabel' => $priorityLabels[$priority] ?? $ticket['priority'],
            'priorityTone' => self::PRIORITY_TONES[$priority] ?? 'info',
            'statusLabel' => $statusLabels[$status] ?? ucfirst($status),
            'statusTone' => self::STATUS_TONES[$status] ?? 'muted',
            'isClosable' => $status !== SupportTicketService::STATUS_CLOSED,
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function getMessageRows(Model $ticket): array
    {
        $rows = [
            $this->getMessageRow(
                id: 'ticket-'.$ticket['id'],
                text: $ticket['description'],
                isIncoming: false,
                model: $ticket,
            ),
        ];

        foreach ($ticket->conversations as $conversation) {
            $isIncoming = (bool) $conversation['admin_id'];

            $rows[] = $this->getMessageRow(
                id: $conversation['id'],
                text: $isIncoming ? $conversation['admin_message'] : $conversation['customer_message'],
                isIncoming: $isIncoming,
                model: $conversation,
                avatar: $isIncoming
                    ? getStorageImages(path: $conversation->adminInfo?->image_full_url, type: 'avatar')
                    : null,
            );
        }

        return $rows;
    }

    /**
     * @return array<string, mixed>
     */
    private function getMessageRow(string|int $id, ?string $text, bool $isIncoming, Model $model, ?string $avatar = null): array
    {
        $attachments = $this->supportTicketService->getAttachmentGroups(model: $model);

        return [
            'id' => $id,
            'text' => $text,
            'isIncoming' => $isIncoming,
            'avatar' => $avatar,
            'documents' => $this->getDocumentRows(documents: $attachments['documents']),
            'media' => $this->getMediaRows(media: $attachments['media']),
            'time' => $model->created_at->format('h:i:A | M d'),
            'relativeTime' => $model->created_at->diffForHumans(),
        ];
    }

    /**
     * @param  array<int, array<string, mixed>>  $documents
     * @return array<int, array<string, mixed>>
     */
    private function getDocumentRows(array $documents): array
    {
        return array_map(static fn (array $document): array => [
            ...$document,
            'iconName' => in_array($document['extension'], self::WORD_ICON_EXTENSIONS, true) ? 'word-icon' : 'default-icon',
        ], $documents);
    }

    /**
     * @param  array<int, array<string, mixed>>  $media
     * @return array<int, array<string, mixed>>
     */
    private function getMediaRows(array $media): array
    {
        return array_map(static fn (array $file): array => [
            ...$file,
            'preview' => getStorageImages(path: $file['link'], type: 'product'),
        ], $media);
    }

    /**
     * @return array<int, UploadedFile>
     */
    private function getAttachments(SupportTicketCommentRequest $request): array
    {
        return array_merge(
            $this->getUploadedFiles(request: $request, key: 'media'),
            $this->getUploadedFiles(request: $request, key: 'file'),
        );
    }

    /**
     * @return array<int, UploadedFile>
     */
    private function getUploadedFiles(SupportTicketCommentRequest $request, string $key): array
    {
        $files = $request->file($key);

        return match (true) {
            $files === null => [],
            is_array($files) => $files,
            default => [$files],
        };
    }

    private function getTicketListUrl(?string $businessModule): string
    {
        $routeName = self::MODULE_TICKET_LIST_ROUTES[$businessModule] ?? null;

        return $routeName !== null && Route::has($routeName) ? route($routeName) : route('account-tickets');
    }

    private function findOwnedTicket(string $id): mixed
    {
        return $this->supportTicketService->find(id: $id, customerId: $this->customerId());
    }

    private function customerId(): int
    {
        return (int) auth('customer')->id();
    }
}
