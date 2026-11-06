<?php

namespace App\Services\New;

use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;

class MaintenanceModeService
{
    public const SYSTEM_USER_APP = 'user_app';

    public const SYSTEM_USER_WEBSITE = 'user_website';

    public const SYSTEM_VENDOR_APP = 'vendor_app';

    public const SYSTEM_DELIVERYMAN_APP = 'deliveryman_app';

    public const SYSTEM_VENDOR_PANEL = 'vendor_panel';

    public const DURATION_UNTIL_CHANGE = 'until_change';

    public const CONTACT_PHONE = 'phone';

    public const CONTACT_EMAIL = 'email';

    private const CACHE_KEY = 'system_maintenance_mode';

    private const MESSAGE_SETTING = 'maintenance_message_setup';

    private const DURATION_SETTING = 'maintenance_duration_setup';

    private const SYSTEM_SETTING = 'maintenance_system_setup';

    public function isUnderMaintenance(string $system): bool
    {
        $state = $this->getState();

        if ($state === null || empty($state['status']) || empty($state['selectedSystems'][$system])) {
            return false;
        }

        return $this->isWithinWindow(state: $state);
    }

    /** @return array<string, mixed> */
    public function getMessages(): array
    {
        return getWebConfig(name: self::MESSAGE_SETTING) ?? [];
    }

    /** @return array<string, mixed> */
    public function getDuration(): array
    {
        return getWebConfig(name: self::DURATION_SETTING) ?? [];
    }

    /** @return array<int, array{type: string, value: string}> */
    public function getContacts(): array
    {
        $messages = $this->getMessages();
        $channels = [
            self::CONTACT_PHONE => ['enabled' => 'business_number', 'setting' => 'company_phone'],
            self::CONTACT_EMAIL => ['enabled' => 'business_email', 'setting' => 'company_email'],
        ];

        $contacts = [];
        foreach ($channels as $type => $channel) {
            $value = getWebConfig(name: $channel['setting']);

            if (($messages[$channel['enabled']] ?? 0) == 1 && ! blank($value)) {
                $contacts[] = ['type' => $type, 'value' => $value];
            }
        }

        return $contacts;
    }

    public function getEndsAt(): ?Carbon
    {
        $duration = $this->getDuration();

        if (($duration['maintenance_duration'] ?? null) === self::DURATION_UNTIL_CHANGE) {
            return null;
        }

        return blank($duration['end_date'] ?? null) ? null : Carbon::parse($duration['end_date']);
    }

    /** @return array<string, mixed> */
    public function getSelectedSystems(): array
    {
        return getWebConfig(name: self::SYSTEM_SETTING) ?? [];
    }

    /** @return array<string, mixed>|null */
    private function getState(): ?array
    {
        $state = Cache::get(self::CACHE_KEY);

        return is_array($state) ? $state : null;
    }

    /** @param  array<string, mixed>  $state */
    private function isWithinWindow(array $state): bool
    {
        $duration = $state['maintenance_duration'] ?? [];

        if (empty($duration)) {
            return false;
        }

        if (($duration['maintenance_duration'] ?? null) === self::DURATION_UNTIL_CHANGE) {
            return true;
        }

        if (empty($state['start_date']) || empty($state['end_date'])) {
            return false;
        }

        return Carbon::now()->between(
            Carbon::parse($state['start_date']),
            Carbon::parse($state['end_date']),
        );
    }
}
