@php($courierBs = 4)

<div class="courier-offcanvas" id="courier-offcanvas-{{ $provider['id'] }}" role="dialog" aria-modal="true"
     aria-labelledby="courier-offcanvas-{{ $provider['id'] }}-label" aria-hidden="true">
    <div class="courier-offcanvas-overlay" data-courier-offcanvas-dismiss></div>

    <div class="courier-offcanvas-content">
        <div class="courier-offcanvas-header bg-light d-flex justify-content-between align-items-center gap-3 p-3">
            <h4 class="text-capitalize mb-0" id="courier-offcanvas-{{ $provider['id'] }}-label">
                {{ translate('Setup') }} - {{ $provider['label'] }}
            </h4>
            <button type="button" class="close p-0 fz-22" data-courier-offcanvas-dismiss aria-label="{{ translate('Close') }}">
                <span aria-hidden="true"><i class="tio-clear"></i></span>
            </button>
        </div>

        <form action="{{ route('vendor.courier.config.update') }}" method="post"
              id="courier-{{ $provider['id'] }}-form" autocomplete="off"
              class="form-advance-validation non-ajax-form-validate d-flex flex-column flex-grow-1 overflow-hidden" novalidate>
            @csrf
            @method('PUT')
            <input type="hidden" name="provider" value="{{ $provider['id'] }}">
            <input type="hidden" name="business_module" value="{{ $activeBusinessModule }}">
            <input type="hidden" name="is_enabled" value="0">

            <div class="courier-offcanvas-body p-3">
                <div class="p-3 bg-light rounded mb-3">
                    <h4 class="text-capitalize">{{ $provider['label'] }}</h4>
                    <p class="fz-12">
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

                <div class="p-3 bg-light rounded">
                    <h4 class="mb-3">{{ translate('API Credentials') }}</h4>

                    @if (count($provider['countries']) > 1)
                        <div class="form-group">
                            @include('courier::partials._field-label', [
                                'for'      => 'courier-'.$provider['id'].'-country',
                                'text'     => translate('Operating Country'),
                                'required' => true,
                                'help'     => translate('The delivery charge estimate and country options follow this selection.'),
                            ])
                            <select class="form-control courier-searchable-select" name="country"
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
                        <div class="form-group">
                            @include('courier::partials._field-label', [
                                'text'     => translate('Environment'),
                                'required' => true,
                                'help'     => translate('Sandbox uses test credentials; live connects to the real platform.'),
                            ])
                            <div class="min-h-40 d-flex align-items-center flex-wrap gap-4 border rounded mb-2 px-3 py-2 bg-white">
                                @foreach ($provider['environments'] as $environment)
                                    <div class="d-flex align-items-center gap-1">
                                        <input class="courier-environment-input m-0" type="radio"
                                               name="environment" value="{{ $environment }}"
                                               id="courier-{{ $provider['id'] }}-env-{{ $environment }}"
                                               data-base-url="{{ $provider['base_urls'][$environment] ?? '' }}"
                                               data-target="#courier-{{ $provider['id'] }}-base-url"
                                               {{ $provider['environment'] === $environment ? 'checked' : '' }}>
                                        <label class="mb-0 text-capitalize"
                                               for="courier-{{ $provider['id'] }}-env-{{ $environment }}">
                                            {{ translate($environment) }}
                                        </label>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    @if (!empty($provider['base_urls']))
                        <div class="form-group">
                            @include('courier::partials._field-label', [
                                'for'  => 'courier-'.$provider['id'].'-base-url',
                                'text' => translate('API Base URL'),
                                'help' => translate('The base url switches automatically with the selected environment'),
                            ])
                            <div class="input-group">
                                <input type="text" readonly class="form-control bg-white"
                                       id="courier-{{ $provider['id'] }}-base-url"
                                       value="{{ $provider['base_urls'][$provider['environment']] ?? '' }}">
                                <div class="input-group-append">
                                    <button type="button" class="btn btn-outline-secondary courier-copy-btn"
                                            data-copy-target="#courier-{{ $provider['id'] }}-base-url">
                                        <i class="fi fi-rr-copy"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    @endif

                    <div class="form-group">
                        @include('courier::partials._field-label', [
                            'for'  => 'courier-'.$provider['id'].'-webhook-url',
                            'text' => translate('Callback URL'),
                            'help' => translate('Give this URL to the delivery platform so status updates reach you.'),
                        ])
                        <div class="input-group">
                            <input type="text" readonly class="form-control bg-white"
                                   id="courier-{{ $provider['id'] }}-webhook-url"
                                   value="{{ $provider['webhook_url'] }}">
                            <div class="input-group-append">
                                <button type="button" class="btn btn-outline-secondary courier-copy-btn"
                                        data-copy-target="#courier-{{ $provider['id'] }}-webhook-url">
                                    <i class="fi fi-rr-copy"></i>
                                </button>
                            </div>
                        </div>
                    </div>

                    @forelse ($provider['fields'] as $field)
                        @php($fieldValue = $provider['credentials'][$field['key']] ?? '')
                        @php($isCopyable = str_contains($field['key'], 'url'))
                        <div class="form-group">
                            @include('courier::partials._field-label', [
                                'for'      => 'courier-'.$provider['id'].'-'.$field['key'],
                                'text'     => translate($field['label']),
                                'required' => !empty($field['required']),
                                'help'     => !empty($field['help']) ? translate($field['help']) : null,
                            ])

                            @php($isSecret = ($field['type'] ?? 'text') === 'password')
                            <div class="{{ $isCopyable || $isSecret ? 'input-group' : '' }}">
                                @if (($field['type'] ?? 'text') === 'select')
                                    <select class="form-control courier-searchable-select" name="credentials[{{ $field['key'] }}]"
                                            id="courier-{{ $provider['id'] }}-{{ $field['key'] }}"
                                            data-placeholder="{{ translate('Select') }} {{ translate($field['label']) }}"
                                            @if (!empty($field['required'])) data-required-msg="{{ translate($field['label']).' '.translate('Is required') }}" required @endif>
                                        @php($translatableOptions = $field['translatable_options'] ?? true)
                                        @foreach ($field['options'] ?? [] as $option)
                                            <option value="{{ $option['id'] }}" {{ (string) $fieldValue === (string) $option['id'] ? 'selected' : '' }}>
                                                {{ $translatableOptions ? translate($option['label']) : $option['label'] }}
                                            </option>
                                        @endforeach
                                    </select>
                                @else
                                    <input type="{{ $field['type'] ?? 'text' }}" class="form-control"
                                           name="credentials[{{ $field['key'] }}]"
                                           id="courier-{{ $provider['id'] }}-{{ $field['key'] }}"
                                           value="{{ showDemoModeInputValue(value: $fieldValue) }}"
                                           @isset($field['placeholder']) placeholder="{{ $field['placeholder'] }}" @endisset
                                           autocomplete="{{ $isSecret ? 'new-password' : 'off' }}"
                                           @if (!empty($field['required'])) data-required-msg="{{ translate($field['label']).' '.translate('Is required') }}" required @endif>
                                @endif
                                @if ($isSecret)
                                    <div class="input-group-append">
                                        <button type="button" class="btn btn-outline-secondary courier-pass-toggle"
                                                data-target="#courier-{{ $provider['id'] }}-{{ $field['key'] }}"
                                                aria-label="{{ translate('Show hide') }}">
                                            <i class="fi fi-rr-eye"></i>
                                        </button>
                                    </div>
                                @endif
                                @if ($isCopyable)
                                    <div class="input-group-append">
                                        <button type="button" class="btn btn-outline-secondary courier-copy-btn"
                                                data-copy-target="#courier-{{ $provider['id'] }}-{{ $field['key'] }}">
                                            <i class="fi fi-rr-copy"></i>
                                        </button>
                                    </div>
                                @endif
                            </div>
                        </div>
                    @empty
                        <p class="fz-12 text-muted mb-0">{{ translate('This provider needs no credentials') }}</p>
                    @endforelse
                </div>
            </div>

            <div class="courier-offcanvas-footer d-flex justify-content-center flex-wrap gap-3 border-top bg-white px-3 py-3">
                <button type="button" class="btn btn-secondary px-4 flex-grow-1 courier-config-reset"
                        data-form="#courier-{{ $provider['id'] }}-form">{{ translate('Reset') }}</button>
                <button type="submit" class="btn btn-primary px-4 flex-grow-1">{{ translate('Submit') }}</button>
            </div>
        </form>
    </div>
</div>
