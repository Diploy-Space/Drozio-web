@php
    use Illuminate\Support\Facades\Session;
@endphp
@extends('layouts.admin.app')

@section('title', translate('Language Translate'))

@push('css_or_js')
    <link rel="stylesheet" href="{{ dynamicAsset(path: 'public/assets/back-end/css/admin/language.css') }}">
@endpush

@section('content')
    @php($direction = Session::get('direction') === "rtl" ? 'right' : 'left')
    @php($completionRate = ($stats['total'] ?? 0) > 0 ? round(($stats['translated'] / $stats['total']) * 100) : 0)
    @php($activeStatus = request('status'))
    <div class="content container-fluid">

        <div class="mb-4 pb-2 d-flex flex-wrap justify-content-between align-items-end gap-3">
            <div>
                <h2 class="h1 mb-0 text-capitalize d-flex align-items-center gap-2">
                    <img src="{{dynamicAsset(path: 'public/assets/back-end/img/language.png')}}" alt="">
                    {{translate('View Translations -')}} {{ $languageName . '(' . $lang . ')' }}
                </h2>
                <a href="{{route('admin.settings.system-setup.language.index')}}" class="d-flex mt-2 gap-1 align-items-center text-decoration-none">
                    <i class="fi fi-rr-arrow-small-left mt-1 fs-5"></i>
                    <span class="text text-capitalize">{{translate('Back to Language Setup')}}</span>
                </a>
            </div>
        </div>

        <div class="row g-3 mb-3" id="translation-stat-cards">
            <div class="col-sm-6 col-xl-3">
                <a href="{{ request()->fullUrlWithQuery(['status' => null, 'page' => 1]) }}"
                   class="card h-100 text-reset text-decoration-none translation-filter-card {{ !$activeStatus ? 'active' : '' }}">
                    <div class="card-body d-flex align-items-center gap-3">
                        <div class="stat-icon bg-primary bg-opacity-10 text-primary rounded d-flex align-items-center justify-content-center">
                            <i class="fi fi-sr-list fs-4 d-flex"></i>
                        </div>
                        <div>
                            <h3 class="mb-0 fw-bold" data-stat="total">{{ $stats['total'] ?? 0 }}</h3>
                            <span class="text-muted fs-12 text-capitalize">{{ translate('Total keys') }}</span>
                        </div>
                    </div>
                </a>
            </div>
            <div class="col-sm-6 col-xl-3">
                <a href="{{ request()->fullUrlWithQuery(['status' => 'translated', 'page' => 1]) }}"
                   class="card h-100 text-reset text-decoration-none translation-filter-card {{ $activeStatus === 'translated' ? 'active' : '' }}">
                    <div class="card-body d-flex align-items-center gap-3">
                        <div class="stat-icon bg-success bg-opacity-10 text-success rounded d-flex align-items-center justify-content-center">
                            <i class="fi fi-sr-check-circle fs-4 d-flex"></i>
                        </div>
                        <div>
                            <h3 class="mb-0 fw-bold" data-stat="translated">{{ $stats['translated'] ?? 0 }}</h3>
                            <span class="text-muted fs-12 text-capitalize">{{ translate('Translated') }}</span>
                        </div>
                    </div>
                </a>
            </div>
            <div class="col-sm-6 col-xl-3">
                <a href="{{ request()->fullUrlWithQuery(['status' => 'pending', 'page' => 1]) }}"
                   class="card h-100 text-reset text-decoration-none translation-filter-card {{ $activeStatus === 'pending' ? 'active' : '' }}">
                    <div class="card-body d-flex align-items-center gap-3">
                        <div class="stat-icon bg-warning bg-opacity-10 text-warning rounded d-flex align-items-center justify-content-center">
                            <i class="fi fi-sr-hourglass-end fs-4 d-flex"></i>
                        </div>
                        <div>
                            <h3 class="mb-0 fw-bold" data-stat="untranslated">{{ $stats['untranslated'] ?? 0 }}</h3>
                            <span class="text-muted fs-12 text-capitalize">{{ translate('Untranslated') }}</span>
                        </div>
                    </div>
                </a>
            </div>
            <div class="col-sm-6 col-xl-3">
                <div class="card h-100 translation-metric-card">
                    <div class="card-body d-flex align-items-center gap-3">
                        <div class="stat-icon bg-info bg-opacity-10 text-info rounded d-flex align-items-center justify-content-center">
                            <i class="fi fi-sr-chart-pie-alt fs-4 d-flex"></i>
                        </div>
                        <div class="w-100">
                            <h3 class="mb-1 fw-bold" data-stat="completion">{{ $completionRate }}%</h3>
                            <div class="progress h-5px">
                                <div class="progress-bar bg-info rounded-pill" data-stat="completion-bar" style="width: {{ $completionRate }}%"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-body d-flex flex-column gap-20">
                <div class="bg-info bg-opacity-10 fs-12 px-12 py-10 text-dark rounded d-flex gap-2 align-items-center">
                    <i class="fi fi-sr-info text-info"></i>
                    <span>
                        <strong>{{ translate('Auto Translate') }}</strong> {{ translate('Translates and saves the key instantly.') }}
                        {{ translate('To edit a value manually, type it and click') }} <strong>{{ translate('Save') }}</strong> {{ translate('(or press Enter).') }}
                    </span>
                </div>

                <div class="d-flex justify-content-between align-items-center gap-20 flex-wrap">
                    <h3 class="mb-0 text-capitalize">
                        {{ translate('Language Content Table') }}
                        <span id="content-table-count" class="badge text-dark bg-body-secondary fw-semibold rounded-50">{{ $translations->total() }}</span>
                    </h3>
                    <div class="d-flex flex-wrap gap-3 align-items-stretch translation-actions">
                        <form action="{{ url()->current() }}" method="GET" class="mb-0">
                            <input type="hidden" name="status" value="{{ $activeStatus }}">
                            <div class="input-group flex-grow-1 translation-search">
                                <input id="datatableSearch_" type="search" name="searchValue" class="form-control"
                                       value="{{ request('searchValue') }}" placeholder="{{ translate('Search by key or value') }}"
                                       aria-label="{{ translate('Search by key or value') }}">
                                <div class="input-group-append search-submit">
                                    <button type="submit">
                                        <i class="fi fi-rr-search"></i>
                                    </button>
                                </div>
                            </div>
                        </form>
                        <div class="d-flex flex-wrap gap-3 translation-action-buttons">
                            @if(config('app.mode') != 'demo')
                                <button class="btn btn-outline-info text-nowrap" data-bs-toggle="modal" data-bs-target="#sync-languages-modal">
                                    <i class="fi fi-sr-refresh"></i>
                                    {{ translate('Sync Languages') }}
                                </button>
                                @if(config('app.development_environment'))
                                    <button class="btn btn-outline-danger text-nowrap" id="scan-unused-keys-btn" data-bs-toggle="modal" data-bs-target="#unused-keys-modal">
                                        <i class="fi fi-sr-trash"></i>
                                        {{ translate('Remove Unused Keys') }}
                                    </button>
                                @endif
                                <button class="btn btn-primary text-nowrap" data-bs-toggle="modal" data-bs-target="#warning-modal">
                                    <i class="fi fi-sr-language-exchange"></i>
                                    {{ translate('Translate All') }}
                                </button>
                            @else
                                <button class="btn btn-outline-info text-nowrap call-demo-alert">
                                    <i class="fi fi-sr-refresh"></i>
                                    {{ translate('Sync Languages') }}
                                </button>
                                @if(config('app.development_environment'))
                                    <button class="btn btn-outline-danger text-nowrap call-demo-alert">
                                        <i class="fi fi-sr-trash"></i>
                                        {{ translate('Remove Unused Keys') }}
                                    </button>
                                @endif
                                <button class="btn btn-primary text-nowrap call-demo-alert">
                                    <i class="fi fi-sr-language-exchange"></i>
                                    {{ translate('Translate All') }}
                                </button>
                            @endif
                        </div>
                    </div>
                </div>

                <div class="table-responsive" data-active-status="{{ $activeStatus }}" data-first-item="{{ $translations->firstItem() ?? 1 }}">
                    <table class="table table-hover table-borderless table-thead-bordered align-middle translation-content-table">
                        <colgroup>
                            <col class="col-sl">
                            <col class="col-key">
                            <col class="col-value">
                            <col class="col-status">
                            <col class="col-action">
                        </colgroup>
                        <thead class="thead-light thead-50 text-capitalize">
                        <tr>
                            <th>{{ translate('Serial') }}</th>
                            <th>{{ translate('Key') }}</th>
                            <th>{{ translate('Value') }}</th>
                            <th class="text-center">{{ translate('Status') }}</th>
                            <th class="text-center">{{ translate('Action') }}</th>
                        </tr>
                        </thead>
                        <tbody>
                        @foreach($translations as $key => $row)
                            <tr data-status="{{ $row['status'] }}">
                                <td>{{ $translations->firstItem() + $key }}</td>
                                <td class="translation-key-cell">{{ $row['key'] }}</td>
                                <td>
                                    <textarea class="form-control translation-value" rows="1">{{ $row['value'] }}</textarea>
                                </td>
                                <td class="text-center status-cell">
                                    @if($row['status'] === 'translated')
                                        <span class="badge badge-soft-success text-success"><i class="fi fi-sr-check-circle"></i> {{ translate('Translated') }}</span>
                                    @else
                                        <span class="badge badge-soft-warning text-warning"><i class="fi fi-sr-hourglass-end"></i> {{ translate('Pending') }}</span>
                                    @endif
                                </td>
                                <td>
                                    <div class="d-flex justify-content-center gap-3">
                                        <button type="button" class="btn btn-outline-primary icon-btn autoTranslate"
                                                data-key="{{ $row['encode'] }}" title="{{ translate('Auto Translate') }}">
                                            <i class="fi fi-sr-language-exchange"></i>
                                        </button>
                                        <button type="button" class="btn btn-primary icon-btn update-lang"
                                                data-key="{{ $row['encode'] }}" title="{{ translate('Save') }}">
                                            <i class="fi fi-sr-disk"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="table-responsive mt-4">
                    <div class="px-4 d-flex justify-content-lg-end">
                        {{ $translations->links() }}
                    </div>
                </div>
                @if($translations->total() == 0)
                    @include('layouts.admin.partials._empty-state', ['text' => 'no_data_found', 'image' => 'default'])
                @endif
            </div>
        </div>
    </div>

    @include('layouts.admin.partials.offcanvas._language-translate')

    <div class="modal fade language-complete-modal" id="complete-modal" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-body text-center">
                    <div class="py-5">
                        <div class="mb-4">
                            <img src="{{dynamicAsset('public/assets/back-end/img/language-complete.png')}}" alt="">
                        </div>
                        <h4 class="mb-3">
                            {{ translate('Your file has been successfully translated') }}
                        </h4>
                        <p class="mb-4 text-9EADC1 max-w-362px mx-auto translateCountSuccess"></p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="warning-modal" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-body">
                    <div class="d-flex gap-3 align-items-center mb-3">
                        <div>
                            <img src="{{ dynamicAsset('public/assets/back-end/img/invalid-icon.png') }}" alt="">
                        </div>
                        <div class="w-0 flex-grow-1">
                            <h3>{{ translate('Warning') }}!</h3>
                            <p class="mb-0">
                                {{ translate('Are you sure you want to start auto translation') }} ?
                            </p>
                        </div>
                    </div>
                    <div class="d-flex justify-content-end gap-3">
                        @if(config('app.mode') != 'demo')
                            <button type="button" class="btn btn-primary" data-bs-dismiss="modal" id="translating-modal-start">
                                {{ translate('Continue') }}
                            </button>
                        @else
                            <button type="button" class="btn btn-primary call-demo-alert" data-bs-dismiss="modal">
                                {{ translate('Continue') }}
                            </button>
                        @endif

                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                            {{ translate('Cancel') }}
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>


    <div class="modal fade language-complete-modal" id="translating-modal" data-backdrop="static"
         data-keyboard="false" aria-labelledby="languageCompleteModalLabel" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-body text-center">
                    <div class="py-5 px-sm-2">
                        <div class="mb-4">
                            <div class="spinner-border text-primary" style="width: 3rem; height: 3rem;" role="status">
                                <span class="visually-hidden">{{ translate('Loading') }}</span>
                            </div>
                        </div>
                        <h4 class="mb-2" id="translating-eta">
                            <?php
                                $getDuration = isset($totalMessages) ? ((($totalMessages / 60) / 60) / 30) : 0;
                                $getHours = floor($getDuration);
                                $getMinutes = round(($getDuration - $getHours) * 60);
                            ?>

                            @if($totalMessages <= 0)
                                {{ translate('All messages are translated') }}
                            @elseif($getDuration < 1)
                                {{ translate('Translating may take up to') }} {{ $getMinutes }} {{ translate('Minutes') }}
                            @elseif($getDuration >= 1)
                                @if($getMinutes == 0)
                                    {{ translate('Translating may take up to') }} {{ $getHours }} {{ translate('Hour') }}
                                @else
                                    {{ translate('Translating may take up to') }} {{ $getHours }} {{ translate('Hour') }} {{ $getMinutes }} {{ translate('Minutes') }}
                                @endif
                            @endif

                        </h4>
                        <p class="mb-4">
                            {{ translate('Please do not close the browser or this page') }}
                        </p>
                        <div class="max-w-215px mx-auto">
                            <div class="d-flex flex-wrap mb-1 justify-content-between font-semibold text-dark">
                                <span>{{ translate('In Progress') }}</span>
                                <span class="translating-modal-success-rate">0%</span>
                            </div>
                            <div class="progress mb-3 h-5px">
                                <div class="progress-bar bg-success rounded-pill translating-modal-success-bar" style="width: 0%"></div>
                            </div>
                        </div>
                        <p class="mb-4 text-9EADC1">
                            {{ translate('Translating all the content may take some time.') }} {{ translate('Contact your administrator if you hit problems while translating.') }}
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="unused-keys-modal" tabindex="-1" aria-labelledby="unusedKeysModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">
                <div class="modal-header border-0">
                    <h3 class="mb-0 d-flex align-items-center gap-2">
                        <i class="fi fi-sr-trash text-danger d-flex"></i>
                        {{ translate('Remove Unused Keys') }}
                    </h3>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="text-center py-5" id="unused-keys-loading">
                        <div class="spinner-border text-primary" role="status"><span class="visually-hidden">{{ translate('Loading') }}</span></div>
                        <p class="mt-3 mb-0">{{ translate('Scanning the codebase for unused keys...') }}</p>
                    </div>

                    <div class="d-none" id="unused-keys-result">
                        <div class="bg-danger bg-opacity-10 fs-12 px-12 py-10 text-dark rounded d-flex gap-2 align-items-start mb-3">
                            <i class="fi fi-sr-info text-danger mt-1"></i>
                            <span>
                                {{ translate('These keys are not referenced by any static') }} <strong>translate()</strong> {{ translate('Call in the codebase.') }}
                                {{ translate('They will be removed from all languages.') }}
                                {{ translate('Keys built dynamically may appear here; review before removing.') }}
                                <strong>{{ translate('This action cannot be undone.') }}</strong>
                            </span>
                        </div>
                        <p class="mb-2">
                            <strong class="unused-keys-count">0</strong> {{ translate('Unused keys found out of') }}
                            <strong class="unused-keys-total">0</strong> {{ translate('Total keys') }}
                            <span class="text-muted">({{ translate('Includes') }} <strong class="unused-keys-junk">0</strong> {{ translate('Junk keys') }})</span>.
                        </p>
                        <div class="border rounded p-2 max-h-300px overflow-auto" id="unused-keys-list"></div>
                    </div>

                    <div class="d-none text-center py-5" id="unused-keys-empty">
                        <img src="{{dynamicAsset('public/assets/back-end/img/language-complete.png')}}" alt="" class="mb-3">
                        <h4 class="mb-0">{{ translate('No unused keys found') }}</h4>
                    </div>
                </div>
                <div class="modal-footer border-0 d-none" id="unused-keys-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ translate('Cancel') }}</button>
                    <button type="button" class="btn btn-danger" id="remove-unused-keys-confirm">
                        <i class="fi fi-sr-trash"></i>
                        {{ translate('Remove All') }}
                    </button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade language-complete-modal" id="removing-unused-modal" data-backdrop="static"
         data-keyboard="false" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-body text-center">
                    <div class="py-5 px-sm-2">
                        <div class="mb-4">
                            <div class="spinner-border text-danger" style="width: 3rem; height: 3rem;" role="status"><span class="visually-hidden">{{ translate('Loading') }}</span></div>
                        </div>
                        <h4 class="mb-2">{{ translate('Removing unused keys...') }}</h4>
                        <p class="mb-0">{{ translate('Please do not close the browser or this page') }}</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="sync-languages-modal" tabindex="-1" aria-labelledby="syncLanguagesModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header border-0">
                    <h3 class="mb-0 d-flex align-items-center gap-2">
                        <i class="fi fi-sr-refresh text-info d-flex"></i>
                        {{ translate('Sync Languages') }}
                    </h3>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="bg-info bg-opacity-10 fs-12 px-12 py-10 text-dark rounded d-flex gap-2 align-items-start">
                        <i class="fi fi-sr-info text-info mt-1"></i>
                        <span>
                            {{ translate('This aligns every language to the English master key set.') }}
                            {{ translate('Missing keys are added as untranslated and extra keys are removed.') }}
                            {{ translate('Existing translations are kept.') }}
                            <strong>{{ translate('This action cannot be undone.') }}</strong>
                        </span>
                    </div>
                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ translate('Cancel') }}</button>
                    <button type="button" class="btn btn-info" id="sync-languages-confirm">
                        <i class="fi fi-sr-refresh"></i>
                        {{ translate('Sync Now') }}
                    </button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade language-complete-modal" id="syncing-languages-modal" data-backdrop="static"
         data-keyboard="false" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-body text-center">
                    <div class="py-5 px-sm-2">
                        <div class="mb-4">
                            <div class="spinner-border text-info" style="width: 3rem; height: 3rem;" role="status"><span class="visually-hidden">{{ translate('Loading') }}</span></div>
                        </div>
                        <h4 class="mb-2">{{ translate('Syncing languages...') }}</h4>
                        <p class="mb-0">{{ translate('Please do not close the browser or this page') }}</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <span id="get-sync-languages-route-and-text"
          data-route="{{ route('admin.settings.system-setup.language.sync-languages', ['lang' => $lang]) }}">
    </span>

    <span id="get-unused-keys-route-and-text"
          data-route="{{ route('admin.settings.system-setup.language.unused-keys', ['lang' => $lang]) }}"
          data-remove-route="{{ route('admin.settings.system-setup.language.remove-unused-keys', ['lang' => $lang]) }}"
          data-success-text="{{ translate(':item removed successfully', ['item' => translate('Unused keys')]) }}">
    </span>

    <span id="total-messages-of-current-language"
          data-total="{{ $totalMessages }}"
          data-message-group="{{ $totalMessages > 0 ? $messageGroup : 1 }}"
          data-eta-prefix="{{ translate('Estimated time remaining:') }}"
          data-eta-calculating="{{ translate('Estimating time remaining...') }}"
          data-eta-hour="{{ translate('Hour') }}"
          data-eta-min="{{ translate('Minutes') }}"
          data-eta-sec="{{ translate('Seconds') }}"
          data-eta-almost="{{ translate('Almost done...') }}"
          @if($totalMessages > 0)
            data-message="{{ translate('Your') . ' ' . $totalMessages . ' ' . translate('Messages translated successfully') }}"
          @else
              data-message="{{ translate('All messages are translated') }}"
          @endif
    ></span>
    <span id="get-auto-translate-route-and-text" data-route="{{route('admin.settings.system-setup.language.auto-translate', [$lang])}}"
          data-success-text="{{translate('Key translated successfully')}}"
          data-error-text="{{translate('Translation service is unavailable. Please try again in a few minutes.')}}">
    </span>
    <span id="get-auto-translate-all-route-and-text" data-route="{{ route('admin.settings.system-setup.language.auto-translate-all', [$lang]) }}"
          data-success-text="{{translate('All translated successfully')}}"
          data-error-text="{{translate('Translation service is unavailable. Please try again in a few minutes.')}}">
    </span>
    <span id="get-translate-route-and-text" data-route="{{route('admin.settings.system-setup.language.translate-submit', [$lang])}}"
          data-success-text="{{translate(':item updated successfully', ['item' => translate('Text')])}}">
    </span>
    <span id="status-labels"
          data-translated="{{ translate('Translated') }}"
          data-pending="{{ translate('Pending') }}">
    </span>
@endsection

@push('script')
    <script src="{{dynamicAsset(path: 'public/assets/new/back-end/js/translate.js')}}"></script>
@endpush
