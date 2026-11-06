<?php

namespace App\Http\Requests\Vendor\Shop;

use App\Http\Requests\BaseRequest;
use App\Services\New\ShopService;
use Illuminate\Validation\Rule;

class VacationUpdateRequest extends BaseRequest
{
    public const NOTE_MAX_LENGTH = 1000;

    private ?string $storedStartDate = null;

    private bool $isStoredStartDateLoaded = false;

    public function __construct(private readonly ShopService $shopService)
    {
        parent::__construct();
    }

    protected function prepareForValidation(): void
    {
        if ($this->isApiRequest() && ! $this->has('modules')) {
            $this->merge(['modules' => $this->availableModuleKeys()]);
        }
    }

    public function rules(): array
    {
        $isTurningOn = $this->isTurningOn();
        $needsDates = $isTurningOn && $this['vacation_duration_type'] === ShopService::VACATION_CUSTOM;

        return [
            'vacation_status' => ['nullable', 'boolean'],
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
            'vacation_note' => ['nullable', 'string', 'max:'.self::NOTE_MAX_LENGTH],
        ];
    }

    public function messages(): array
    {
        return [
            'vacation_status.boolean' => translate('This setting can only be turned on or off'),
            'modules.required' => translate('Please choose at least one business for vacation mode.'),
            'modules.min' => translate('Please choose at least one business for vacation mode.'),
            'modules.*.in' => translate('One of the selected businesses is not available on your shop.'),
            'vacation_duration_type.required' => translate(':field is required', ['field' => translate('Vacation duration type')]),
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

    /** @return array<string, mixed> */
    public function vacationColumns(): array
    {
        $durationType = $this['vacation_duration_type'] ?? null;
        $moduleColumns = $this->selectedModuleKeys() === null ? [] : ['vacation_modules' => $this->selectedModuleKeys()];

        if (! $this->isTurningOn() && ! $this->filled('vacation_duration_type')) {
            return ['vacation_status' => 0, ...$moduleColumns];
        }

        return [
            'vacation_status' => $this->isTurningOn() ? 1 : 0,
            'vacation_duration_type' => $durationType,
            ...$this->shopService->getVacationPeriod(
                durationType: $durationType,
                isTurningOn: $this->isTurningOn(),
                startDate: $this['vacation_start_date'] ?? null,
                endDate: $this['vacation_end_date'] ?? null,
            ),
            'vacation_note' => $this['vacation_note'] ?? null,
            ...$moduleColumns,
        ];
    }

    /** @return string[]|null */
    public function selectedModuleKeys(): ?array
    {
        if ($this->has('modules')) {
            return array_values($this['modules'] ?? []);
        }

        return $this->isApiRequest() ? null : [];
    }

    private function isTurningOn(): bool
    {
        return filter_var($this['vacation_status'] ?? false, FILTER_VALIDATE_BOOLEAN);
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
            $sellerId = $this->isApiRequest() ? $this->attributes->get('seller')?->id : auth('seller')->id();
            $shop = $sellerId === null ? null : $this->shopService->findBy(params: [
                'seller_id' => $sellerId,
                'author_type' => ShopService::AUTHOR_TYPE_VENDOR,
            ]);

            $this->storedStartDate = $shop?->vacation_start_date === null ? null : (string) $shop->vacation_start_date;
            $this->isStoredStartDateLoaded = true;
        }

        return $this->storedStartDate;
    }

    /** @return string[] */
    public function availableModuleKeys(): array
    {
        return array_keys(businessModuleManager()->availableInPanel());
    }
}
