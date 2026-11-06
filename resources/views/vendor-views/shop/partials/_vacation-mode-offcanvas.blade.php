<div class="offcanvas-sidebar offcanvas-vacation-mode">
    <div class="offcanvas-overlay" data-dismiss="offcanvas"></div>

    <div class="offcanvas-content bg-white shadow d-flex flex-column">
        <div class="offcanvas-header bg-light d-flex justify-content-between align-items-center p-3">
            <h3 class="text-capitalize m-0">{{ translate('Vacation Mode Setup') }}</h3>
            <button type="button" class="close" data-dismiss="offcanvas" aria-label="{{ translate('Close') }}">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>

        <form action="{{ route('vendor.shop.update-vacation') }}" method="post" class="d-flex flex-column flex-grow-1"
            id="vendor-vacation-offcanvas-form">
            @csrf
            <div class="offcanvas-body p-3 overflow-auto flex-grow-1">
                <div class="bg-light p-3 rounded mb-3">
                    <h4>{{ translate('Vacation Mode') }}</h4>
                    <p class="fs-12">
                        {{ translate('If you turn on your shop will go to vacation mode.') }}
                        {{ translate("Customer can view your shop but can't order or bid") }}
                    </p>

                    <div class="border rounded bg-white p-3 d-flex justify-content-between gap-3 mb-1 user-select-none">
                        <h5 class="mb-0 d-flex gap-1 c1">
                            {{ translate('Status') }}
                        </h5>
                        <label class="switcher">
                            <input type="checkbox" name="vacation_status" value="1" class="switcher_input"
                                {{ $shop['vacation_status'] == 1 ? 'checked' : '' }}>
                            <span class="switcher_control"></span>
                        </label>
                    </div>
                </div>

                <div class="bg-light p-3 rounded mb-3">
                    <div class="form-group m-0">
                        <label class="form-label">
                            {{ translate('Select Module') }}
                            <span class="tooltip-icon" data-toggle="tooltip" data-placement="top"
                                aria-label="{{ translate('The businesses your shop stops taking orders for while on vacation.') }}"
                                data-title="{{ translate('The businesses your shop stops taking orders for while on vacation.') }}">
                                <i class="fi fi-sr-info"></i>
                            </span>
                            <span class="text-danger">*</span>
                        </label>
                        <div
                            class="min-h-40 d-flex align-items-center flex-wrap gap-3 gap-sm-4 border rounded px-3 py-1 bg-white user-select-none">
                            @foreach ($vacationModules as $vacationModule)
                                <div class="d-flex align-items-center gap-5px">
                                    <input class="form-check-input checkbox--input mt-0 ml-0 mr-0 position-static"
                                        type="checkbox" name="modules[]" value="{{ $vacationModule['key'] }}"
                                        id="vacation-module-{{ $vacationModule['key'] }}"
                                        {{ $vacationModule['is_selected'] ? 'checked' : '' }}>
                                    <label class="form-check-label cursor-pointer"
                                        for="vacation-module-{{ $vacationModule['key'] }}">
                                        {{ $vacationModule['label'] }}
                                    </label>
                                </div>
                            @endforeach
                        </div>
                        <small id="vacationModuleError" class="form-text text-danger d-none">
                            {{ translate('Please choose at least one business for vacation mode.') }}
                        </small>
                    </div>
                </div>

                <div class="bg-light p-3 rounded mb-3">
                    <div class="form-group m-0">
                        <label class="form-label">
                            {{ translate('Vacation Mode Duration') }}
                            <span class="tooltip-icon" data-toggle="tooltip" data-placement="top"
                                aria-label="{{ translate('Dates you will be unavailable to fulfil orders.') }}"
                                data-title="{{ translate('Dates you will be unavailable to fulfil orders.') }}">
                                <i class="fi fi-sr-info"></i>
                            </span>
                            <span class="text-danger">*</span>
                        </label>
                        <div
                            class="min-h-40 d-flex align-items-center flex-wrap gap-3 gap-sm-4 border rounded mb-2 px-3 py-1 bg-white user-select-none">
                            <div class="d-flex align-items-center gap-5px">
                                <input class="form-check-input radio--input mt-0 ml-0 mr-0 position-static" type="radio"
                                    name="vacation_duration_type" id="duration_type1" value="one_day"
                                    {{ isset($shop['vacation_duration_type']) && $shop['vacation_duration_type'] == 'one_day' ? 'checked' : '' }}>
                                <label class="form-check-label cursor-pointer" for="duration_type1">
                                    {{ translate('24 hours') }}
                                </label>
                            </div>
                            <div class="d-flex align-items-center gap-5px">
                                <input class="form-check-input radio--input mt-0 ml-0 mr-0 position-static" type="radio"
                                    name="vacation_duration_type" id="duration_type2" value="until_change"
                                    {{ isset($shop['vacation_duration_type']) && $shop['vacation_duration_type'] == 'until_change' ? 'checked' : '' }}>
                                <label class="form-check-label cursor-pointer" for="duration_type2">
                                    {{ translate('Until i Change') }}
                                </label>
                            </div>
                            <div class="d-flex align-items-center gap-5px">
                                <input class="form-check-input radio--input mt-0 ml-0 mr-0 position-static" type="radio"
                                    name="vacation_duration_type" id="duration_type3" value="custom"
                                    {{ !isset($shop['vacation_duration_type']) || $shop['vacation_duration_type'] == 'custom' ? 'checked' : '' }}>
                                <label class="form-check-label cursor-pointer" for="duration_type3">
                                    {{ translate('Custom') }}
                                </label>
                            </div>
                        </div>
                    </div>

                    <div class="form-group m-0 mt-3 custom-time-range-container">
                        <label class="form-label mb-1" for="vacation_date_range">
                            {{ translate('Select Date Range') }}
                            <span class="text-danger vacation-time-required">*</span>
                        </label>
                        <div class="position-relative">
                            <span class="fi fi-sr-calendar icon-absolute-on-right"></span>
                            <input type="text" class="form-control cursor-pointer" id="vacation_date_range"
                                placeholder="{{ translate('Select date range') }}" autocomplete="off">
                        </div>
                        <input type="hidden" name="vacation_start_date" id="custom_time_range_start"
                            value="{{ old('vacation_start_date', $shop['vacation_start_date'] ?? '') }}">
                        <input type="hidden" name="vacation_end_date" id="custom_time_range_end"
                            value="{{ old('vacation_end_date', $shop['vacation_end_date'] ?? '') }}">
                        <small id="dateError" class="form-text text-danger d-none">
                            {{ translate('Please select the date range vacation mode covers.') }}
                        </small>
                    </div>
                </div>

                <div class="bg-light p-3 rounded mb-3">
                    <div class="form-group m-0">
                        <label class="text-dark text-capitalize" for="vacation_note">
                            {{ translate('Vacation Note') }}
                            <i class="fi fi-sr-info cursor-pointer text-muted" data-toggle="tooltip"
                                title="{{ translate('Customers will see this note while your shop is on vacation.') }}"></i>
                        </label>
                        <textarea class="form-control js-character-counter" rows="4" name="vacation_note" id="vacation_note"
                            maxlength="{{ \App\Http\Requests\Vendor\Shop\VacationUpdateRequest::NOTE_MAX_LENGTH }}"
                            placeholder="{{ translate('Type about the description') }}">{{ old('vacation_note', $shop->vacation_note) }}</textarea>
                        <div class="d-flex justify-content-end">
                            <span class="text-body-light fs-12 js-character-counter-text">0/{{ \App\Http\Requests\Vendor\Shop\VacationUpdateRequest::NOTE_MAX_LENGTH }}</span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="offcanvas-footer offcanvas-footer-sticky p-3 border-top bg-white d-flex gap-3">
                <button type="reset" class="btn btn-secondary w-100">{{ translate('Reset') }}</button>
                <button type="submit" class="btn btn--primary w-100">
                    <i class="fi fi-sr-disk"></i>
                    {{ translate('Save') }}
                </button>
            </div>
        </form>
    </div>
</div>
