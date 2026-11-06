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

    <form action="{{ route('admin.settings.system-setup.addon.activation') }}" method="post" autocomplete="off">
        @csrf
        <div class="bg-section rounded p-12 p-sm-20 mb-30">
            <div class="form-group mb-20">
                <label class="form-label" for="username">
                    {{ translate('Codecanyon username') }}
                    <span class="text-danger">*</span>
                    <span class="tooltip-icon" data-bs-toggle="tooltip" data-bs-placement="top" aria-label="Enter your username" data-bs-title="Enter your username">
                        <i class="fi fi-sr-info"></i>
                    </span>
                </label>
                <input name="username" id="username" class="form-control"
                       placeholder="{{ translate('Ex') }}: Riad uddin" required />
            </div>
            <div class="form-group mb-20">
                <label class="form-label" for="purchase_code">
                    {{ translate('Purchase code') }}
                    <span class="text-danger">*</span>
                    <span class="tooltip-icon" data-bs-toggle="tooltip" data-bs-placement="top" aria-label="Enter your purchase code" data-bs-title="Enter your purchase code">
                        <i class="fi fi-sr-info"></i>
                    </span>
                </label>
                <input name="purchase_code" id="purchase_code" class="form-control"
                       placeholder="{{ translate('Ex').' : '.'987652' }}" required />
            </div>
        </div>
        <div class="d-flex justify-content-center gap-20">
            <input type="text" name="path" class="form-control" value="{{ $path }}" hidden>
            <button type="button" class="btn btn-secondary flex-grow-1" data-bs-dismiss="modal">{{ translate('Cancel') }}</button>
            <button type="submit" class="btn btn-primary flex-grow-1">{{ translate('Activate') }}</button>
        </div>
    </form>
</div>

