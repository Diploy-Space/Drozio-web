@extends('layouts.admin.app')

@section('title', translate('Delivery Partner Integration'))

@php($enabledCount = collect($providers)->where('is_enabled', true)->count())

@section('content')
    <div class="content container-fluid">
        <div class="mb-4 pb-2">
            <h2 class="h1 mb-0 text-capitalize d-flex align-items-center gap-2">
                <img src="{{ dynamicAsset(path: 'public/assets/back-end/img/3rd-party.png') }}"
                     loading="lazy" decoding="async" alt="{{ translate('Delivery Partner Integration') }}">
                {{ translate('Delivery Partner Integration') }}
            </h2>
        </div>

        <div class="bg-warning bg-opacity-10 fs-12 px-12 py-10 text-dark rounded d-flex gap-2 align-items-center mb-3">
            <i class="fi fi-sr-info text-warning"></i>
            <span>
                {{ translate('Here you can configure delivery partner by obtaining the necessary credentials') }}
                ({{ translate('E.g., api keys') }})
                {{ translate('From each respective delivery platform') }}.
            </span>
        </div>

        @if (!($deliveryPartnerServiceEnabled ?? true))
            <div class="bg-danger bg-opacity-10 fs-12 px-12 py-10 text-dark rounded d-flex gap-2 align-items-center mb-3">
                <i class="fi fi-sr-triangle-warning text-danger"></i>
                <span>
                    {{ translate('Third party delivery is off. Partners can be configured, not assigned.') }}
                </span>
            </div>
        @endif

        @if ($enabledCount <= 0)
            <div class="bg-danger bg-opacity-10 fs-12 px-12 py-10 text-dark rounded d-flex gap-2 align-items-center mb-3">
                <i class="fi fi-sr-triangle-warning text-danger"></i>
                <span>
                    {{ translate('No delivery partner supports your system. Activate at least one.') }}
                </span>
            </div>
        @endif

        <div class="card">
            <div class="card-body">
                @if ($showBusinessModuleTabs)
                    <ul class="nav nav-pills nav--tab mb-3" role="tablist">
                        @foreach ($businessModules as $businessModule => $businessModuleLabel)
                            <li class="nav-item">
                                <a class="nav-link {{ $businessModule === $activeBusinessModule ? 'active' : '' }}"
                                   href="{{ route('admin.settings.courier.config.index', ['business_module' => $businessModule]) }}">
                                    {{ $businessModuleLabel }}
                                </a>
                            </li>
                        @endforeach
                    </ul>
                @endif

                <div class="d-flex flex-wrap gap-3 justify-content-between align-items-center mb-3">
                    <h3 class="mb-0">{{ translate('Delivery Partner List') }}</h3>

                    <div class="form-group mb-0 flex-grow-1 max-w-300 min-w-100-mobile">
                        <div class="input-group">
                            <input type="search" id="courier-provider-search" class="form-control"
                                   placeholder="{{ translate('Search by delivery partner name') }}">
                            <div class="input-group-append search-submit">
                                <button type="submit">
                                    <i class="fi fi-rr-search"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row gy-3" id="courier-provider-cards">
                    @forelse ($providers as $provider)
                        <div class="col-md-6 courier-provider-cards">
                            <div class="card shadow-2 h-100">
                                <div class="card-body d-flex gap-3 align-items-center">
                                    @php($courierLogo = 'assets/back-end/img/courier/logos/'.$provider['id'].'.png')
                                    @if (file_exists(public_path($courierLogo)))
                                        <img class="courier-avatar" src="{{ dynamicAsset(path: 'public/'.$courierLogo) }}"
                                             loading="lazy" decoding="async" alt="{{ $provider['label'] }}">
                                    @else
                                        <span class="courier-avatar">{{ mb_substr($provider['label'], 0, 1) }}</span>
                                    @endif
                                    <div class="flex-grow-1 min-w-0">
                                        <h4 class="text-capitalize mb-1 text-truncate">{{ $provider['label'] }}</h4>
                                        @if (!$provider['is_configured'])
                                            <span class="badge text-bg-danger badge-danger">{{ translate('Not Configured') }}</span>
                                        @elseif ($provider['is_enabled'])
                                            <span class="badge text-bg-success badge-success">{{ translate('Enabled') }}</span>
                                        @else
                                            <span class="badge text-bg-info badge-info">{{ translate('Disabled') }}</span>
                                        @endif
                                    </div>

                                    <div class="d-flex gap-2 align-items-center flex-shrink-0">
                                        @if ($provider['is_configured'])
                                            <form action="{{ route('admin.settings.courier.config.update') }}" method="post"
                                                  id="courier-{{ $provider['id'] }}-status-form">
                                                @csrf
                                                @method('PUT')
                                                <input type="hidden" name="provider" value="{{ $provider['id'] }}">
                                                <input type="hidden" name="business_module" value="{{ $activeBusinessModule }}">
                                                <input type="hidden" name="is_enabled" value="0">
                                                <label class="switcher" for="courier-{{ $provider['id'] }}-status">
                                                    <input class="switcher_input custom-modal-plugin"
                                                           type="checkbox" value="1" name="is_enabled"
                                                           id="courier-{{ $provider['id'] }}-status"
                                                           {{ $provider['is_enabled'] ? 'checked' : '' }}
                                                           data-modal-type="input-change-form"
                                                           data-modal-form="#courier-{{ $provider['id'] }}-status-form"
                                                           data-on-image="{{ dynamicAsset(path: 'public/assets/back-end/img/modal/payment-gateway-on.png') }}"
                                                           data-off-image="{{ dynamicAsset(path: 'public/assets/back-end/img/modal/payment-gateway-off.png') }}"
                                                           data-on-title="{{ translate('Want to enable') }} {{ $provider['label'] }} {{ translate('Delivery').'?' }}"
                                                           data-off-title="{{ translate('Want to disable') }} {{ $provider['label'] }} {{ translate('Delivery').'?' }}"
                                                           data-on-message="<p>{{ translate('Makes this partner available for third-party delivery.') . '.' }}</p>"
                                                           data-off-message="<p>{{ translate('Removes this partner from third-party delivery. Orders cannot be shipped with it.') . '.' }}</p>">
                                                    <span class="switcher_control"></span>
                                                </label>
                                            </form>
                                        @else
                                            <label class="switcher" data-bs-toggle="offcanvas"
                                                   data-bs-target="#courier-offcanvas-{{ $provider['id'] }}">
                                                <input class="switcher_input" type="checkbox" value="1" name="is_enabled" disabled>
                                                <span class="switcher_control"></span>
                                            </label>
                                        @endif

                                        <button type="button" class="btn btn-outline-warning btn-outline-warning-dark icon-btn"
                                                data-bs-toggle="offcanvas"
                                                data-bs-target="#courier-offcanvas-{{ $provider['id'] }}">
                                            <i class="fi fi-sr-settings"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="w-100">
                            @include('layouts.admin.partials._empty-state', ['text' => 'no_delivery_partner_is_registered', 'image' => 'deliveryman', 'width' => 60])
                        </div>
                    @endforelse

                    <div class="empty-state-for-courier d-none w-100">
                        @include('layouts.admin.partials._empty-state', ['text' => 'no_delivery_partner_found', 'image' => 'deliveryman', 'width' => 60])
                    </div>
                </div>
            </div>
        </div>

        @foreach ($providers as $provider)
            @include('courier::admin.config._setup-offcanvas', ['provider' => $provider])
        @endforeach
    </div>
