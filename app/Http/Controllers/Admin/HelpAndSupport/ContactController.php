<?php

namespace App\Http\Controllers\Admin\HelpAndSupport;

use App\Http\Controllers\BaseController;
use App\Http\Requests\Admin\HelpAndSupport\ContactAddRequest;
use App\Services\New\BusinessSettingService;
use App\Services\New\ContactService;
use App\Services\New\MailService;
use Devrabiul\ToastMagic\Facades\ToastMagic;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ContactController extends BaseController
{
    private const SEEN = 1;

    private const NOT_SEEN = 0;

    public function __construct(
        private readonly ContactService $contactService,
        private readonly BusinessSettingService $businessSettingService,
        private readonly MailService $mailService,
    ) {}

    public function index(?Request $request, ?string $type = null): View
    {
        $contacts = $this->contactService->getList(
            searchValue: $request['searchValue'],
            dataLimit: $this->getPaginationLimit(),
        );

        return view('admin-views.contacts.list', [
            'contacts' => $contacts,
            'overview' => $this->getMessageOverview(),
        ]);
    }

    public function getListByFilter(Request $request): JsonResponse
    {
        $contacts = $this->contactService->getList(
            searchValue: $request['searchValue'],
            dataLimit: $this->getPaginationLimit(),
            filters: $this->getReplyStatusFilter(status: $request['status']),
        );

        return response()->json([
            'view' => view('admin-views.contacts._table', compact('contacts'))->render(),
            'count' => $contacts->total(),
        ]);
    }

    public function getView(string $id): View|RedirectResponse
    {
        $contact = $this->contactService->find(id: $id);

        if ($contact === null) {
            ToastMagic::error(translate('Not found'));

            return redirect()->route('admin.people.contact.list');
        }

        return view('admin-views.contacts.view', [
            'contact' => $contact,
            'reply' => $this->getReply(reply: $contact['reply']),
            'isMailConfigured' => $this->isMailConfigured(),
        ]);
    }

    public function add(ContactAddRequest $request): JsonResponse
    {
        $this->contactService->create(data: $request->all());

        return response()->json(['success' => translate('Your message sent successfully')]);
    }

    public function update(Request $request, string $id): RedirectResponse
    {
        $this->contactService->update(id: $id, data: ['seen' => self::SEEN]);
        ToastMagic::success(translate('Message checked').'.');

        return redirect()->route('admin.people.contact.list');
    }

    public function sendMail(Request $request, string $id): RedirectResponse
    {
        $contact = $this->contactService->find(id: $id);

        if ($contact === null) {
            ToastMagic::error(translate('Not found'));

            return redirect()->route('admin.people.contact.list');
        }

        if (! $this->isMailConfigured()) {
            ToastMagic::error(translate('Configure your mail setup first'));

            return back();
        }

        $isSent = $this->mailService->sendContactReply(
            email: $contact['email'],
            companyName: $this->businessSettingService->findBy(params: ['type' => 'company_name'])?->value ?? '',
            subject: $request['subject'],
            body: $request['mail_body'],
        );

        if (! $isSent) {
            ToastMagic::error(translate('This mail could not be sent').'.');
            ToastMagic::info(translate('Please go to 3rd Party').' > '.translate('Mail Config').','.translate('And check if you have enabled and saved your settings properly'));

            return back();
        }

        $this->contactService->update(id: $id, data: [
            'reply' => json_encode(['subject' => $request['subject'], 'body' => $request['mail_body']]),
        ]);
        ToastMagic::success(translate('Mail sent successfully'));

        return back();
    }

    public function delete(Request $request): JsonResponse
    {
        $this->contactService->delete(id: $request['id']);

        return response()->json([
            'message' => translate('Message deleted successfully'),
        ], 200);
    }

    /** @return array<string, string>|null */
    private function getReply(?string $reply): ?array
    {
        $decoded = $reply === null ? null : json_decode($reply, true);

        return is_array($decoded) ? $decoded : null;
    }

    /** @return array<string, int> */
    private function getMessageOverview(): array
    {
        $total = $this->contactService->count();
        $notReplied = $this->contactService->count(filters: ['reply' => null]);

        return [
            'total' => $total,
            'replied' => $total - $notReplied,
            'notReplied' => $notReplied,
            'unread' => $this->contactService->count(filters: ['seen' => self::NOT_SEEN]),
        ];
    }

    private function getPaginationLimit(): int|string
    {
        return $this->businessSettingService->findBy(params: ['type' => 'pagination_limit'])?->value ?? DEFAULT_DATA_LIMIT;
    }

    private function getReplyStatusFilter(?string $status): array
    {
        $replyStatuses = [ContactService::REPLY_STATUS_REPLIED, ContactService::REPLY_STATUS_NOT_REPLIED];

        return in_array($status, $replyStatuses, true) ? ['reply' => $status] : [];
    }

    private function isMailConfigured(): bool
    {
        $mailConfig = $this->getMailConfig(type: 'mail_config');

        if (($mailConfig['status'] ?? 0) == 0) {
            $mailConfig = $this->getMailConfig(type: 'mail_config_sendgrid');
        }

        return ($mailConfig['status'] ?? 0) == 1;
    }

    private function getMailConfig(string $type): array
    {
        $setting = $this->businessSettingService->findBy(params: ['type' => $type]);

        return $setting === null ? [] : (json_decode($setting['value'], true) ?? []);
    }
}
