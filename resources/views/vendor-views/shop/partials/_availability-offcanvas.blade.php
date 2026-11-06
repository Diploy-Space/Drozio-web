<div class="offcanvas-sidebar offcanvas-store-availability">
    <div class="offcanvas-overlay" data-dismiss="offcanvas"></div>

    <div class="offcanvas-content bg-white shadow d-flex flex-column">
        <div class="offcanvas-header bg-light d-flex justify-content-between align-items-center p-3">
            <h3 class="text-capitalize m-0">{{ translate('Availability') }}</h3>
            <button type="button" class="close" data-dismiss="offcanvas" aria-label="{{ translate('Close') }}">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>

        <form action="{{ route('vendor.shop.availability') }}" method="post" class="d-flex flex-column flex-grow-1"
            id="store-availability-form">
            @csrf
            <div class="offcanvas-body p-3 overflow-auto flex-grow-1">
                @foreach ($availabilityModules as $availabilityModule)
                    <div class="bg-light p-3 rounded mb-3" data-availability-row>
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
