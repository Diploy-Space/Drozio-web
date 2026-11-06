<div class="d-flex flex-column gap-20">
    <div class="p-12 p-sm-20 bg-section rounded">
        <div class="form-group mb-20">
            <label class="form-label" for="bonus-title-{{ $fieldId }}">
                {{ translate('Bonus title') }} <span class="text-danger">*</span>
            </label>
            <input type="text" name="title" class="form-control" id="bonus-title-{{ $fieldId }}"
                   value="{{ $bonus?->title }}"
                   data-required-msg="{{ translate('Bonus title field is required') }}"
                   placeholder="{{ translate('Ex') }}: EID Dhamaka" required>
        </div>
        <div class="form-group mb-0">
            <label class="form-label" for="bonus-description-{{ $fieldId }}">{{ translate('Short Description') }}</label>
            <input type="text" name="description" class="form-control" id="bonus-description-{{ $fieldId }}"
                   value="{{ $bonus?->description }}"
                   placeholder="{{ translate('Ex') }}: EID Dhamaka">
        </div>
    </div>

    <div class="p-12 p-sm-20 bg-section rounded">
        <div class="form-group mb-20">
            <label class="form-label" for="bonus-type-{{ $fieldId }}">
                {{ translate('Bonus type') }} <span class="text-danger">*</span>
            </label>
            <div class="select-wrapper">
                <select name="bonus_type" id="bonus-type-{{ $fieldId }}" class="form-select bonus-type-select"
                        data-required-msg="{{ translate('Bonus type field is required') }}" required>
                    <option value="percentage" {{ $bonus?->bonus_type === 'percentage' ? 'selected' : '' }}>
                        {{ translate('Percentage').'(%)' }}
                    </option>
                    <option value="fixed" {{ $bonus?->bonus_type === 'fixed' ? 'selected' : '' }}>
                        {{ translate('Fixed amount') }}
                    </option>
                </select>
            </div>
        </div>
        <div class="form-group mb-20">
            <label class="form-label" for="bonus-amount-{{ $fieldId }}">
                {{ translate('Bonus amount') }} (<span class="bonus-amount-unit">%</span>)
                <span class="text-danger">*</span>
            </label>
            <input type="number" name="bonus_amount" min="0" class="form-control" id="bonus-amount-{{ $fieldId }}"
                   value="{{ $bonus ? ($bonus->bonus_type === 'fixed' ? usdToDefaultCurrency(amount: $bonus->bonus_amount) : $bonus->bonus_amount) : 0 }}"
                   data-required-msg="{{ translate('Bonus amount field is required') }}"
                   placeholder="{{ translate('Ex').':'.'5' }}" required>
        </div>
        <div class="form-group mb-20">
            <label class="form-label" for="min-add-money-amount-{{ $fieldId }}">
                {{ translate('Minimum add amount') }}
                ({{ getCurrencySymbol(currencyCode: getCurrencyCode(type: 'default')) }})
                <span class="text-danger">*</span>
            </label>
            <input type="number" name="min_add_money_amount" min="0" class="form-control"
                   id="min-add-money-amount-{{ $fieldId }}"
                   value="{{ $bonus ? usdToDefaultCurrency(amount: $bonus->min_add_money_amount) : 0 }}"
                   data-required-msg="{{ translate('Min add money amount field is required') }}"
                   placeholder="{{ translate('Ex').':'.'100' }}" required>
        </div>
        <div class="form-group mb-0 max-bonus-amount-area">
            <label class="form-label" for="max-bonus-amount-{{ $fieldId }}">
                {{ translate('Maximum bonus') }}
                ({{ getCurrencySymbol(currencyCode: getCurrencyCode(type: 'default')) }})
            </label>
            <input type="number" name="max_bonus_amount" min="0" class="form-control"
                   id="max-bonus-amount-{{ $fieldId }}"
                   value="{{ $bonus ? usdToDefaultCurrency(amount: $bonus->max_bonus_amount) : 0 }}"
                   placeholder="{{ translate('Ex').':'.'5000' }}">
        </div>
    </div>

    <div class="p-12 p-sm-20 bg-section rounded">
        <div class="form-group mb-20">
            <label class="form-label" for="start-date-time-{{ $fieldId }}">
                {{ translate('Start Date') }} <span class="text-danger">*</span>
            </label>
            <input type="date" name="start_date_time" id="start-date-time-{{ $fieldId }}"
                   class="form-control bonus-start-date"
                   value="{{ $bonus ? date('Y-m-d', strtotime($bonus->start_date_time)) : '' }}"
                   data-required-msg="{{ translate('Start date field is required') }}" required>
        </div>
        <div class="form-group mb-0">
            <label class="form-label" for="end-date-time-{{ $fieldId }}">
                {{ translate('End Date') }} <span class="text-danger">*</span>
            </label>
            <input type="date" name="end_date_time" id="end-date-time-{{ $fieldId }}"
                   class="form-control bonus-end-date"
                   value="{{ $bonus ? date('Y-m-d', strtotime($bonus->end_date_time)) : '' }}"
                   data-required-msg="{{ translate('End date field is required') }}" required>
        </div>
    </div>
</div>
