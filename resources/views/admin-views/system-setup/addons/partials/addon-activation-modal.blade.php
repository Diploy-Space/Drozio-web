<div class="modal-header border-0 pb-0 d-flex justify-content-end">
    <button type="button" class="btn-close border-0 btn-circle bg-section2 shadow-none"
            data-bs-dismiss="modal" aria-label="Close">
    </button>
</div>
<div class="modal-body px-30 py-0 mb-30">
    <div class="mb-20 text-center">
        <img width="140"
             src="{{ getStorageImages(path: null, type: 'backend-basic',source: $path.'/public/addon.png') }}"
             alt="" class="dark-support"/>
    </div>
    <div class="text-center mb-30">
        <h2 class="mb-2">{{ $addonName }}</h2>
        <p class="mb-0">{{ translate('To activate this addon, please fill in the following information.') }}</p>
    </div>

    <form id="addon-list-activation-form" action="{{ route('admin.settings.system-setup.addon-activation.activate-from-list') }}" method="post"
          class="form-advance-validation non-ajax-form-validate addon-list-activation-form" autocomplete="off" novalidate="novalidate">
        @csrf
        <input type="hidden" name="addon_name" value="{{ strtolower($fullData['module_name']) }}">
        <input type="hidden" name="software_type" value="addon">
        <input type="hidden" name="software_id" value="{{ base64_encode($fullData['software_id']) }}">
        <input type="hidden" name="path" value="{{ $path }}">

        <div class="bg-section rounded p-12 p-sm-20 mb-30">
            <div class="row g-3">
                <div class="col-md-6">
                    <div class="form-group">
                        <label class="form-label text-capitalize" for="addon_person_name">
                            {{ translate('Name') }}
                            <span class="text-danger">*</span>
                        </label>
                        <input type="text" id="addon_person_name" name="name" class="form-control"
                               placeholder="{{ translate('Ex') }}: {{ 'Jone Doe' }}"
                               data-required-msg="{{ translate('Name is field is required') }}" required>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label class="form-label text-capitalize" for="addon_person_email">
                            {{ translate('Email') }}
                            <span class="text-danger">*</span>
                        </label>
                        <input type="email" id="addon_person_email" name="email" class="form-control"
                               placeholder="{{ translate('Ex') }}: {{ 'jone-doe@example.com' }}"
                               data-required-msg="{{ translate('Email is field is required') }}" required>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label class="form-label text-capitalize" for="addon_username">
                            {{ translate('Username') }}
                            <span class="text-danger">*</span>
                            <span class="tooltip-icon"
                                  data-bs-toggle="tooltip"
                                  data-bs-placement="top"
                                  aria-label="{{ translate('Please use the username exactly as it is, without any spaces.') }} {{ translate('Make sure to enter the name correctly.') }}"
                                  data-bs-title="{{ translate('Please use the username exactly as it is, without any spaces.') }} {{ translate('Make sure to enter the name correctly.') }}">
                                <i class="fi fi-sr-info"></i>
                            </span>
                        </label>
                        <input type="text" id="addon_username" name="username" class="form-control"
                               placeholder="{{ translate('Ex') }}: {{ 'jonedoe3050' }}"
                               data-required-msg="{{ translate('Username is field is required') }}" required>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label class="form-label text-capitalize" for="addon_purchase_key">
                            {{ translate('Purchase code') }}
                            <span class="text-danger">*</span>
                            <span class="tooltip-icon"
                                  data-bs-toggle="tooltip"
                                  data-bs-placement="top"
                                  aria-label="{{ translate('Please check your purchase code before proceeding with the update.') }}"
                                  data-bs-title="{{ translate('Please check your purchase code before proceeding with the update.') }}">
                                <i class="fi fi-sr-info"></i>
                            </span>
                        </label>
                        <input type="text" id="addon_purchase_key" name="purchase_key" class="form-control"
                               placeholder="{{ translate('Ex') }}: {{ 'CAWFRWRAAWRCAWRA' }}"
                               data-required-msg="{{ translate('Purchase key field is required') }}" required>
                    </div>
                </div>
            </div>
        </div>

        <div class="d-flex justify-content-center gap-20">
            <button type="button" class="btn btn-secondary flex-grow-1" data-bs-dismiss="modal">
                {{ translate('Cancel') }}
            </button>
            <button type="{{ getDemoModeFormButton(type: 'button') }}"
                    class="btn btn-primary flex-grow-1 addon-list-activation-submit {{ getDemoModeFormButton(type: 'class') }}">
                <span class="spinner-border spinner-border-sm me-2 d--none submit-spinner" role="status" aria-hidden="true"></span>
                <span class="submit-label">{{ translate('Activate') }}</span>
            </button>
        </div>
    </form>
</div>
