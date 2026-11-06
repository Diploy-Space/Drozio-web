<?php

namespace App\Http\Requests\Admin\Setting\Business;

use App\Http\Requests\BaseRequest;
use App\Services\New\ShopService;
use Illuminate\Validation\Rule;

class InHouseShopVacationRequest extends BaseRequest
{
    private ?string $storedStartDate = null;

    private bool $isStoredStartDateLoaded = false;

    public function __construct(private readonly ShopService $shopService)
    {
        parent::__construct();
    }

    public function rules(): array
    {
        $isTurningOn = $this->isTurningOn();
        $needsDates = $isTurningOn && $this['vacation_duration_type'] === ShopService::VACATION_CUSTOM;

        return [
            'status' => ['nullable', 'in:1'],
            'modules' => $isTurningOn ? ['required', 'array', 'min:1'] : ['nullable', 'array'],
            'modules.*' => ['string', Rule::in($this->availableModuleKeys())],
            'vacation_duration_type' => [$isTurningOn ? 'required' : 'nullable', Rule::in(ShopService::VACATION_DURATION_TYPES)],
            'vacation_start_date' => [
                $needsDates ? 'required' : 'nullable',
                'date',
                ...($needsDates && $this->isStartDateChanged() ? ['after_or_equal:today'] : []),
            ],
            'vacation_end_date' => [
                $needsDates ? 'required' : 'nullable',
                'date',
                'after_or_equal:vacation_start_date',
                ...($needsDates ? ['after:now'] : []),
            ],
            'vacation_note' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function messages(): array
    {
        return [
            'status.in' => translate('This setting can only be turned on or off'),
            'modules.required' => translate('Please choose at least one business for vacation mode.'),
            'modules.min' => translate('Please choose at least one business for vacation mode.'),
            'modules.*.in' => translate('One of the selected businesses is not available on your shop.'),
            'vacation_duration_type.required' => translate('Please choose how long vacation mode lasts.'),
            'vacation_duration_type.in' => translate('Please choose how long vacation mode lasts.'),
            'vacation_start_date.required' => translate('Please select the date range vacation mode covers.'),
            'vacation_start_date.date' => translate('Please select the date range vacation mode covers.'),
            'vacation_start_date.after_or_equal' => translate('The vacation start date cannot be in the past.'),
            'vacation_end_date.required' => translate('Please select the date range vacation mode covers.'),
            'vacation_end_date.date' => translate('Please select the date range vacation mode covers.'),
            'vacation_end_date.after_or_equal' => translate('The end date cannot be earlier than the start date.'),
            'vacation_end_date.after' => translate('The vacation end time must be in the future.'),
            'vacation_note.max' => translate('The vacation note cannot be longer than 1000 characters.'),
        ];
    }

    /** @return array{vacation_start_date: ?string, vacation_end_date: ?string} */
    public function vacationPeriod(): array
    {
        return $this->shopService->getVacationPeriod(
            durationType: $this['vacation_duration_type'] ?? null,
            isTurningOn: $this->isTurningOn(),
            startDate: $this['vacation_start_date'] ?? null,
            endDate: $this['vacation_end_date'] ?? null,
        );
    }

    /** @return string[] */
    public function availableModuleKeys(): array
    {
        return array_keys(businessModuleManager()->availableInPanel());
    }

    private function isTurningOn(): bool
    {
        return (int) ($this['status'] ?? 0) === 1;
    }

    private function isStartDateChanged(): bool
    {
        $submittedTimestamp = strtotime((string) $this['vacation_start_date']);
        $storedStartDate = $this->storedStartDate();

        return $submittedTimestamp === false
            || $storedStartDate === null
            || $submittedTimestamp !== strtotime($storedStartDate);
    }

    private function storedStartDate(): ?string
    {
        if (! $this->isStoredStartDateLoaded) {
            $shop = $this->shopService->findBy(params: ['author_type' => ShopService::AUTHOR_TYPE_ADMIN]);

            $this->storedStartDate = $shop?->vacation_start_date === null ? null : (string) $shop->vacation_start_date;
            $this->isStoredStartDateLoaded = true;
        }

        return $this->storedStartDate;
    }
}
