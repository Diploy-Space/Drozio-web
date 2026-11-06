@extends('layouts.admin.app')

@section('title', translate('Clean Database'))

@push('css_or_js')
    <link rel="stylesheet" href="{{ dynamicAsset(path: 'public/assets/back-end/css/admin/clean-database.css') }}">
@endpush

@section('content')
    @php
        $cleanableTables = collect($tables)->where('protected', false);
        $totalCleanable = $cleanableTables->count();
        $totalProtected = collect($tables)->where('protected', true)->count();
        $totalRecords = $cleanableTables->sum('count');
    @endphp
    <div class="content container-fluid">
        <div class="mb-4 pb-2">
            <h2 class="h1 mb-0 text-capitalize d-flex align-items-center gap-2">
                <img src="{{ dynamicAsset(path: 'public/assets/back-end/img/system-setting.png') }}" alt="">
                {{ translate('System Setup') }}
            </h2>
        </div>

        @include('admin-views.system-setup.system-settings-inline-menu')

        <div class="row row-cols-1 row-cols-md-3 g-3 mb-3" id="db-metric-cards">
            <div class="col">
                <div class="card h-100 db-metric-card">
                    <div class="card-body d-flex align-items-center gap-3">
                        <div class="stat-icon bg-primary bg-opacity-10 text-primary rounded d-flex align-items-center justify-content-center">
                            <i class="fi fi-sr-database fs-5 d-flex"></i>
                        </div>
                        <div class="min-w-0">
                            <h3 class="mb-0 fw-bold lh-1">{{ $totalCleanable }}</h3>
                            <span class="text-muted fs-12 text-capitalize">{{ translate('Cleanable tables') }}</span>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col">
                <div class="card h-100 db-metric-card">
                    <div class="card-body d-flex align-items-center gap-3">
                        <div class="stat-icon bg-info bg-opacity-10 text-info rounded d-flex align-items-center justify-content-center">
                            <i class="fi fi-sr-list fs-5 d-flex"></i>
                        </div>
                        <div class="min-w-0">
                            <h3 class="mb-0 fw-bold lh-1">{{ number_format($totalRecords) }}</h3>
                            <span class="text-muted fs-12 text-capitalize">{{ translate('Total records') }}</span>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col">
                <div class="card h-100 db-metric-card">
                    <div class="card-body d-flex align-items-center gap-3">
                        <div class="stat-icon bg-danger bg-opacity-10 text-danger rounded d-flex align-items-center justify-content-center">
                            <i class="fi fi-sr-trash fs-5 d-flex"></i>
                        </div>
                        <div class="min-w-0">
                            <h3 class="mb-0 fw-bold lh-1" data-metric="selected">0</h3>
                            <span class="text-muted fs-12 text-capitalize">{{ translate('Selected to clean') }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="bg-warning bg-opacity-10 fs-12 px-12 py-10 text-dark rounded mb-3">
            <div class="d-flex gap-2 align-items-center mb-2">
                <i class="fi fi-sr-info text-warning"></i>
                <span>
                    {{ translate('This page contains sensitive information.') }}
                    {{ translate('Make sure before changing.') }}
                </span>
            </div>
            <ul class="m-0 ps-20 d-flex flex-column gap-1 text-body">
                <li>{{ translate('Check how the data you delete interacts with other sections.') }}</li>
                <li>{{ translate('Implement backup to reduce risks and ensure data recovery if necessary.') }}</li>
                <li>{{ translate('Delete only after reviewing every dependency.') }}</li>
            </ul>
        </div>

        <div class="card">
            <div class="card-header d-flex flex-wrap gap-3 align-items-center justify-content-between">
                <div>
                    <h3 class="mb-1 text-capitalize d-flex align-items-center gap-2">
                        <i class="fi fi-sr-broom text-primary d-flex"></i>
                        {{ translate('Clean Database') }}
                    </h3>
                    <p class="mb-0 fs-12 text-muted">
                        {{ translate('Maintaining a clean database') . ': ' . translate('Best practices for data purging and optimization.') }}
                    </p>
                </div>
                <div class="d-flex flex-wrap gap-3 align-items-center">
                    <form class="mb-0" onsubmit="return false;">
                        <div class="input-group flex-grow-1 max-w-280">
                            <input id="db-table-search" type="search" name="searchValue" class="form-control"
                                   placeholder="{{ translate('Search by table name') }}"
                                   aria-label="{{ translate('Search by table name') }}">
                            <div class="input-group-append search-submit">
                                <button type="submit"><i class="fi fi-rr-search"></i></button>
                            </div>
                        </div>
                    </form>
                    <div class="form-check d-flex align-items-center gap-2 m-0">
                        <input type="checkbox" class="form-check-input checkbox--input m-0" id="db-select-all">
                        <label class="form-check-label pointer text-capitalize" for="db-select-all">{{ translate('Select all') }}</label>
                    </div>
                </div>
            </div>
            <div class="card-body">
                <form action="{{ route('admin.settings.system-setup.clean-db') }}" method="post" class="clean-database-form" enctype="multipart/form-data">
                    @csrf
                    <div class="bg-section rounded-8 p-12 p-sm-20">
                        <div class="row gy-3" id="db-table-grid">
                            @foreach($tables as $key => $table)
                                <div class="col-sm-6 col-xl-3 db-table-col" data-table-name="{{ strtolower(str_replace('_', ' ', $table['name'])) }}">
                                    <label class="db-table-item user-select-none {{ $table['protected'] ? 'db-table-protected' : '' }}">
                                        <input type="checkbox" name="tables[]" value="{{ $table['name'] }}"
                                               class="form-check-input checkbox--input db-table-checkbox m-0"
                                               id="business_section_{{ $key }}"
                                               {{ $table['protected'] ? 'disabled' : '' }}>
                                        <span class="db-table-name">{{ translate(str_replace('_', ' ', $table['name'])) }}</span>
                                        @if($table['protected'])
                                            <span class="db-table-count text-muted d-flex align-items-center" data-bs-toggle="tooltip"
                                                  data-bs-placement="top" title="{{ translate('System table cannot be cleaned') }}">
                                                <i class="fi fi-sr-lock d-flex"></i>
                                            </span>
                                        @else
                                            <span class="db-table-count badge bg-info bg-opacity-10 fs-12 text-info px-2 py-1 rounded">{{ number_format($table['count']) }}</span>
                                        @endif
                                    </label>
                                </div>
                            @endforeach
                        </div>
                        <div class="text-center text-muted py-4 db-empty-search" id="db-no-result">{{ translate('No table found') }}</div>
                    </div>
                    <div class="d-flex justify-content-end trans3 mt-4 action-btn-wrapper-container">
                        <div class="d-flex align-items-center justify-content-sm-end justify-content-center flex-wrap gap-3 flex-grow-1 flex-grow-sm-0 bg-white action-btn-wrapper trans3">
                            <span class="fs-14 fw-semibold text-dark text-nowrap me-sm-auto">
                                <span data-metric="selected">0</span> {{ translate('Tables selected') }}
                            </span>
                            <button type="reset" class="btn btn-secondary px-4">{{ translate('Reset') }}</button>
                            @if(config('app.mode') != 'demo')
                                <button type="button" class="btn btn-danger px-4" data-bs-toggle="modal" data-bs-target="#cleanDatabaseModal">
                                    {{ translate('Clear') }}
                                </button>
                            @else
                                <button type="button" class="btn btn-danger px-4 {{ getDemoModeFormButton(type: 'class') }}">
                                    {{ translate('Clear') }}
                                </button>
                            @endif
                        </div>
                    </div>

                    <div class="modal fade" id="cleanDatabaseModal" tabindex="-1" aria-labelledby="cleanDatabaseModal" aria-hidden="true">
                        <div class="modal-dialog modal-dialog-centered">
                            <div class="modal-content">
                                <div class="modal-header border-0 pb-0 d-flex justify-content-end">
                                    <button type="button" class="btn-close border-0 btn-circle bg-section2 shadow-none" data-bs-dismiss="modal" aria-label="Close"></button>
                                </div>
                                <div class="modal-body px-20 py-0 mb-30">
                                    <div class="d-flex flex-column align-items-center text-center mb-30">
                                        <img src="{{ dynamicAsset(path: 'public/assets/new/back-end/img/modal/delete.png') }}" width="80" class="mb-20" alt="">
                                        <h2 class="modal-title mb-3">{{ translate('Want to clean database').' ?' }}</h2>
                                        <div class="text-center">
                                            {{ translate('Are you sure you want to clean the selected database').' ?' }}
                                            {{ translate('It will be permanently deleted from your database') }}
                                        </div>
                                    </div>
                                    <div class="d-flex justify-content-center gap-3">
                                        <button type="{{ getDemoModeFormButton(type: 'button') }}"
                                                class="btn btn-danger max-w-120 flex-grow-1 {{ getDemoModeFormButton(type: 'class') }}"
                                                data-bs-dismiss="modal">
                                            {{ translate('Yes Delete') }}
                                        </button>
                                        <button type="button" class="btn btn-secondary max-w-120 flex-grow-1" data-bs-dismiss="modal">
                                            {{ translate('No') }}
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <span class="clean-database-form-msg"
          data-warning="{{ translate('Warning') }}!"
          data-warning-msg="{{ translate('Please select any checkbox first') }}!"></span>

    @include("layouts.admin.partials.offcanvas._clean-database")
@endsection

@push('script')
    <script src="{{ dynamicAsset(path: 'public/assets/backend/admin/js/system-setup/system-setup.js') }}"></script>
    <script>
        "use strict";
        (function () {
            const grid = document.getElementById('db-table-grid');
            // Only selectable (non-protected) checkboxes participate in select/count.
            const checkboxes = () => [...grid.querySelectorAll('.db-table-checkbox:not(:disabled)')];
            const selectedEls = document.querySelectorAll('[data-metric="selected"]');
            const selectAll = document.getElementById('db-select-all');

            function refreshCount() {
                const n = checkboxes().filter(c => c.checked).length;
                selectedEls.forEach(el => el.textContent = n);
            }

            grid.addEventListener('change', function (e) {
                if (e.target.classList.contains('db-table-checkbox')) {
                    refreshCount();
                    const boxes = checkboxes();
                    selectAll.checked = boxes.length && boxes.every(c => c.checked);
                }
            });

            selectAll.addEventListener('change', function () {
                checkboxes().forEach(c => {
                    // only toggle rows currently visible (respect the search filter)
                    if (c.closest('.db-table-col').style.display !== 'none') {
                        c.checked = selectAll.checked;
                    }
                });
                refreshCount();
            });

            const search = document.getElementById('db-table-search');
            const noResult = document.getElementById('db-no-result');
            search.addEventListener('input', function () {
                const term = this.value.trim().toLowerCase();
                let visible = 0;
                grid.querySelectorAll('.db-table-col').forEach(col => {
                    const match = col.dataset.tableName.includes(term);
                    col.style.display = match ? '' : 'none';
                    if (match) visible++;
                });
                noResult.style.display = visible ? 'none' : 'block';
            });

            document.querySelector('.clean-database-form').addEventListener('reset', function () {
                setTimeout(() => { selectAll.checked = false; refreshCount(); }, 0);
            });
        })();
    </script>
@endpush
