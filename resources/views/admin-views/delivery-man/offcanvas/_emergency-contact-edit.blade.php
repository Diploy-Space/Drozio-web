<form action="{{ route('admin.people.delivery-man.emergency-contact.update', ['id' => $contact['id']]) }}" method="post"
      class="text-start form-advance-validation non-ajax-form-validate" novalidate="novalidate">
    @csrf
    <div class="offcanvas offcanvas-end" tabindex="-1" id="emergencyContactEditOffcanvas-{{ $contact['id'] }}"
         aria-labelledby="emergencyContactEditOffcanvasLabel-{{ $contact['id'] }}" style="--bs-offcanvas-width: 500px;">

        <div class="offcanvas-header gap-3 justify-content-between bg-body">
            <h2 class="mb-0 flex-grow-1" id="emergencyContactEditOffcanvasLabel-{{ $contact['id'] }}">
                {{ translate('Update emergency contact') }}
            </h2>
            <button type="button" class="btn btn-circle bg-white text-dark fs-10" style="--size: 1.5rem;"
                    data-bs-dismiss="offcanvas" aria-label="{{ translate('Close') }}">
                <i class="fi fi-rr-cross"></i>
            </button>
        </div>

        <div class="offcanvas-body">
            <div class="p-12 p-sm-20 bg-section rounded d-flex flex-column gap-20">
                <div class="form-group mb-0">
                    <label class="form-label d-flex" for="emergency-contact-edit-name-{{ $contact['id'] }}">
                        {{ translate('Contact name') }} <span class="text-danger">*</span>
                    </label>
                    <input type="text" name="name" class="form-control"
                           id="emergency-contact-edit-name-{{ $contact['id'] }}"
                           value="{{ $contact['name'] }}"
                           placeholder="{{ translate('Contact name') }}"
                           data-required-msg="{{ translate('Contact name field is required') }}" required>
                </div>
                <div class="form-group mb-0">
                    <label class="form-label d-flex" for="emergency-contact-edit-phone-{{ $contact['id'] }}">
                        {{ translate('Phone') }} <span class="text-danger">*</span>
                    </label>
                    <input type="tel" name="phone" class="form-control"
                           id="emergency-contact-edit-phone-{{ $contact['id'] }}"
                           value="{{ $contact['phone'] }}"
                           placeholder="{{ translate('Ex').':'.'017********' }}"
                           data-required-msg="{{ translate('Contact phone field is required') }}" required>
                </div>
            </div>
        </div>

        <div class="offcanvas-footer shadow-popup">
            <div class="d-flex justify-content-center flex-wrap gap-3 bg-white px-3 py-2">
                <button type="submit" class="btn btn-primary flex-grow-1">{{ translate('Update') }}</button>
            </div>
        </div>
    </div>
</form>
