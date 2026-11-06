<?php

namespace App\Http\Controllers\Admin\HelpAndSupport;

use App\Http\Controllers\BaseController;
use App\Http\Requests\Admin\HelpAndSupport\SupportTicketReplyRequest;
use App\Services\New\BusinessSettingService;
use App\Services\New\SupportTicket\SupportTicketConvService;
use App\Services\New\SupportTicket\SupportTicketService;
use Carbon\Carbon;
use Devrabiul\ToastMagic\Facades\ToastMagic;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;

class SupportTicketController extends BaseController
{
    private const FILTER_ALL = 'all';

    private const REPLY_MAX_LENGTH = 189;

    private const WORD_ICON_EXTENSIONS = ['.pdf', '.doc', '.docx', '.txt'];

    public function __construct(
        private readonly SupportTicketService $supportTicketService,
        private readonly SupportTicketConvService $supportTicketConvService,
        private readonly BusinessSettingService $businessSettingService,
    ) {}

    public function index(?Request $request, ?string $type = null): View
    {
        $tickets = $this->supportTicketService->getList(
            searchValue: $request['searchValue'],
            dataLimit: $this->getPaginationLimit(),
            relations: ['customer'],
            filters: $this->getTicketFilters(priority: $request['priority'], status: $request['status']),
            sortBy: SupportTicketService::SORT_RECENTLY_UPDATED,
        );

        $priorityOptions = $this->getPriorityOptions();
        $statusOptions = $this->getStatusOptions();

        return view('admin-views.support-ticket.view', [
            'tickets' => $tickets,
            'priorityOptions' => $priorityOptions,
            'statusOptions' => $statusOptions,
            'priorityBadges' => $this->getPriorityBadges(),
            'statusBadges' => $this->getStatusBadges(),
            'selectedPriority' => $request['priority'] ?? self::FILTER_ALL,
            'selectedStatus' => $request['status'] ?? self::FILTER_ALL,
            'activeFilters' => $this->getActiveFilters($request, $priorityOptions, $statusOptions),
            'resetUrl' => route('admin.people.support-ticket.view'),
        ]);
    }

    public function getView(string $id): View|RedirectResponse
    {
        $ticket = $this->supportTicketService->find(id: $id, relations: ['customer', 'conversations']);

        if ($ticket === null) {
            ToastMagic::error(translate('Support ticket not found'));

            return redirect()->route('admin.people.support-ticket.view');
        }

        $ticketData = $this->getTicketViewData(ticket: $ticket);

        return view('admin-views.support-ticket.singleView', [
            'ticket' => $ticketData,
            'messages' => $this->getMessageRows(ticket: $ticket, customerName: $ticketData['customerName']),
            'replyMaxLength' => self::REPLY_MAX_LENGTH,
        ]);
    }

    public function updateStatus(Request $request): JsonResponse
    {
        $ticket = $this->supportTicketService->find(id: $request['id']);

        if ($ticket === null) {
            return response()->json(['message' => translate('Support ticket not found')], 404);
        }

        $this->supportTicketService->update(
            id: $ticket['id'],
            data: ['status' => $this->getToggledStatus(status: $ticket['status'])],
        );

        return response()->json(['message' => translate('Support ticket status updated')]);
    }

    public function reply(SupportTicketReplyRequest $request, string $id): RedirectResponse
    {
        $attachments = $this->getAttachments($request);
        $message = $request['replay'];

        if ($attachments === [] && $message === null) {
            ToastMagic::warning(translate('Type something').'!');

            return back();
        }

        if ($message && mb_strlen($message) > self::REPLY_MAX_LENGTH) {
            ToastMagic::warning(translate('You cannot send text longer than 189 characters'));

            return back();
        }

        $this->supportTicketConvService->create(
            data: [
                'support_ticket_id' => $id,
                'admin_id' => auth('admin')->id(),
                'admin_message' => $message,
            ],
            attachments: $attachments,
        );

        $this->supportTicketService->update(id: $id, data: ['status' => SupportTicketService::STATUS_OPEN]);

        return back();
    }

