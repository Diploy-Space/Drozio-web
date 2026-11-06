@extends('layouts.admin.app')
@section('title', translate('Contact List'))

@push('css_or_js')
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="stylesheet" href="{{ dynamicAsset(path: 'public/assets/back-end/css/admin/contact.css') }}">
@endpush

@section('content')
    <div class="content container-fluid">
        <div class="mb-3">
            <h2 class="h1 mb-0 text-capitalize d-flex align-items-center gap-2">
                <img width="20" src="{{dynamicAsset(path: 'public/assets/back-end/img/message.png')}}" alt="">
                {{translate('Customer message')}}
            </h2>
        </div>

        <div class="row row-cols-2 row-cols-xl-4 g-3 mb-3" id="contact-overview-cards">
            <div class="col">
                <div class="card h-100 contact-overview-card">
                    <div class="card-body d-flex align-items-center gap-3">
                        <div class="stat-icon bg-primary bg-opacity-10 text-primary rounded d-flex align-items-center justify-content-center">
                            <i class="fi fi-sr-inbox fs-5 d-flex"></i>
                        </div>
                        <div class="min-w-0">
                            <h3 class="mb-0 fw-bold contact-overview-value">{{ $overview['total'] }}</h3>
                            <span class="text-muted fs-12 text-capitalize">{{ translate('Total messages') }}</span>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col">
                <div class="card h-100 contact-overview-card">
                    <div class="card-body d-flex align-items-center gap-3">
                        <div class="stat-icon bg-success bg-opacity-10 text-success rounded d-flex align-items-center justify-content-center">
                            <i class="fi fi-sr-check-circle fs-5 d-flex"></i>
                        </div>
                        <div class="min-w-0">
                            <h3 class="mb-0 fw-bold contact-overview-value">{{ $overview['replied'] }}</h3>
                            <span class="text-muted fs-12 text-capitalize">{{ translate('Reply sent') }}</span>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col">
                <div class="card h-100 contact-overview-card">
                    <div class="card-body d-flex align-items-center gap-3">
                        <div class="stat-icon bg-warning bg-opacity-10 text-warning rounded d-flex align-items-center justify-content-center">
                            <i class="fi fi-sr-clock fs-5 d-flex"></i>
                        </div>
                        <div class="min-w-0">
                            <h3 class="mb-0 fw-bold contact-overview-value">{{ $overview['notReplied'] }}</h3>
                            <span class="text-muted fs-12 text-capitalize">{{ translate('Reply not sent') }}</span>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col">
                <div class="card h-100 contact-overview-card">
                    <div class="card-body d-flex align-items-center gap-3">
                        <div class="stat-icon bg-info bg-opacity-10 text-info rounded d-flex align-items-center justify-content-center">
                            <i class="fi fi-sr-envelope fs-5 d-flex"></i>
                        </div>
                        <div class="min-w-0">
                            <h3 class="mb-0 fw-bold contact-overview-value">{{ $overview['unread'] }}</h3>
                            <span class="text-muted fs-12 text-capitalize">{{ translate('Unread') }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card mt-4">
            <div class="card-body">
                <div class="row justify-content-between align-items-center flex-grow-1">
                    <div class="col-sm-4 col-md-6 col-lg-6 mb-2 mb-sm-0">
                        <h3 class="d-flex gap-2 align-items-center text-capitalize">
                            {{translate('Customer message table')}}
                            <span class="badge text-bg-info badge-info fs-12" id="row-count">{{ $contacts->total() }}
                            </span>
                        </h3>
                    </div>
                    <div class="col-sm-8 col-md-6 col-lg-6">
                        <div class="d-flex flex-wrap flex-md-nowrap gap-2">
                            <form action="{{ url()->current() }}" method="GET" class="flex-grow-1">
                                <div class="form-group">
                                    <div class="input-group">
                                        <input type="search" name="searchValue" class="form-control" value="{{ request('searchValue') }}" placeholder="{{translate('Search by name, email or phone')}}">
                                        <div class="input-group-append search-submit">
                                            <button type="submit">
                                                <i class="fi fi-rr-search"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </form>
                            <div class="dropdown">
                                <a class="btn btn-primary" data-bs-toggle="dropdown" href="javascript:">
                                    <i class="fi fi-sr-settings-sliders"></i> {{ translate('Filter') }}
                                </a>

                                <div id="menu" class="dropdown-menu dropdown-menu-sm-right px-3 py-4">
                                    <div class="d-flex justify-content-between align-items-center gap-2 mb-3">
                                        <span>
                                            {{ translate('Reply sent') }}
                                        </span>
                                        <label class="switcher">
                                            <input type="checkbox" class="switcher_input status-filter" name="reply_status" value="replied">
                                            <span class="switcher_control"></span>
                                        </label>
                                    </div>
                                    <div class="d-flex justify-content-between align-items-center gap-2">
                                        <span>
                                            {{ translate('Reply not sent') }}
                                        </span>
                                        <label class="switcher">
                                            <input type="checkbox" class="switcher_input status-filter" name="reply_status" value="not_replied">
                                            <span class="switcher_control"></span>
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>

                    </div>
                </div>

                <div id="status-wise-view" class="mt-4">
                    @include('admin-views.contacts._table')
                </div>
            </div>
        </div>
    </div>
    <span id="get-filter-route" data-action="{{route('admin.people.contact.filter')}}"></span>
@endsection

@push('script')
    <script src="{{dynamicAsset(path: 'public/assets/back-end/js/admin/contact.js')}}"></script>
@endpush
