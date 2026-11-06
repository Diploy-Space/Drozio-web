@extends('layouts.admin.app')

@section('title', translate('Banner'))

@section('content')
    <div class="content container-fluid">
        <div class="d-flex justify-content-between align-items-center gap-3 mb-3">
            <h2 class="h1 mb-0 text-capitalize d-flex align-items-center gap-2 flex-wrap">
                <img width="20" src="{{ dynamicAsset(path: 'public/assets/new/back-end/img/banner.png') }}" alt="">
                {{ translate('Banner Setup') }}
                <small>
                    <strong class="text-primary text-capitalize">({{ str_replace('_', ' ', theme_root_path()) }})</strong>
                </small>
            </h2>
            @if (theme_root_path() == 'theme_aster')
                <div class="d-flex align-items-center gap-2 flex-wrap ms-auto">
                    <div class="btn-group">
                        <div class="ripple-animation" aria-haspopup="true" aria-expanded="false">
                            <svg role="button" data-bs-toggle="offcanvas" data-bs-target="#bannerSetupGuide"
                                 xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 18 18" fill="none"
                                 class="svg replaced-svg">
                                <path
                                    d="M9.00033 9.83268C9.23644 9.83268 9.43449 9.75268 9.59449 9.59268C9.75449 9.43268 9.83421 9.2349 9.83366 8.99935V5.64518C9.83366 5.40907 9.75366 5.21463 9.59366 5.06185C9.43366 4.90907 9.23588 4.83268 9.00033 4.83268C8.76421 4.83268 8.56616 4.91268 8.40616 5.07268C8.24616 5.23268 8.16644 5.43046 8.16699 5.66602V9.02018C8.16699 9.25629 8.24699 9.45074 8.40699 9.60352C8.56699 9.75629 8.76477 9.83268 9.00033 9.83268ZM9.00033 13.166C9.23644 13.166 9.43449 13.086 9.59449 12.926C9.75449 12.766 9.83421 12.5682 9.83366 12.3327C9.83366 12.0966 9.75366 11.8985 9.59366 11.7385C9.43366 11.5785 9.23588 11.4988 9.00033 11.4993C8.76421 11.4993 8.56616 11.5793 8.40616 11.7393C8.24616 11.8993 8.16644 12.0971 8.16699 12.3327C8.16699 12.5688 8.24699 12.7668 8.40699 12.9268C8.56699 13.0868 8.76477 13.1666 9.00033 13.166ZM9.00033 17.3327C7.84755 17.3327 6.76421 17.1138 5.75033 16.676C4.73644 16.2382 3.85449 15.6446 3.10449 14.8952C2.35449 14.1452 1.76088 13.2632 1.32366 12.2493C0.886437 11.2355 0.667548 10.1521 0.666992 8.99935C0.666992 7.84657 0.885881 6.76324 1.32366 5.74935C1.76144 4.73546 2.35505 3.85352 3.10449 3.10352C3.85449 2.35352 4.73644 1.7599 5.75033 1.32268C6.76421 0.88546 7.84755 0.666571 9.00033 0.666016C10.1531 0.666016 11.2364 0.884905 12.2503 1.32268C13.2642 1.76046 14.1462 2.35407 14.8962 3.10352C15.6462 3.85352 16.24 4.73546 16.6778 5.74935C17.1156 6.76324 17.3342 7.84657 17.3337 8.99935C17.3337 10.1521 17.1148 11.2355 16.677 12.2493C16.2392 13.2632 15.6456 14.1452 14.8962 14.8952C14.1462 15.6452 13.2642 16.2391 12.2503 16.6768C11.2364 17.1146 10.1531 17.3332 9.00033 17.3327ZM9.00033 15.666C10.8475 15.666 12.4206 15.0168 13.7195 13.7185C15.0184 12.4202 15.6675 10.8471 15.667 8.99935C15.667 7.15213 15.0178 5.57907 13.7195 4.28018C12.4212 2.98129 10.8481 2.33213 9.00033 2.33268C7.1531 2.33268 5.58005 2.98185 4.28116 4.28018C2.98227 5.57852 2.3331 7.15157 2.33366 8.99935C2.33366 10.8466 2.98283 12.4196 4.28116 13.7185C5.57949 15.0174 7.15255 15.6666 9.00033 15.666Z"
                                    fill="currentColor"></path>
                            </svg>
                        </div>
                    </div>
                </div>
            @endif
        </div>

        <div class="row" id="banner-table">
            <div class="col-md-12">
                <div class="card">
                    <div class="px-3 py-4">
                        <div class="d-flex flex-wrap justify-content-between align-items-center gap-3">
                            <h3 class="mb-0">
                                {{ translate('Banner list') }}
                                <span class="badge text-dark bg-body-secondary fw-semibold rounded-50 mx-1">
                                    {{ $banners->total() }}
                                </span>
                            </h3>
                            <div class="d-flex flex-wrap gap-3 align-items-center justify-content-lg-end">
                            <div class="min-w-300 min-w-100-mobile">
                                <form action="{{ url()->current() }}" method="GET">
                                    @foreach ($preservedFilterInputs as $preservedFilterInput)
                                        @if ($preservedFilterInput['name'] !== 'searchValue')
                                            <input type="hidden" name="{{ $preservedFilterInput['name'] }}"
                                                   value="{{ $preservedFilterInput['value'] }}">
                                        @endif
                                    @endforeach
                                    <div class="input-group">
                                        <input id="datatableSearch_" type="search" name="searchValue"
                                               class="form-control"
                                               placeholder="{{ translate('Search by banner link or type') }}"
                                               aria-label="{{ translate('Search') }}"
                                               value="{{ request('searchValue') }}">
                                        <div class="input-group-append search-submit">
                                            <button type="submit">
                                                <i class="fi fi-rr-search"></i>
                                            </button>
                                        </div>
                                    </div>
                                </form>
                            </div>
                                <div class="position-relative">
                                    <button type="button" class="btn btn-primary" data-bs-toggle="offcanvas"
                                            data-bs-target="#offcanvasBannerFilter">
                                        <i class="fi fi-sr-settings-sliders d-flex"></i> {{ translate('Filter') }}
                                    </button>
                                    @if ($hasActiveFilters)
                                        <div class="position-absolute top-n1 inset-inline-end-n1 btn-circle bg-danger border border-white border-2"
                                             style="--size: 12px;"></div>
                                    @endif
                                </div>
                                <div id="banner-btn">
                                    <a class="btn btn-primary text-nowrap text-capitalize"
                                       data-bs-toggle="offcanvas" href="#bannerAddOffcanvas" role="button">
                                        <i class="fi fi-sr-plus"></i>
                                        {{ translate('Add banner') }}
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-hover table-borderless table-thead-bordered align-middle">
                            <thead class="text-capitalize">
                            <tr>
                                <th>{{ translate('SL') }}</th>
                                <th>{{ translate('Image') }}</th>
                                <th>{{ translate('Banner') }}</th>
                                <th>{{ translate('Resource') }}</th>
                                <th class="text-center">{{ translate('Published') }}</th>
                                <th class="text-center">{{ translate('Action') }}</th>
                            </tr>
                            </thead>
                            <tbody>
                            @foreach ($banners as $key => $banner)
                                <tr id="data-{{ $banner['id'] }}">
                                    <th scope="row">{{ $banners->firstItem() + $key }}</th>
                                    <td>
                                        <img class="ratio-4-2 object-fit-cover border rounded" width="80" alt=""
                                             src="{{ getStorageImages(path: $banner->photo_full_url, type: 'backend-banner') }}">
                                    </td>
                                    <td>
                                        <div class="max-w-400 min-w-130">
                                            <span class="badge badge-soft-dark fs-12 fw-semibold">
                                                {{ $bannerTypes[$banner['banner_type']] ?? $banner['banner_type'] }}
                                            </span>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="max-w-200">
                                            <div class="text-dark">
                                                {{ $resourceTypes[$banner['resource_type']] ?? $banner['resource_type'] }}
                                            </div>
                                            @if (!empty($banner['url']))
                                                <a class="fs-12 text-info-dark d-inline-flex align-items-center gap-1 mt-1"
                                                   href="{{ $banner['url'] }}" target="_blank" rel="noopener"
                                                   data-bs-toggle="tooltip" title="{{ $banner['url'] }}">
                                                    <i class="fi fi-rr-arrow-up-right-from-square d-flex"></i>
                                                    {{ translate('View link') }}
                                                </a>
                                            @endif
                                        </div>
                                    </td>
                                    <td class="text-center">
                                        <form action="{{ route('admin.retail.banner.status') }}" method="post"
                                              id="banner-status{{ $banner['id'] }}-form" class="no-reload-form reload-true">
                                            @csrf
                                            <input type="hidden" name="id" value="{{ $banner['id'] }}">
                                            <label class="switcher mx-auto" for="banner-status{{ $banner['id'] }}">
                                                <input class="switcher_input custom-modal-plugin"
                                                       type="checkbox" value="1" name="status"
                                                       id="banner-status{{ $banner['id'] }}"
                                                       {{ $banner['published'] == 1 ? 'checked' : '' }}
                                                       data-modal-type="input-change-form"
                                                       data-modal-form="#banner-status{{ $banner['id'] }}-form"
                                                       data-on-image="{{ dynamicAsset(path: 'public/assets/new/back-end/img/modal/banner-status-on.png') }}"
                                                       data-off-image="{{ dynamicAsset(path: 'public/assets/new/back-end/img/modal/banner-status-off.png') }}"
                                                       data-on-title="{{ translate('Want to turn ON').' '.($bannerTypes[$banner['banner_type']] ?? $banner['banner_type']) }}"
                                                       data-off-title="{{ translate('Want to turn OFF').' '.($bannerTypes[$banner['banner_type']] ?? $banner['banner_type']) }}"
                                                       data-on-message="<p>{{ translate('Shows this banner on the website and app.') }}</p>"
                                                       data-off-message="<p>{{ translate('Hides this banner on the website and app.') }}</p>"
                                                       data-on-button-text="{{ translate('Turn on') }}"
                                                       data-off-button-text="{{ translate('Turn off') }}">
                                                <span class="switcher_control"></span>
                                            </label>
                                        </form>
                                    </td>
                                    <td>
                                        <div class="d-flex gap-2 justify-content-center">
                                            <a class="btn btn-outline-info top01 icon-btn edit"
                                               title="{{ translate('Edit') }}"
                                               data-bs-toggle="offcanvas" href="#bannerEditOffcanvas-{{ $banner['id'] }}"
                                               role="button">
                                                <i class="fi fi-sr-pencil"></i>
                                            </a>
                                            <a class="btn btn-outline-danger top01 icon-btn banner-delete-button"
                                               title="{{ translate('Delete') }}"
                                               id="{{ $banner['id'] }}">
                                                <i class="fi fi-rr-trash"></i>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                    </div>

                    @if (count($banners) == 0)
                        @include('layouts.admin.partials._empty-state', ['text' => 'no_banner_found'], ['image' => 'default'])
                    @endif

                    <div class="table-responsive mt-4">
                        <div class="px-4 d-flex justify-content-lg-end">
                            {{ $banners->links() }}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <span id="route-admin-banner-store" data-url="{{ route('admin.retail.banner.store') }}"></span>
    <span id="route-admin-banner-delete" data-url="{{ route('admin.retail.banner.delete') }}"></span>
    <span id="message-banner-not-found" data-text="{{ translate('Not found') }}"></span>
    <span id="theme-ratio-map" data-theme="{{ theme_root_path() }}" data-map="{{ json_encode(THEME_RATIO) }}"></span>

    @include('admin-views.banner.offcanvas._filter-offcanvas')
    @include('admin-views.banner.offcanvas._banner-add')

    @foreach ($banners as $banner)
        @include('admin-views.banner.offcanvas._banner-edit', ['banner' => $banner])
    @endforeach

    @include('admin-views.banner.off-canvas')
@endsection

@push('script')
    <script src="{{ dynamicAsset(path: 'public/assets/backend/admin/js/promotion/banner.js') }}"></script>
    @if($errors->any())
        <script>
            "use strict";
            $(function () {
                bootstrap.Offcanvas.getOrCreateInstance(document.getElementById('bannerAddOffcanvas')).show();
            });
        </script>
    @endif
@endpush
