<form action="{{ route('admin.settings.system-setup.push-notification.update') }}"
      class="text-start" method="post" enctype="multipart/form-data" id="push-notifications-form">
    @csrf
    <input type="hidden" name="module" value="{{ $businessModule }}">
    <input type="hidden" name="user_type" value="{{ $userType }}">
    <div class="row g-4 mb-4">
        @foreach ($messages as $value)
            @php
                $label = $labels[$value['key']] ?? ['token' => $value['key'], 'suffix' => null];
                $fieldId = $userType . '_' . $value['key'];
            @endphp
            <div class="col-md-6 col-lg-6">
                <div class="form-group">
                    <div class="d-flex align-items-center justify-content-between mb-3 flex-wrap gap-10">
                        <label for="{{ $fieldId }}" class="form-label switcher_content mb-0">
                            {{ translate($label['token']) }}@if(!empty($label['suffix'])) ({{ translate($label['suffix']) }})@endif
                        </label>
                        <label class="switcher" for="{{ $fieldId }}">
                            <input
                                class="switcher_input custom-modal-plugin"
                                type="checkbox" value="1" name="status{{ $value['id'] }}"
                                id="{{ $fieldId }}"
                                {{ $value['status'] == 1 ? 'checked' : '' }}
                                data-modal-type="input-change"
                                data-modal-form="#push-notifications-form"
                                data-on-image="{{ dynamicAsset(path: 'public/assets/new/back-end/img/modal/notification-on.png') }}"
                                data-off-image="{{ dynamicAsset(path: 'public/assets/new/back-end/img/modal/notification-off.png') }}"
                                data-on-title="{{ translate('Want to Turn ON :item', ['item' => translate('Push Notification')]) }}"
                                data-off-title="{{ translate('Want to Turn OFF :item', ['item' => translate('Push Notification')]) }}"
                                data-on-message="<p>{{ translate('If enabled customers will receive notifications on their devices') }}</p>"
                                data-off-message="<p>{{ translate('If disabled customers will not receive notifications on their devices') }}</p>"
                                data-on-button-text="{{ translate('Turn On') }}"
                                data-off-button-text="{{ translate('Turn Off') }}">
                            <span class="switcher_control"></span>
                        </label>
                    </div>
                    @foreach (json_decode($language) as $lang)
                        @php
                            $translate = [];
                            if (count($value['translations'])) {
                                foreach ($value['translations'] as $t) {
                                    if ($t->locale == $lang && $t->key == $value['key']) {
                                        $translate[$lang][$value['key']] = $t->value;
                                    }
                                }
                            }
                        @endphp
                        <input type="hidden" name="lang{{ $value['id'] }}[]" value="{{ $lang }}">
                        <textarea name="message{{ $value['id'] }}[]" rows="3"
                                  class="form-control text-area-max-min {{ $lang != $default_lang ? 'd-none' : '' }} lang-form {{ $lang }}-form">{{ $translate[$lang][$value['key']] ?? $value['message'] }}</textarea>
                    @endforeach
                </div>
            </div>
        @endforeach
    </div>
</form>