    /**
     * @return array<string, mixed>
     */
    private function getTicketViewData(Model $ticket): array
    {
        $customer = $ticket->customer;

        return [
            'id' => $ticket['id'],
            'subject' => $ticket['subject'],
            'typeLabel' => $this->getTypeLabel(type: $ticket['type']),
            'priorityLabel' => $this->getPriorityOptions()[$ticket['priority']] ?? $ticket['priority'],
            'priorityBadge' => $this->getPriorityBadges()[$ticket['priority']] ?? 'badge-soft-secondary',
            'statusLabel' => $this->getStatusOptions()[$ticket['status']] ?? $ticket['status'],
            'statusBadge' => $this->getStatusBadges()[$ticket['status']] ?? 'badge-soft-secondary',
            'isOpen' => $ticket['status'] === SupportTicketService::STATUS_OPEN,
            'openedAt' => $ticket->created_at->format('d M Y, h:i A'),
            'customerName' => $customer ? trim($customer['f_name'].' '.$customer['l_name']) : translate('Customer not found'),
            'customerEmail' => $customer['email'] ?? null,
            'customerPhone' => $customer['phone'] ?? null,
            'customerImage' => getStorageImages(path: $customer->image_full_url ?? '', type: 'backend-profile'),
            'customerRoute' => $customer ? route('admin.people.customer.view', $customer['id']) : null,
        ];
    }

