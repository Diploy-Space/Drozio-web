<form action="{{ route('admin.settings.courier.config.update') }}" method="post"
      id="courier-{{ $provider['id'] }}-form" autocomplete="off"
      class="form-advance-validation non-ajax-form-validate" novalidate>
    @csrf
    @method('PUT')
    <input type="hidden" name="provider" value="{{ $provider['id'] }}">
    <input type="hidden" name="business_module" value="{{ $activeBusinessModule }}">
    <input type="hidden" name="is_enabled" value="0">

    <div class="offcanvas offcanvas-end courier-setup-offcanvas" tabindex="-1" id="courier-offcanvas-{{ $provider['id'] }}"
         aria-labelledby="courier-offcanvas-{{ $provider['id'] }}-label">
        <div class="offcanvas-header bg-body">
            <h3 class="mb-0 text-capitalize">{{ translate('Setup') }} - {{ $provider['label'] }}</h3>
            <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
        </div>

        <div class="offcanvas-body">
            <div class="p-12 p-sm-20 bg-section rounded mb-3 mb-sm-20">
                <h4 class="text-capitalize">{{ $provider['label'] }}</h4>
                <p class="fs-12">
                    {{ $provider['label'] }}
                    {{ translate('Turning this off stops shipping. Other delivery partners are unaffected.') }}.
                </p>

                <div class="border rounded px-3 py-2 d-flex justify-content-between align-items-center bg-white">
                    <h4 class="text-capitalize mb-0">{{ translate('Status') }}</h4>
                    <label class="switcher" for="courier-{{ $provider['id'] }}-canvas-status">
                        <input class="switcher_input" type="checkbox" value="1" name="is_enabled"
                               id="courier-{{ $provider['id'] }}-canvas-status"
                               {{ $provider['is_enabled'] ? 'checked' : '' }}>
                        <span class="switcher_control"></span>
                    </label>
                </div>
            </div>

            <div class="p-12 p-sm-20 bg-section rounded mb-3 mb-sm-20">
                <h4 class="mb-3">{{ translate('API Credentials') }}</h4>

                @if (count($provider['countries']) > 1)
                    <div class="mb-4">
                        @include('courier::partials._field-label', [
                            'for'      => 'courier-'.$provider['id'].'-country',
                            'text'     => translate('Operating Country'),
                            'required' => true,
                            'help'     => translate('The delivery charge estimate and country options follow this selection.'),
                        ])
                        <select class="form-select courier-searchable-select" name="country"
                                id="courier-{{ $provider['id'] }}-country"
                                data-placeholder="{{ translate('Select Country') }}"
                                data-required-msg="{{ translate('Operating Country').' '.translate('Is required') }}" required>
                            <option value="" disabled {{ $provider['country'] === '' ? 'selected' : '' }}>
                                {{ translate('Select Country') }}
                            </option>
                            @foreach ($provider['countries'] as $country)
                                <option value="{{ $country['id'] }}" {{ $provider['country'] === $country['id'] ? 'selected' : '' }}>
                                    {{ $country['label'] }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                @endif

                @if (count($provider['environments']) > 1)
                    <div class="mb-4">
                        @include('courier::partials._field-label', [
                            'text'     => translate('Environment'),
                            'required' => true,
                            'help'     => translate('Sandbox uses test credentials; live connects to the real platform.'),
                        ])
                        <div class="min-h-40 d-flex align-items-center flex-wrap gap-4 border rounded mb-2 px-3 py-2 bg-white">
                            @foreach ($provider['environments'] as $environment)
                                <div class="form-check d-flex gap-1">
                                    <input class="form-check-input radio--input courier-environment-input" type="radio"
                                           name="environment" value="{{ $environment }}"
                                           id="courier-{{ $provider['id'] }}-env-{{ $environment }}"
                                           data-base-url="{{ $provider['base_urls'][$environment] ?? '' }}"
                                           data-target="#courier-{{ $provider['id'] }}-base-url"
                                           {{ $provider['environment'] === $environment ? 'checked' : '' }}>
                                    <label class="form-check-label text-capitalize"
                                           for="courier-{{ $provider['id'] }}-env-{{ $environment }}">
                                        {{ translate($environment) }}
                                    </label>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif

                @if (!empty($provider['base_urls']))
                    <div class="mb-4">
                        @include('courier::partials._field-label', [
                            'for'  => 'courier-'.$provider['id'].'-base-url',
                            'text' => translate('API Base URL'),
                            'help' => translate('The base url switches automatically with the selected environment'),
                        ])
                        <div class="input-group">
                            <input type="text" readonly class="form-control bg-white"
                                   id="courier-{{ $provider['id'] }}-base-url"
                                   value="{{ $provider['base_urls'][$provider['environment']] ?? '' }}">
                            <button type="button" class="btn btn-outline-secondary courier-copy-btn"
                                    data-copy-target="#courier-{{ $provider['id'] }}-base-url">
                                <i class="fi fi-rr-copy"></i>
                            </button>
                        </div>
                    </div>
                @endif

                <div class="mb-4">
                    @include('courier::partials._field-label', [
                        'for'  => 'courier-'.$provider['id'].'-webhook-url',
                        'text' => translate('Callback URL'),
                        'help' => translate('Give this URL to the delivery platform so status updates reach you.'),
                    ])
                    <div class="input-group">
                        <input type="text" readonly class="form-control bg-white"
                               id="courier-{{ $provider['id'] }}-webhook-url"
                               value="{{ $provider['webhook_url'] }}">
                        <button type="button" class="btn btn-outline-secondary courier-copy-btn"
                                data-copy-target="#courier-{{ $provider['id'] }}-webhook-url">
                            <i class="fi fi-rr-copy"></i>
                        </button>
                    </div>
                </div>

                @forelse ($provider['fields'] as $field)
                    @php($fieldValue = $provider['credentials'][$field['key']] ?? '')
                    @php($isCopyable = str_contains($field['key'], 'url'))
                    <div class="mb-4">
                        @include('courier::partials._field-label', [
                            'for'      => 'courier-'.$provider['id'].'-'.$field['key'],
                            'text'     => translate($field['label']),
                            'required' => !empty($field['required']),
                            'help'     => !empty($field['help']) ? translate($field['help']) : null,
                        ])

                        @php($isSecret = ($field['type'] ?? 'text') === 'password')
                        <div class="{{ $isCopyable ? 'input-group' : ($isSecret ? 'position-relative' : '') }}">
                            @if (($field['type'] ?? 'text') === 'select')
                                <select class="form-select courier-searchable-select" name="credentials[{{ $field['key'] }}]"
                                        id="courier-{{ $provider['id'] }}-{{ $field['key'] }}"
                                        data-placeholder="{{ translate('Select') }} {{ translate($field['label']) }}"
                                        @if (!empty($field['required'])) data-required-msg="{{ translate($field['label']).' '.translate('Is required') }}" required @endif>
                                    @php($translatableOptions = $field['translatable_options'] ?? true)
                                    @foreach ($field['options'] ?? [] as $option)
                                        <option value="{{ showDemoModeInputValue(value: $option['id']) }}" {{ (string) $fieldValue === (string) $option['id'] ? 'selected' : '' }}>
                                            {{ $translatableOptions ? translate($option['label']) : $option['label'] }}
                                        </option>
                                    @endforeach
                                </select>
                            @else
                                <input type="{{ $field['type'] ?? 'text' }}" class="form-control{{ $isSecret ? ' pe-5' : '' }}"
                                       name="credentials[{{ $field['key'] }}]"
                                       id="courier-{{ $provider['id'] }}-{{ $field['key'] }}"
                                       value="{{ showDemoModeInputValue(value: $fieldValue) }}"
                                       @isset($field['placeholder']) placeholder="{{ $field['placeholder'] }}" @endisset
                                       autocomplete="{{ $isSecret ? 'new-password' : 'off' }}"
                                       @if (!empty($field['required'])) data-required-msg="{{ translate($field['label']).' '.translate('Is required') }}" required @endif>
                            @endif
                            @if ($isSecret)
                                <span class="changePassTarget position-absolute top-50 end-0 translate-middle-y me-3 cursor-pointer text-body-secondary" role="button" aria-label="{{ translate('Show hide') }}">
                                    <i class="fi fi-sr-eye"></i>
                                </span>
                            @endif
                            @if ($isCopyable)
                                <button type="button" class="btn btn-outline-secondary courier-copy-btn"
                                        data-copy-target="#courier-{{ $provider['id'] }}-{{ $field['key'] }}">
                                    <i class="fi fi-rr-copy"></i>
                                </button>
                            @endif
                        </div>
                    </div>
                @empty
                    <p class="fs-12 text-muted mb-0">{{ translate('This provider needs no credentials') }}</p>
                @endforelse
            </div>
        </div>

        <div class="offcanvas-footer shadow-lg">
            <div class="d-flex justify-content-center flex-wrap gap-3 bg-white px-3 py-2">
                <button type="button" class="btn btn-secondary px-3 px-sm-4 flex-grow-1 courier-config-reset"
                        data-form="#courier-{{ $provider['id'] }}-form">{{ translate('Reset') }}</button>
                <button type="{{ getDemoModeFormButton(type: 'button') }}"
                        class="btn btn-primary px-3 px-sm-4 flex-grow-1 {{ getDemoModeFormButton(type: 'class') }}">
                    {{ translate('Submit') }}
                </button>
            </div>
        </div>
    </div>
</form>
