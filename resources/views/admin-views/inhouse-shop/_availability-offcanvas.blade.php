<form action="{{ route('admin.settings.business-settings.inhouse-shop-availability') }}" method="post"
      id="store-availability-form">
    @csrf
    <div class="offcanvas offcanvas-end w-sm-500" tabindex="-1" id="offcanvasStoreAvailability"
        aria-labelledby="offcanvasStoreAvailabilityLabel">
        <div class="offcanvas-header bg-body">
            <h2 class="mb-0" id="offcanvasStoreAvailabilityLabel">{{ translate('Availability') }}</h2>
            <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="{{ translate('Close') }}"></button>
        </div>
        <div class="offcanvas-body">
            <div class="d-flex flex-column gap-3">
                @foreach ($availabilityModules as $availabilityModule)
                    <div class="p-12 p-sm-20 bg-section rounded" data-availability-row>
                        <div class="d-flex gap-3 justify-content-between align-items-start">
                            <div>
                                <h4 class="mb-2">{{ $availabilityModule['label'] }}</h4>
                                <p class="fs-12 mb-0" data-availability-description
                                   data-on-text="{{ $availabilityModule['description_on'] }}"
                                   data-off-text="{{ $availabilityModule['description_off'] }}">
                                    {{ $availabilityModule['is_open'] ? $availabilityModule['description_on'] : $availabilityModule['description_off'] }}
                                </p>
                            </div>
                            <label class="switcher flex-shrink-0" for="availability-{{ $availabilityModule['key'] }}">
                                <input class="switcher_input" type="checkbox" name="status[]"
                                    value="{{ $availabilityModule['key'] }}"
                                    id="availability-{{ $availabilityModule['key'] }}"
                                    data-availability-input
                                    {{ $availabilityModule['is_open'] ? 'checked' : '' }}>
                                <span class="switcher_control"></span>
                            </label>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
        <div class="offcanvas-footer shadow-lg">
            <div class="d-flex justify-content-center flex-wrap gap-3 bg-white px-3 py-2">
                <button type="reset" class="btn btn-secondary px-3 px-sm-4 flex-grow-1">{{ translate('Reset') }}</button>
                <button type="{{ getDemoModeFormButton(type: 'button') }}" class="btn btn-primary px-3 px-sm-4 flex-grow-1 {{ getDemoModeFormButton(type: 'class') }}">
                    <i class="fi fi-sr-disk"></i>
                    {{ translate('Save') }}
                </button>
            </div>
        </div>
    </div>
</form>
