<div class="d-flex flex-column align-items-center text-center gap-2 mb-2">
    <div class="d-flex flex-column justify-content-center align-items-center mb-3 position-relative">
        @if($data['type'] == 'firebase')
            <img src="{{ dynamicAsset(path: 'public/assets/back-end/img/firebase-settings.png') }}"
                 class="status-icon" alt="" width="80"/>
        @else
            <img src="{{ dynamicAsset(path: 'public/assets/back-end/img/google-logo.png') }}"
                 class="status-icon" alt="" width="80"/>
        @endif
    </div>
    <h5 class="modal-title">
        {{ translate('Set up firebase configuration first') }}
    </h5>
    <div class="text-center">
        {{ translate('It looks like your :type login configuration is not set up yet', ['type' => $data['type']]) }}
        {{ translate('To enable :type login, set up the :type configuration first', ['type' => $data['type']]) }}
    </div>
    <div class="text-center py-3">
        <a class="text-decoration-underline fw-bold"
           href="{{ route('admin.settings.third-party.firebase-configuration.setup') }}" target="_blank">
            {{ translate('Go to Firebase configuration') }}
        </a>
    </div>
</div>
<div class="d-flex justify-content-center gap-3 mt-3">
    <button type="button" class="btn btn-primary min-w-120" data-bs-dismiss="modal">
        {{ translate('Okay') }}
    </button>
    <button type="button" class="btn btn-secondary min-w-120" data-bs-dismiss="modal">
        {{ translate('Cancel') }}
    </button>
</div>