    private function getTypeLabel(?string $type): string
    {
        return ucfirst(str_replace('_', ' ', (string) $type));
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function getMessageRows(Model $ticket, string $customerName): array
    {
        $rows = [];
        $conversations = $ticket->conversations->sortByDesc('id')->values();

        foreach ($conversations as $message) {
            $isIncoming = ! $message['admin_id'];

            $rows[] = [
                'id' => $message['id'],
                'text' => $isIncoming ? $message['customer_message'] : $message['admin_message'],
                'isIncoming' => $isIncoming,
                ...$this->getAttachmentRows(model: $message),
                'sentAt' => $message->created_at,
            ];
        }

        $rows[] = [
            'id' => 'ticket-'.$ticket['id'],
            'text' => $ticket['description'],
            'isIncoming' => true,
            ...$this->getAttachmentRows(model: $ticket),
            'sentAt' => $ticket->created_at,
        ];

        return $this->withThreadMarkers(rows: $rows, customerName: $customerName);
    }

    /**
     * @param  array<int, array<string, mixed>>  $rows
     * @return array<int, array<string, mixed>>
     */
    private function withThreadMarkers(array $rows, string $customerName): array
    {
        foreach ($rows as $index => $row) {
            $older = $rows[$index + 1] ?? null;
            $newer = $rows[$index - 1] ?? null;

            $rows[$index] = [
                ...$row,
                'senderName' => $row['isIncoming'] ? $customerName : translate('You'),
                'time' => $row['sentAt']->format('h:i A'),
                'dateLabel' => $this->getDateLabel(sentAt: $row['sentAt']),
                'startsDay' => $older === null || ! $older['sentAt']->isSameDay($row['sentAt']),
                'showAvatar' => $row['isIncoming'] && ($newer === null || ! $newer['isIncoming']),
            ];
        }

        return $rows;
    }

    private function getDateLabel(Carbon $sentAt): string
    {
        return match (true) {
            $sentAt->isToday() => translate('Today'),
            $sentAt->isYesterday() => translate('Yesterday'),
            default => $sentAt->format('d M Y'),
        };
    }

    /**
     * @return array{documents: array<int, array<string, mixed>>, media: array<int, array<string, mixed>>}
     */
    private function getAttachmentRows(Model $model): array
    {
        $attachments = $this->supportTicketService->getAttachmentGroups(model: $model);

        return [
            'documents' => array_map(static fn (array $document): array => [
                ...$document,
                'icon' => dynamicAsset(path: 'public/assets/back-end/img/'.(
                    in_array($document['extension'], self::WORD_ICON_EXTENSIONS, true) ? 'word-icon' : 'default-icon'
                ).'.png'),
            ], $attachments['documents']),
            'media' => array_map(static fn (array $file): array => [
                ...$file,
                'preview' => getStorageImages(path: $file['link'], type: 'backend-basic'),
            ], $attachments['media']),
        ];
    }

    /**
     * @return array<int, UploadedFile>
     */
    private function getAttachments(SupportTicketReplyRequest $request): array
    {
        return array_merge(
            $this->getUploadedFiles(request: $request, key: 'media'),
            $this->getUploadedFiles(request: $request, key: 'file'),
        );
    }

    /**
     * @return array<int, UploadedFile>
     */
    private function getUploadedFiles(SupportTicketReplyRequest $request, string $key): array
    {
        $files = $request->file($key);

        return match (true) {
            $files === null => [],
            is_array($files) => $files,
            default => [$files],
        };
    }

    /**
     * @param  array<string, string>  $priorityOptions
     * @param  array<string, string>  $statusOptions
     * @return array<int, array<string, string>>
     */
    private function getActiveFilters(Request $request, array $priorityOptions, array $statusOptions): array
    {
        $labelledParams = [
            'searchValue' => [translate('Search'), []],
            'priority' => [translate('Priority'), $priorityOptions],
            'status' => [translate('Status'), $statusOptions],
        ];

        $activeFilters = [];

        foreach ($labelledParams as $param => [$label, $options]) {
            $value = $request[$param];

            if ($value === null || $value === '' || $value === self::FILTER_ALL) {
                continue;
            }

            $activeFilters[] = [
                'label' => $label,
                'value' => $options[$value] ?? $value,
                'removeUrl' => $request->fullUrlWithoutQuery([$param, 'page']),
            ];
        }

        return $activeFilters;
    }

    /** @return array<string, string> */
    private function getPriorityOptions(): array
    {
        $labels = [
            SupportTicketService::PRIORITY_LOW => translate('Low'),
            SupportTicketService::PRIORITY_MEDIUM => translate('Medium'),
            SupportTicketService::PRIORITY_HIGH => translate('High'),
            SupportTicketService::PRIORITY_URGENT => translate('Urgent'),
        ];

        return [self::FILTER_ALL => translate('All Priority')]
            + array_intersect_key($labels, array_flip(SupportTicketService::priorities()));
    }

    /** @return array<string, string> */
    private function getStatusOptions(): array
    {
        return [
            self::FILTER_ALL => translate('All Status'),
            SupportTicketService::STATUS_PENDING => translate('Pending'),
            SupportTicketService::STATUS_OPEN => translate('Open'),
            SupportTicketService::STATUS_CLOSED => translate('Close'),
        ];
    }

    /** @return array<string, string> */
    private function getPriorityBadges(): array
    {
        return [
            SupportTicketService::PRIORITY_LOW => 'badge-soft-secondary',
            SupportTicketService::PRIORITY_MEDIUM => 'badge-soft-info',
            SupportTicketService::PRIORITY_HIGH => 'badge-soft-danger',
            SupportTicketService::PRIORITY_URGENT => 'badge-soft-danger',
        ];
    }

    /** @return array<string, string> */
    private function getStatusBadges(): array
    {
        return [
            SupportTicketService::STATUS_PENDING => 'badge-soft-warning',
            SupportTicketService::STATUS_OPEN => 'badge-soft-success',
            SupportTicketService::STATUS_CLOSED => 'badge-soft-secondary',
        ];
    }

    private function getToggledStatus(string $status): string
    {
        return $status === SupportTicketService::STATUS_OPEN
            ? SupportTicketService::STATUS_CLOSED
            : SupportTicketService::STATUS_OPEN;
    }

    private function getTicketFilters(?string $priority, ?string $status): array
    {
        return array_filter(
            ['priority' => $priority, 'status' => $status],
            static fn ($value) => $value !== null && $value !== '' && $value !== self::FILTER_ALL,
        );
    }

    private function getPaginationLimit(): int|string
    {
        return $this->businessSettingService->findBy(params: ['type' => 'pagination_limit'])?->value ?? DEFAULT_DATA_LIMIT;
    }
}
