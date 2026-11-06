<?php

namespace App\Http\Controllers\Web\v6;

use App\Http\Controllers\Controller;
use App\Http\Requests\Customer\MaintenanceMode\ShowRequest;
use App\Services\New\MaintenanceModeService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

class MaintenanceModeController extends Controller
{
    private const RETURN_TIME_FORMAT = 'd M Y, h:i A';

    public function __construct(private readonly MaintenanceModeService $maintenanceModeService) {}

    public function index(ShowRequest $request): View|RedirectResponse
    {
        $isBypassed = auth('admin')->check();

        if ($isBypassed || ! $this->maintenanceModeService->isUnderMaintenance(system: $request->system())) {
            return redirect()->route('home');
        }

        $messages = $this->maintenanceModeService->getMessages();
        $contacts = $this->contactLinks(contacts: $this->maintenanceModeService->getContacts());
        $endsAt = $this->maintenanceModeService->getEndsAt();

        return view(themeViewName(key: 'maintenance_mode'), [
            'maintenanceMessages' => $messages,
            'maintenanceTypeAndDuration' => $this->maintenanceModeService->getDuration(),
            'maintenanceTitle' => blank($messages['maintenance_message'] ?? null)
                ? translate('We are working on something special').'!'
                : $messages['maintenance_message'],
            'maintenanceBody' => blank($messages['message_body'] ?? null)
                ? translate('We apologize for any inconvenience.').' '.translate('For immediate assistance please contact our support team')
                : $messages['message_body'],
            'maintenanceContacts' => $contacts,
            'maintenanceContactPrompt' => $this->contactPrompt(contacts: $contacts),
            'maintenanceReturnTime' => $endsAt?->format(self::RETURN_TIME_FORMAT),
        ]);
    }

    /**
     * @param  array<int, array{type: string, value: string}>  $contacts
     * @return array<int, array{type: string, value: string, href: string}>
     */
    private function contactLinks(array $contacts): array
    {
        return array_map(
            static fn (array $contact): array => [...$contact, 'href' => match ($contact['type']) {
                MaintenanceModeService::CONTACT_PHONE => 'tel:'.$contact['value'],
                default => 'mailto:'.$contact['value'],
            }],
            $contacts,
        );
    }

    /** @param  array<int, array{type: string, value: string}>  $contacts */
    private function contactPrompt(array $contacts): ?string
    {
        $types = array_column($contacts, 'type');
        $hasPhone = in_array(MaintenanceModeService::CONTACT_PHONE, $types, true);
        $hasEmail = in_array(MaintenanceModeService::CONTACT_EMAIL, $types, true);

        $invitation = match (true) {
            $hasPhone && $hasEmail => translate('Feel free to call or mail us.'),
            $hasPhone => translate('Feel free to call Us.'),
            $hasEmail => translate('Feel free to mail Us.'),
            default => null,
        };

        return $invitation === null ? null : translate('Any query').'? '.$invitation;
    }
}