@endsection

@push('script')
    <script>
        'use strict';
        $("#courier-provider-search").on("input search", function () {
            let value = $(this).val().toLowerCase();
            $(".courier-provider-cards").each(function () {
                $(this).toggle($(this).text().toLowerCase().indexOf(value) > -1);
            });
            $('.empty-state-for-courier').toggleClass('d-none', $(".courier-provider-cards:visible").length > 0);
        });

        const hasSelect2 = () => window.jQuery && typeof window.jQuery.fn.select2 === 'function';

        function enhanceCourierSelects(panel) {
            if (!hasSelect2()) return;
            $(panel).find('.courier-searchable-select').each(function () {
                let $select = $(this);
                if ($select.hasClass('select2-hidden-accessible')) $select.select2('destroy');
                $select.select2({
                    width: '100%',
                    placeholder: $select.data('placeholder') || '',
                    dropdownParent: $(panel),
                    minimumResultsForSearch: $select.find('option').length > 7 ? 0 : Infinity,
                });
            });
        }

        document.querySelectorAll('[data-bs-toggle="tooltip"]')
            .forEach(el => bootstrap.Tooltip.getOrCreateInstance(el));

        $('.offcanvas[id^="courier-offcanvas-"]')
            .on('shown.bs.offcanvas', function () {
                enhanceCourierSelects(this);
            })
            .on('hidden.bs.offcanvas', function () {
                if (!hasSelect2()) return;
                $(this).find('.courier-searchable-select.select2-hidden-accessible').select2('destroy');
            });

        function syncCourierBaseUrl(environmentInput) {
            let target = document.querySelector($(environmentInput).data('target'));
            if (target) target.value = $(environmentInput).data('base-url') || '';
        }

        $(document).on('change', '.courier-environment-input', function () {
            syncCourierBaseUrl(this);
        });

        $(document).on('click', '.courier-config-reset', function () {
            let form = document.querySelector($(this).data('form'));
            if (!form) return;

            form.reset();
            $(form).find('.courier-searchable-select.select2-hidden-accessible').trigger('change.select2');

            let environment = form.querySelector('.courier-environment-input:checked');
            if (environment) syncCourierBaseUrl(environment);
        });

        $(document).on('click', '.courier-copy-btn', function () {
            let input = document.querySelector($(this).data('copy-target'));
            if (!input) return;
            navigator.clipboard.writeText(input.value).then(function () {
                toastMagic.success('{{ translate('Copied to clipboard') }}');
            });
        });
    </script>
@endpush
