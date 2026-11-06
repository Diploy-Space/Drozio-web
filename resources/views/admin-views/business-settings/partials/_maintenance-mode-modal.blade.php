<div class="modal fade" id="maintenance-mode-modal" tabindex="-1" aria-labelledby="maintenanceModeModalLabel"
     aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <form method="post" action="{{ route('admin.settings.business-settings.maintenance-mode') }}"
              class="modal-content" id="maintenance-mode-form">
            @csrf
            <div class="modal-header bg-body align-items-center gap-3 p-3 p-sm-4">
                <h2 class="mb-0" id="maintenanceModeModalLabel">{{ translate('System Maintenance') }}</h2>
                <button type="button" class="btn-close maintenance-cancel-button" data-bs-dismiss="modal"
                        aria-label="{{ translate('Close') }}"></button>
            </div>
            <div class="modal-body p-3 p-sm-4">
                <div class="d-flex flex-column gap-3 gap-sm-20">
                    <div class="p-12 p-sm-20 bg-section rounded">
                        <div class="mb-3">
                            <h4>{{ translate('Maintenance Mode') }}</h4>
                            <p class="fs-12 mb-0">{{ translate('Turning on maintenance mode controls your entire system & functions') }}</p>
                        </div>
                        <label class="d-flex justify-content-between align-items-center gap-3 border rounded px-3 py-2 user-select-none">
                            <span class="fw-medium text-dark">{{ translate('Status') }}</span>
                            <span class="switcher">
                                <input type="checkbox" class="switcher_input" name="maintenance_mode"
                                       id="maintenance-mode-checkbox" value="1"
                                       {{ $businessSetting['maintenance_mode'] ? 'checked' : '' }}>
                                <span class="switcher_control"></span>
                            </span>
                        </label>
                    </div>

                    <div class="p-12 p-sm-20 bg-section rounded">
                        <div class="form-group m-0">
                            <label class="form-label">
                                {{ translate('Select System') }}
                                <span class="text-danger">*</span>
                                <span class="tooltip-icon" data-bs-toggle="tooltip" data-bs-placement="top"
                                      aria-label="{{ translate('Select the systems you want to temporarily deactivate for maintenance') }}"
                                      data-bs-title="{{ translate('Select the systems you want to temporarily deactivate for maintenance') }}">
                                    <i class="fi fi-sr-info"></i>
                                </span>
                            </label>
                            <div class="min-h-40 d-flex align-items-center flex-wrap gap-3 gap-sm-4 border rounded px-3 py-2 user-select-none">
                                <div class="d-flex align-items-center gap-5px p-0 pe-1">
                                    <input class="form-check-input checkbox--input system-checkbox mt-0" type="checkbox"
                                           name="all_system" id="allSystemSelection"
                                           {{ $maintenanceAllSystemsSelected ? 'checked' : '' }}>
                                    <label class="form-check-label cursor-pointer fw-medium" for="allSystemSelection">
                                        {{ translate('All System') }}
                                    </label>
                                </div>
                                @foreach ($maintenanceSystems as $maintenanceSystem)
                                    <div class="d-flex align-items-center gap-5px p-0 pe-1">
                                        <input class="form-check-input checkbox--input system-checkbox mt-0"
                                               type="checkbox" value="1"
                                               name="{{ $maintenanceSystem['key'] }}"
                                               id="maintenance-system-{{ $maintenanceSystem['key'] }}"
                                               {{ $maintenanceSystem['is_selected'] ? 'checked' : '' }}>
                                        <label class="form-check-label cursor-pointer"
                                               for="maintenance-system-{{ $maintenanceSystem['key'] }}">
                                            {{ $maintenanceSystem['label'] }}
                                        </label>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>

                    <div class="p-12 p-sm-20 bg-section rounded">
                        <div class="mb-3">
                            <h4>{{ translate('Maintenance Date and Time') }}</h4>
                            <p class="fs-12 mb-0">{{ translate('Choose the maintenance mode duration for your selected system.') }}</p>
                        </div>
                        <div class="min-h-40 d-flex align-items-center flex-wrap gap-3 gap-sm-4 border rounded px-3 py-2 mb-3 user-select-none">
                            @foreach ($maintenanceDurations as $duration)
                                <div class="d-flex align-items-center gap-5px p-0 pe-1">
                                    <input class="form-check-input radio--input mt-0" type="radio"
                                           name="maintenance_duration" value="{{ $duration['key'] }}"
                                           id="maintenance-duration-{{ $duration['key'] }}"
                                           {{ $duration['is_selected'] ? 'checked' : '' }}>
                                    <label class="form-check-label cursor-pointer"
                                           for="maintenance-duration-{{ $duration['key'] }}">
                                        {{ $duration['label'] }}
                                    </label>
                                </div>
                            @endforeach
                        </div>
                        <div class="row g-3 start-and-end-date">
                            <div class="col-sm-6">
                                <div class="form-group m-0">
                                    <label class="form-label" for="startDate">{{ translate('Start Date') }}</label>
                                    <input type="datetime-local" class="form-control" name="start_date" id="startDate"
                                           value="{{ old('start_date', $selectedMaintenanceDuration['start_date'] ?? '') }}"
                                           required>
                                </div>
                            </div>
                            <div class="col-sm-6">
                                <div class="form-group m-0">
                                    <label class="form-label" for="endDate">{{ translate('End Date') }}</label>
                                    <input type="datetime-local" class="form-control" name="end_date" id="endDate"
                                           value="{{ old('end_date', $selectedMaintenanceDuration['end_date'] ?? '') }}"
                                           required>
                                </div>
                            </div>
                        </div>
                        <small id="dateError" class="form-text text-danger d-none">
                            {{ translate('Start date cannot be greater than end date.') }}
                        </small>
                    </div>

                    <div id="advanceFeatureSection" class="d-none">
                        <div class="p-12 p-sm-20 bg-section rounded">
                            <div class="mb-3">
                                <h4>{{ translate('Maintenance Message') }}</h4>
                                <p class="fs-12 mb-0">{{ translate('Choose the message shown while maintenance mode is active.') }}</p>
                            </div>
                            <div class="form-group">
                                <label class="form-label">{{ translate('Show Contact Information') }}</label>
                                <div class="min-h-40 d-flex align-items-center flex-wrap gap-3 gap-sm-4 border rounded px-3 py-2 user-select-none">
                                    <div class="d-flex align-items-center gap-5px p-0 pe-1">
                                        <input class="form-check-input checkbox--input mt-0" type="checkbox"
                                               name="business_number" value="1" id="businessNumber"
                                               {{ ($selectedMaintenanceMessage['business_number'] ?? 0) == 1 ? 'checked' : '' }}>
                                        <label class="form-check-label cursor-pointer" for="businessNumber">
                                            {{ translate('Business Number') }}
                                        </label>
                                    </div>
                                    <div class="d-flex align-items-center gap-5px p-0 pe-1">
                                        <input class="form-check-input checkbox--input mt-0" type="checkbox"
                                               name="business_email" value="1" id="businessEmail"
                                               {{ ($selectedMaintenanceMessage['business_email'] ?? 0) == 1 ? 'checked' : '' }}>
                                        <label class="form-check-label cursor-pointer" for="businessEmail">
                                            {{ translate('Business Email') }}
                                        </label>
                                    </div>
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="form-label" for="maintenanceMessage">
                                    {{ translate('Maintenance Message') }}
                                    <span class="tooltip-icon" data-bs-toggle="tooltip" data-bs-placement="top"
                                          aria-label="{{ translate('The maximum character limit is :limit', ['limit' => $maintenanceMessageLimit]) }}"
                                          data-bs-title="{{ translate('The maximum character limit is :limit', ['limit' => $maintenanceMessageLimit]) }}">
                                        <i class="fi fi-sr-info"></i>
                                    </span>
                                </label>
                                <input type="text" class="form-control" name="maintenance_message"
                                       id="maintenanceMessage" maxlength="{{ $maintenanceMessageLimit }}"
                                       placeholder="{{ translate('We are working on something special!') }}"
                                       value="{{ $selectedMaintenanceMessage['maintenance_message'] ?? '' }}">
                            </div>
                            <div class="form-group m-0">
                                <label class="form-label" for="messageBody">
                                    {{ translate('Message Body') }}
                                    <span class="tooltip-icon" data-bs-toggle="tooltip" data-bs-placement="top"
                                          aria-label="{{ translate('The maximum character limit is :limit', ['limit' => $maintenanceMessageBodyLimit]) }}"
                                          data-bs-title="{{ translate('The maximum character limit is :limit', ['limit' => $maintenanceMessageBodyLimit]) }}">
                                        <i class="fi fi-sr-info"></i>
                                    </span>
                                </label>
                                <textarea class="form-control" name="message_body" id="messageBody" rows="4"
                                          maxlength="{{ $maintenanceMessageBodyLimit }}"
                                          placeholder="{{ translate('Sorry for the inconvenience!') }} {{ translate('We are currently undergoing scheduled maintenance to improve our services.') }} {{ translate('We will be back shortly.') }} {{ translate('Thank you for your patience.') }}">{{ $selectedMaintenanceMessage['message_body'] ?? '' }}</textarea>
                            </div>
                        </div>
                        <div class="d-flex justify-content-center mt-3">
                            <a href="#" id="seeLessToggle"
                               class="fw-semibold text-decoration-underline maintenance-advance-feature-button">
                                {{ translate('See Less') }}
                            </a>
                        </div>
                    </div>

                    <div id="advanceFeatureButtonDiv" class="d-flex justify-content-center">
                        <a href="#" id="advanceFeatureToggle"
                           class="fw-semibold text-decoration-underline maintenance-advance-feature-button">
                            {{ translate('Advance Feature') }}
                        </a>
                    </div>
                </div>
            </div>
            <div class="modal-footer shadow-lg p-3 p-sm-4">
                <div class="d-flex justify-content-center flex-wrap gap-3 w-100">
                    <button type="button" class="btn btn-secondary px-3 px-sm-4 flex-grow-1 maintenance-cancel-button"
                            data-bs-dismiss="modal" id="cancelButton">
                        {{ translate('Cancel') }}
                    </button>
                    <button type="{{ config('app.mode') != 'demo' ? 'submit' : 'button' }}"
                            class="btn btn-primary px-3 px-sm-4 flex-grow-1 {{ config('app.mode') != 'demo' ? '' : 'call-demo-alert' }}">
                        <i class="fi fi-sr-disk"></i>
                        {{ translate('Save') }}
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>
