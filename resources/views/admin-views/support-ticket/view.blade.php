@extends('layouts.admin.app')

@section('title', translate('Support ticket'))

@push('css_or_js')
    <link rel="stylesheet" href="{{ dynamicAsset(path: 'public/assets/back-end/css/admin/support-ticket.css') }}">
@endpush

@section('content')
    <div class="content container-fluid">
        <div class="mb-3">
            <h2 class="h1 mb-0 text-capitalize d-flex align-items-center gap-2">
                <img width="20" src="{{dynamicAsset(path: 'public/assets/back-end/img/support_ticket.png')}}" alt="">
                {{translate('Support ticket')}}
                <span class="badge badge-soft-dark radius-50 fz-14">{{ $tickets->total() }}</span>
            </h2>
        </div>

        <div class="card mb-3" id="support-ticket-filters">
            <div class="card-body">
                <div class="row g-3 align-items-end">
                    <div class="col-12 col-lg-5">
                        <label class="form-label fs-12 text-muted text-capitalize" for="ticket-search">
                            {{ translate('Search') }}
                        </label>
                        <form action="{{ url()->current() }}" method="GET">
                            <div class="input-group">
                                <input id="ticket-search" type="search" value="{{ request('searchValue') }}"
                                       name="searchValue" class="form-control"
                                       placeholder="{{translate('Search ticket by subject or status').'...'}}">
                                <div class="input-group-append search-submit">
                                    <button type="submit">
                                        <i class="fi fi-rr-search"></i>
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>

                    <div class="col-6 col-md">
                        <label class="form-label fs-12 text-muted text-capitalize" for="ticket-priority-filter">
                            {{ translate('Priority') }}
                        </label>
                        <div class="select-wrapper">
                            <select id="ticket-priority-filter" class="form-select fs-12 filter-tickets" data-value="priority">
                                @foreach($priorityOptions as $value => $label)
                                    <option value="{{ $value }}" {{ $selectedPriority == $value ? 'selected' : '' }}>
                                        {{ $label }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="col-6 col-md">
                        <label class="form-label fs-12 text-muted text-capitalize" for="ticket-status-filter">
                            {{ translate('Status') }}
                        </label>
                        <div class="select-wrapper">
                            <select id="ticket-status-filter" class="form-select fs-12 filter-tickets" data-value="status">
                                @foreach($statusOptions as $value => $label)
                                    <option value="{{ $value }}" {{ $selectedStatus == $value ? 'selected' : '' }}>
                                        {{ $label }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="col-12 col-md-auto">
                        <a href="{{ $resetUrl }}"
                           class="btn btn-outline-secondary w-100 min-h-40 d-flex align-items-center justify-content-center text-nowrap {{ $activeFilters ? '' : 'disabled' }}">
                            {{ translate('Reset') }}
                        </a>
                    </div>
                </div>

                @if($activeFilters)
                    <div class="d-flex flex-wrap gap-2 align-items-center mt-3 pt-3 border-top">
                        <span class="text-muted fs-12 text-capitalize">{{ translate('Filtered by') }}:</span>
                        @foreach($activeFilters as $filter)
                            <a href="{{ $filter['removeUrl'] }}"
                               class="badge badge-soft-secondary fs-12 fw-semibold d-inline-flex align-items-center gap-1 text-capitalize"
                               title="{{ translate('Remove') }}">
                                {{ $filter['label'] }}: {{ $filter['value'] }}
                                <i class="fi fi-rr-cross-small d-flex"></i>
                            </a>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>

        @if(count($tickets) == 0)
            @include('layouts.admin.partials._empty-state',['text'=>'no_support_ticket_found'],['image'=>'default'])
        @else
            <div class="row row-cols-1 row-cols-xl-2 g-3" id="support-ticket-cards">
                @foreach($tickets as $ticket)
                    <div class="col">
                        <div class="card h-100 support-ticket-card">
                            <div class="card-body d-flex flex-column gap-3">
                                <div class="d-flex justify-content-between align-items-start gap-3">
                                    <h5 class="mb-0 min-w-0 text-truncate">
                                        <span class="text-body-light">#{{ $ticket['id'] }}</span>
                                        <a class="text-dark text-hover-primary stretched-link"
                                           href="{{ route('admin.people.support-ticket.single-ticket', $ticket['id']) }}"
                                           title="{{ $ticket->subject }}">
                                            {{ $ticket->subject }}
                                        </a>
                                    </h5>

                                    <form action="{{route('admin.people.support-ticket.status')}}" method="post"
                                          id="support-ticket-{{ $ticket['id'] }}-form"
                                          class="no-reload-form flex-shrink-0 position-relative z-1">
                                        @csrf
                                        <input type="hidden" name="id" value="{{ $ticket['id'] }}">
                                        <div class="d-flex align-items-center gap-2">
                                            <span class="fs-12 text-body-light">{{ translate('Open') }}</span>
                                            <label class="switcher mb-0">
                                                <input
                                                    class="switcher_input custom-modal-plugin"
                                                    type="checkbox" name="status"
                                                    value="{{ $ticket['status'] == 'open' ? 'close' : 'open' }}"
                                                    {{ $ticket['status'] == 'open' ? 'checked':'' }}
                                                    data-modal-type="input-change-form"
                                                    data-modal-form="#support-ticket-{{ $ticket['id'] }}-form"
                                                    data-on-image="{{ dynamicAsset(path: 'public/assets/new/back-end/img/modal/support-ticket-on.png') }}"
                                                    data-off-image="{{ dynamicAsset(path: 'public/assets/new/back-end/img/modal/support-ticket-off.png') }}"
                                                    data-on-title="{{ translate('Reopen this ticket').'?' }}"
                                                    data-off-title="{{ translate('Close this ticket').'?' }}"
                                                    data-on-message="<p>{{ translate('The customer will be able to continue this conversation') }}</p>"
                                                    data-off-message="<p>{{ translate('The ticket will be marked as resolved') }}</p>">
                                                <span class="switcher_control"></span>
                                            </label>
                                        </div>
                                    </form>
                                </div>

                                <div class="d-flex gap-2 align-items-center min-w-0">
                                    <img width="32" height="32" class="rounded-circle object-fit-cover flex-shrink-0"
                                         src="{{ getStorageImages(path: $ticket->customer->image_full_url ?? '', type: 'backend-profile') }}"
                                         alt="">
                                    <div class="min-w-0 fs-12">
                                        <span class="text-dark">
                                            @if($ticket->customer)
                                                {{ $ticket->customer->f_name }} {{ $ticket->customer->l_name }}
                                            @else
                                                {{ translate('Customer not found') }}
                                            @endif
                                        </span>
                                        <span class="text-body-light d-block text-truncate">{{ $ticket->customer->email ?? '' }}</span>
                                    </div>
                                </div>

                                <p class="mb-0 text-body-light fs-12">{{ Str::limit($ticket->description, 150) }}</p>

                                <div class="d-flex flex-wrap gap-2 align-items-center mt-auto">
                                    <span class="badge {{ $statusBadges[$ticket->status] ?? 'badge-soft-secondary' }} fs-12 fw-semibold">
                                        {{ $statusOptions[$ticket->status] ?? $ticket->status }}
                                    </span>
                                    <span class="badge {{ $priorityBadges[$ticket->priority] ?? 'badge-soft-secondary' }} fs-12 fw-semibold">
                                        {{ translate('Priority') }}: {{ $priorityOptions[$ticket->priority] ?? $ticket->priority }}
                                    </span>
                                    <span class="badge badge-soft-dark fs-12 fw-semibold">{{ $ticket->type }}</span>
                                </div>

                                <div class="d-flex flex-wrap gap-2 justify-content-between align-items-center pt-2 border-top">
                                    <span class="text-body-light fs-12 d-flex align-items-center gap-1">
                                        <i class="fi fi-rr-clock d-flex"></i>
                                        {{ $ticket->created_at->format('d M Y, h:i A') }}
                                    </span>
                                    <span class="btn btn-sm btn-primary d-flex align-items-center gap-2">
                                        <i class="fi fi-rr-arrow-up-right-from-square d-flex"></i>
                                        {{translate('View')}}
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

            <div class="mt-4">
                <div class="px-4 d-flex justify-content-center justify-content-md-end">
                    {{$tickets->links()}}
                </div>
            </div>
        @endif
    </div>
@endsection

@push('script')
    <script src="{{dynamicAsset(path: 'public/assets/back-end/js/admin/support-tickets.js')}}"></script>
@endpush
