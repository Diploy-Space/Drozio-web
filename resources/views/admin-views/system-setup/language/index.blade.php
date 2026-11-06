@php
    use Illuminate\Support\Facades\File;
@endphp

@extends('layouts.admin.app')

@section('title', translate('Language'))

@push('css_or_js')
    <link rel="stylesheet" href="{{ dynamicAsset(path: 'public/assets/back-end/css/admin/language-setup.css') }}">
@endpush

@section('content')
    <div class="content container-fluid">
        <div class="mb-4 pb-2">
            <h2 class="h1 mb-0 text-capitalize d-flex align-items-center gap-2">
                <img src="{{dynamicAsset(path: 'public/assets/back-end/img/system-setting.png') }}" alt="">
                {{ translate('System Setup') }}
            </h2>
        </div>

        @include('admin-views.system-setup.system-settings-inline-menu')

        <div class="d-flex flex-column gap-20 mb-20">
            <div>
                <div class="bg-warning bg-opacity-10 fs-12 px-12 py-10 text-dark rounded d-flex gap-2 align-items-center">
                    <i class="fi fi-sr-info text-warning"></i>
                    <span>
                        {{ translate('Some settings take time to apply. Clear the session or use incognito.') }}
                    </span>
                </div>
            </div>
        </div>

        <div class="row row-cols-2 row-cols-md-3 row-cols-xxl-6 g-3 mb-3" id="language-metric-cards">
            <div class="col">
                <div class="card h-100 language-metric-card">
                    <div class="card-body d-flex align-items-center gap-3">
                        <div class="stat-icon bg-primary bg-opacity-10 text-primary rounded d-flex align-items-center justify-content-center">
                            <i class="fi fi-sr-globe fs-5 d-flex"></i>
                        </div>
                        <div class="min-w-0">
                            <h3 class="mb-0 fw-bold lh-1">{{ $overview['total'] ?? count($languageList) }}</h3>
                            <span class="text-muted fs-12 text-capitalize">{{ translate('Total Languages') }}</span>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col">
                <div class="card h-100 language-metric-card">
                    <div class="card-body d-flex align-items-center gap-3">
                        <div class="stat-icon bg-success bg-opacity-10 text-success rounded d-flex align-items-center justify-content-center">
                            <i class="fi fi-sr-check-circle fs-5 d-flex"></i>
                        </div>
                        <div class="min-w-0">
                            <h3 class="mb-0 fw-bold lh-1" data-metric="active">{{ $overview['active'] ?? 0 }}</h3>
                            <span class="text-muted fs-12 text-capitalize">{{ translate('Active') }}</span>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col">
                <div class="card h-100 language-metric-card">
                    <div class="card-body d-flex align-items-center gap-3">
                        <div class="stat-icon bg-secondary bg-opacity-10 text-secondary rounded d-flex align-items-center justify-content-center">
                            <i class="fi fi-sr-ban fs-5 d-flex"></i>
                        </div>
                        <div class="min-w-0">
                            <h3 class="mb-0 fw-bold lh-1" data-metric="inactive">{{ $overview['inactive'] ?? 0 }}</h3>
                            <span class="text-muted fs-12 text-capitalize">{{ translate('Inactive') }}</span>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col">
                <div class="card h-100 language-metric-card">
                    <div class="card-body d-flex align-items-center gap-3">
                        <div class="stat-icon bg-info bg-opacity-10 text-info rounded d-flex align-items-center justify-content-center">
                            <i class="fi fi-sr-arrow-small-right fs-5 d-flex"></i>
                        </div>
                        <div class="min-w-0">
                            <h3 class="mb-0 fw-bold lh-1">{{ $overview['ltr'] ?? 0 }}</h3>
                            <span class="text-muted fs-12 text-uppercase">LTR</span>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col">
                <div class="card h-100 language-metric-card">
                    <div class="card-body d-flex align-items-center gap-3">
                        <div class="stat-icon bg-warning bg-opacity-10 text-warning rounded d-flex align-items-center justify-content-center">
                            <i class="fi fi-sr-arrow-small-left fs-5 d-flex"></i>
                        </div>
                        <div class="min-w-0">
                            <h3 class="mb-0 fw-bold lh-1">{{ $overview['rtl'] ?? 0 }}</h3>
                            <span class="text-muted fs-12 text-uppercase">{{ translate('RTL') }}</span>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col">
                <div class="card h-100 language-metric-card">
                    <div class="card-body d-flex align-items-center gap-3">
                        <div class="stat-icon bg-danger bg-opacity-10 text-danger rounded d-flex align-items-center justify-content-center">
                            <i class="fi fi-sr-star fs-5 d-flex"></i>
                        </div>
                        <div class="min-w-0">
                            <h3 class="mb-0 fw-bold lh-1 text-truncate text-capitalize">{{ $overview['default'] ?? 'English' }}</h3>
                            <span class="text-muted fs-12 text-capitalize">{{ translate('Default Language') }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-body d-flex flex-column gap-20">
                <div class="d-flex flex-wrap gap-3 justify-content-between align-items-center language-list-header">
                    <h3 class="mb-0 text-capitalize">
                        {{ translate('Language list') }}
                        <span class="badge text-dark bg-body-secondary fw-semibold rounded-50">{{ count($languageList) }}</span>
                    </h3>

                    <div class="d-flex flex-wrap gap-3 align-items-stretch language-list-actions">
                        <form action="{{ route('admin.settings.system-setup.language.index') }}" method="get" class="mb-0">
                            <div class="input-group language-search">
                                <input type="search" class="form-control" name="search"
                                       value="{{ request('search') }}"
                                       placeholder="{{ translate('Search Language') }}">
                                <div class="input-group-append search-submit">
                                    <button type="submit">
                                        <i class="fi fi-rr-search"></i>
                                    </button>
                                </div>
                            </div>
                        </form>
                        <button type="button" class="btn btn-primary text-nowrap" data-bs-toggle="offcanvas"
                                data-bs-target="#addLanguageOffcanvas" aria-controls="addLanguageOffcanvas">
                            <i class="fi fi-sr-add"></i>
                            {{ translate('Add New Language') }}
                        </button>
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="table table-hover table-borderless table-thead-bordered table-align-middle card-table language-setup-table">
                        <thead class="thead-light thead-50 text-capitalize">
                        <tr>
                            <th>{{ translate('Serial') }}</th>
                            <th>{{ translate('Language') }}</th>
                            <th>{{ translate('Code') }}</th>
                            <th class="min-w-160">{{ translate('Translated') }}</th>
                            <th class="text-center">{{ translate('Status') }}</th>
                            <th class="text-center">{{ translate('Action') }}</th>
                        </tr>
                        </thead>
                        <tbody>
                        @foreach($languageList as $key => $language)
                            @php($completion = $languageStats[$language['code']]['completion'] ?? 0)
                            <tr>
                                <td>{{ $key+1 }}</td>
                                <td>
                                    <div class="d-flex align-items-center gap-2 text-capitalize">
                                        <img class="lang-flag" alt="{{ $language['code'] }}"
                                             src="{{ dynamicAsset(path: 'public/assets/front-end/img/flags/'.$language['code'].'.png') }}"
                                             onerror="this.style.visibility='hidden'">
                                        <span class="fw-medium">{{ $language['name'] }}</span>
                                        <span class="badge badge-soft-secondary text-uppercase fs-10">{{ $language['direction'] ?? 'ltr' }}</span>
                                        @if (array_key_exists('default', $language) && $language['default'])
                                            <span class="badge text-bg-info badge-info badge-sm">
                                                {{ translate('Default') }}
                                            </span>
                                        @endif
                                    </div>
                                </td>
                                <td class="text-uppercase">{{ $language['code'] }}</td>
                                <td>
                                    <div class="d-flex align-items-center gap-2 lang-progress">
                                        <div class="progress flex-grow-1">
                                            <div class="progress-bar rounded-pill {{ $completion >= 100 ? 'bg-success' : ($completion >= 50 ? 'bg-info' : 'bg-warning') }}"
                                                 style="width: {{ $completion }}%"></div>
                                        </div>
                                        <span class="fs-12 fw-semibold text-dark min-w-40 text-end">{{ $completion }}%</span>
                                    </div>
                                </td>
                                <td>
                                    @if (array_key_exists('default', $language) && $language['default'])
                                        <label class="switcher mx-auto" id="default-language-status-alert"
                                               data-text="{{ translate('Default language cannot be deactivate').'!' }}">
                                            <input type="checkbox" class="switcher_input" checked disabled>
                                            <span class="switcher_control"></span>
                                        </label>
                                    @else
                                        <form action="{{ route('admin.settings.system-setup.language.update-status') }}"
                                              method="post" id="language-id-{{ $language['id'] }}-form" class="no-reload-form">
                                            @csrf
                                            <input type="hidden" name="code" value="{{ $language['code'] }}">
                                            <label class="switcher mx-auto" for="language-status-{{ $language['id'] }}">
                                                <input
                                                    class="switcher_input custom-modal-plugin"
                                                    type="checkbox" value="1" name="status"
                                                    id="language-status-{{ $language['id'] }}"
                                                    {{ $language['status'] == 1 ? 'checked' : ' '}}
                                                    data-modal-type="input-change-form"
                                                    data-modal-form="#language-id-{{ $language['id'] }}-form"
                                                    data-on-image="{{ dynamicAsset(path: 'public/assets/new/back-end/img/modal/language-on.png') }}"
                                                    data-off-image="{{ dynamicAsset(path: 'public/assets/new/back-end/img/modal/language-off.png') }}"
                                                    data-on-title="{{ translate('Want to Turn ON :item', ['item' => translate('Language Status')]).'?'}}"
                                                    data-off-title="{{ translate('Want to Turn OFF :item', ['item' => translate('Language Status')]).'?'}}"
                                                    data-on-message="<p>{{ translate('If enabled this language will be available throughout the entire system') }}</p>"
                                                    data-off-message="<p>{{ translate('If disabled this language will be hidden from the entire system') }}</p>"
                                                    data-on-button-text="{{ translate('Turn On') }}"
                                                    data-off-button-text="{{ translate('Turn Off') }}">
                                                <span class="switcher_control"></span>
                                            </label>
                                        </form>
                                    @endif
                                </td>
                                <td class="text-center">
                                    <div class="d-flex justify-content-center gap-3">
                                        <a class="btn btn-outline-primary btn-xs"
                                           href="{{ route('admin.settings.system-setup.language.translate', ['lang' => $language['code']]) }}">
                                            <i class="fi fi-sr-language-exchange"></i>
                                            {{ translate('View') }}
                                        </a>
                                        <div class="dropdown">
                                            <button class="btn btn-outline-primary btn-outline-primary-dark icon-btn"
                                                    data-bs-toggle="dropdown">
                                                <i class="fi fi-sr-menu-dots-vertical"></i>
                                            </button>

                                            <div class="dropdown-menu">

                                                <button class="dropdown-item d-flex align-items-center gap-2 {{ (array_key_exists('default', $language) && $language['default'] === true) ? "mark-as-default-language-alert opacity-50" : "" }}"
                                                        @if(!(array_key_exists('default', $language) && $language['default'] === true))
                                                            data-bs-toggle="modal"
                                                            data-bs-target="#languageDefaultModal{{ $language['id'] }}"
                                                        @endif
                                                >
                                                    <i class="fi fi-rr-check-circle mt-1"></i>
                                                    {{ translate('Mark As Default') }}
                                                </button>

                                                @if($language['code'] != 'en')
                                                    <a class="dropdown-item d-flex align-items-center gap-2 pointer"
                                                       data-bs-toggle="offcanvas"
                                                       data-bs-target="#languageEditModal{{ $language['id'] }}">
                                                        <i class="fi fi-rr-pen-circle mt-1"></i>
                                                        {{ translate('Edit') }}
                                                    </a>
                                                @endif

                                                @if ($language['code'] == 'en' || $language['default'] === true)
                                                    <button class="dropdown-item d-flex align-items-center gap-2 default-language-delete-alert opacity-50" data-code="{{ $language['code'] }}">
                                                        <i class="fi fi-rr-trash mt-1"></i>
                                                        {{ translate('Delete') }}
                                                    </button>
                                                @else
                                                    <button class="dropdown-item d-flex align-items-center gap-2"
                                                            data-bs-toggle="modal"
                                                            data-bs-target="#languageDeleteModal{{ $language['id'] }}">
                                                        <i class="fi fi-rr-trash mt-1"></i>
                                                        {{ translate('Delete') }}
                                                    </button>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                    @if(count($languageList) == 0)
                        @include('layouts.admin.partials._empty-state', ['text' => 'no_language_found'], ['image' => 'default'])
                    @endif
                </div>
            </div>
        </div>

        @include("admin-views.system-setup.language._language-offcanvas")

        @foreach($languageList as $key => $language)
            <form action="{{ route('admin.settings.system-setup.language.update-default-status') }}" method="post">
                @csrf
                <input type="hidden" name="code" value="{{ $language['code'] }}">
                <div class="modal fade" id="languageDefaultModal{{ $language['id'] }}" tabindex="-1"
                     aria-labelledby="languageDefaultModal{{ $language['id'] }}" aria-hidden="true">
                    <div class="modal-dialog modal-dialog-centered">
                        <div class="modal-content">
                            <div class="modal-header border-0 pb-0 d-flex justify-content-end">
                                <button type="button"
                                        class="btn-close border-0 btn-circle bg-section2 shadow-none"
                                        data-bs-dismiss="modal" aria-label="Close">
                                </button>
                            </div>
                            <div class="modal-body px-20 py-0 mb-30">
                                <div class="d-flex flex-column align-items-center text-center mb-30">
                                    <img
                                        src="{{ dynamicAsset(path: 'public/assets/back-end/img/modal/general-icon.png') }}"
                                        width="80" class="mb-20" id="" alt="">
                                    <h2 class="modal-title mb-3">
                                        {{ translate('Want to change default language').' ?' }}
                                    </h2>
                                    <div class="text-center">
                                        {{ translate('Are you sure you want to change the default language').' ?' }}
                                    </div>
                                </div>
                                <div class="d-flex justify-content-center gap-3">
                                    <button type="button" class="btn btn-secondary max-w-120 flex-grow-1"
                                            data-bs-dismiss="modal">
                                        {{ translate('No') }}
                                    </button>
                                    <button type="{{ getDemoModeFormButton(type: 'button') }}"
                                            class="btn btn-danger max-w-120 flex-grow-1 {{ getDemoModeFormButton(type: 'class') }}"
                                            data-bs-dismiss="modal">
                                        {{ translate('Yes') }}
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </form>

            <form action="{{ route('admin.settings.system-setup.language.delete', [$language['code']]) }}" method="get">
                @csrf
                <input type="hidden" name="id" value="{{ $language['id'] }}">
                <div class="modal fade" id="languageDeleteModal{{ $language['id'] }}" tabindex="-1"
                     aria-labelledby="languageDeleteModal{{ $language['id'] }}" aria-hidden="true">
                    <div class="modal-dialog modal-dialog-centered">
                        <div class="modal-content">
                            <div class="modal-header border-0 pb-0 d-flex justify-content-end">
                                <button type="button"
                                        class="btn-close border-0 btn-circle bg-section2 shadow-none"
                                        data-bs-dismiss="modal" aria-label="Close">
                                </button>
                            </div>
                            <div class="modal-body px-20 py-0 mb-30">
                                <div class="d-flex flex-column align-items-center text-center mb-30">
                                    <img
                                        src="{{dynamicAsset(path: 'public/assets/new/back-end/img/modal/delete.png')}}"
                                        width="80" class="mb-20" id="" alt="">
                                    <h2 class="modal-title mb-3">
                                        {{ translate('Want to delete this language').' ?' }}
                                    </h2>
                                    <div class="text-center">
                                        {{ translate('Are you sure you want to delete this language').' ?' }}
                                        {{ translate('It will be permanently deleted from your database') }}
                                    </div>
                                </div>
                                <div class="d-flex justify-content-center gap-3">
                                    <button type="button" class="btn btn-secondary max-w-120 flex-grow-1"
                                            data-bs-dismiss="modal">
                                        {{ translate('No') }}
                                    </button>
                                    <button type="{{ getDemoModeFormButton(type: 'button') }}"
                                            class="btn btn-danger max-w-120 flex-grow-1 {{ getDemoModeFormButton(type: 'class') }}"
                                            data-bs-dismiss="modal">
                                        {{ translate('Yes Delete') }}
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </form>
        @endforeach
    </div>

    <div class="offcanvas offcanvas-end" tabindex="-1" id="addLanguageOffcanvas" aria-labelledby="addLanguageOffcanvasLabel">
        <div class="offcanvas-header bg-body">
            <div>
                <h3 class="mb-1">{{ translate('Add New Language') }}</h3>
                <p class="mb-0 fs-12 text-muted">
                    {{ translate('Set up new languages in your system.') . ' ' . translate('To make the order from versatile customers on your website and apps.') }}
                </p>
            </div>
            <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
        </div>
        <div class="offcanvas-body">
            <form action="{{ route('admin.settings.system-setup.language.add-new') }}" method="post" class="form-advance-validation form-advance-inputs-validation form-advance-file-validation non-ajax-form-validate d-flex flex-column h-100" novalidate="novalidate">
                @csrf
                <div class="d-flex flex-column gap-3 flex-grow-1">
                    <div class="form-group">
                        <label class="form-label" for="add-language-name">
                            {{ translate('Language') }}
                        </label>
                        <input type="text" name="name" id="add-language-name" class="form-control"
                               data-required-msg="{{ translate('Language field is required') }}"
                               placeholder="{{ translate('Language name') }}" required>
                        <input name="type" value="add" hidden>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="">
                            {{ translate('Country code') }}
                        </label>
                        <select name="code" class="custom-select image-var-select"
                                data-placeholder="Select from dropdown" data-required-msg="{{ translate(':field is required', ['field' => translate('Country code')]) }}" required>
                            <option></option>
                            @foreach($languageLocaleList as $languageLocale)
                                @if($languageLocale['locale'] !='en')
                                    <option value="{{ strtolower($languageLocale['locale']) }}"
                                        data-image-url="{{ $languageLocale['flag'] }}">
                                        {{ strtoupper($languageLocale['locale']) }} - {{ ucwords($languageLocale['name']) }}
                                        @if(isset($languageLocale['script']))
                                            - {{ $languageLocale['script'] }}
                                        @endif
                                    </option>
                                @endif
                            @endforeach
                        </select>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="">
                            {{ translate('Direction') }}
                        </label>
                        <div class="min-h-40 d-flex align-items-center gap-3 gap-lg-5 border rounded mb-2 px-3 py-1 bg-white">
                            <div class="form-check d-flex gap-1">
                                <input class="form-check-input radio--input" type="radio"
                                       name="direction" id="direction_left"
                                       value="ltr" checked>
                                <label class="form-check-label" for="direction_left">
                                    {{ translate('Left to Right') }}
                                </label>
                            </div>
                            <div class="form-check d-flex gap-1">
                                <input class="form-check-input radio--input" type="radio"
                                       name="direction" id="direction_right"
                                       value="rtl">
                                <label class="form-check-label" for="direction_right">
                                    {{ translate('Right to Left') }}
                                </label>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="d-flex justify-content-end flex-wrap gap-3 pt-3 border-top">
                    <button type="reset" class="btn btn-secondary px-3 px-sm-4 w-120">{{ translate('Reset') }}</button>
                    <button type="submit" class="btn btn-primary px-3 px-sm-4">
                        <i class="fi fi-sr-disk"></i>
                        {{ translate('Submit') }}
                    </button>
                </div>
            </form>
        </div>
    </div>

    <span id="get-language-warning-message"
          data-title="{{ translate('Warning') }}!"
          data-message="{{ translate('The default language cannot be deleted').' !' }} {{ translate('To delete it you must first change the default language.') }}"
          data-english="{{ translate('The English language cannot be deleted').' !' }} {{ translate('There are system dependencies so you cannot delete it.') }}">
    </span>

    <span id="get-mark-default-warning-message"
          data-title="{{ translate('Warning') }}!"
          data-message="{{ translate('This language is already set as the default language.') }}">
    </span>

    @include("layouts.admin.partials.offcanvas._language-setup")
@endsection

@push('script')
    <script src="{{dynamicAsset(path: 'public/assets/backend/admin/js/system-setup/system-setup.js') }}"></script>
    <script>
        "use strict";
        // Status toggles submit via AJAX (no reload); recompute the Active/Inactive cards from the switchers.
        $(document).ajaxComplete(function (event, xhr, settings) {
            if (!settings.url || settings.url.indexOf('language/update-status') === -1) return;
            const switchers = $('.language-setup-table tbody .switcher_input');
            const active = switchers.filter(':checked').length;
            $('#language-metric-cards [data-metric="active"]').text(active);
            $('#language-metric-cards [data-metric="inactive"]').text(Math.max(0, switchers.length - active));
        });
    </script>
@endpush
