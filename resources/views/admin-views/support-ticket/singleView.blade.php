@extends('layouts.admin.app')

@section('title', translate('Support ticket'))

@push('css_or_js')
    <link rel="stylesheet" href="{{ dynamicAsset(path: 'public/assets/new/back-end/libs/owl-carousel/owl.carousel.min.css') }}"/>
    <link rel="stylesheet" href="{{ dynamicAsset(path: 'public/assets/back-end/css/admin/support-ticket.css') }}">
@endpush

@section('content')
    <div class="content container-fluid chat-workspace">
        <div class="mb-4 d-flex align-items-center gap-3 min-w-0">
            <a class="btn btn-outline-secondary icon-btn flex-shrink-0"
               href="{{ route('admin.people.support-ticket.view') }}"
               data-bs-toggle="tooltip" data-bs-title="{{ translate('Back to ticket list') }}">
                <i class="fi fi-rr-angle-left"></i>
            </a>
            <div class="min-w-0">
                <h2 class="h1 mb-1 d-flex align-items-center gap-2 min-w-0">
                    <span class="text-body-light flex-shrink-0">#{{ $ticket['id'] }}</span>
                    <span class="line-1">{{ $ticket['subject'] }}</span>
                </h2>
                <span class="fs-12 text-body-light d-flex align-items-center gap-1">
                    <i class="fi fi-rr-clock d-flex"></i>
                    {{ translate('Opened') }} {{ $ticket['openedAt'] }}
                </span>
            </div>
        </div>

        <div class="row g-3">
            <div class="col-xl-4 ticket-side-column">
                <div class="card mb-3 ticket-status-card">
                    <div class="card-body">
                        <h3 class="fs-16 mb-3">{{ translate('Ticket status') }}</h3>

                        <form action="{{ route('admin.people.support-ticket.status') }}" method="post"
                              id="support-ticket-status-form" class="no-reload-form">
                            @csrf
                            <input type="hidden" name="id" value="{{ $ticket['id'] }}">
                            <div class="d-flex align-items-center justify-content-between gap-3 bg-section rounded p-3">
                                <div class="min-w-0">
                                    <span class="badge {{ $ticket['statusBadge'] }} fw-semibold mb-1">
                                        {{ $ticket['statusLabel'] }}
                                    </span>
                                    <span class="fs-12 text-body-light d-block">
                                        {{ $ticket['isOpen']
                                            ? translate('Turn off to mark this ticket resolved')
                                            : translate('Turn on to reopen this ticket') }}
                                    </span>
                                </div>
                                <label class="switcher mb-0 flex-shrink-0">
                                    <input class="switcher_input custom-modal-plugin" type="checkbox" name="status"
                                           value="{{ $ticket['isOpen'] ? 'close' : 'open' }}" {{ $ticket['isOpen'] ? 'checked' : '' }}
                                           data-modal-type="input-change-form"
                                           data-modal-form="#support-ticket-status-form"
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
                </div>

                <div class="card mb-3">
                    <div class="card-body">
                        <h3 class="fs-16 mb-3">{{ translate('Customer') }}</h3>

                        <div class="d-flex align-items-center gap-3">
                            <img width="48" class="avatar avatar-circle object-fit-cover flex-shrink-0"
                                 src="{{ $ticket['customerImage'] }}" alt="">
                            <div class="min-w-0">
                                @if($ticket['customerRoute'])
                                    <a class="fw-semibold text-dark text-hover-primary d-block line-1"
                                       href="{{ $ticket['customerRoute'] }}" target="_blank">
                                        {{ $ticket['customerName'] }}
                                    </a>
                                @else
                                    <span class="fw-semibold d-block line-1">{{ $ticket['customerName'] }}</span>
                                @endif
                                @if($ticket['customerEmail'])
                                    <a class="fs-12 text-body-light line-1 d-block"
                                       href="mailto:{{ $ticket['customerEmail'] }}">{{ $ticket['customerEmail'] }}</a>
                                @endif
                            </div>
                        </div>

                        @if($ticket['customerPhone'])
                            <div class="d-flex justify-content-between align-items-center gap-2 pt-3 mt-3 border-top">
                                <span class="fs-12 text-body-light">{{ translate('Phone') }}</span>
                                <a class="fs-12 text-dark" href="tel:{{ $ticket['customerPhone'] }}">
                                    {{ $ticket['customerPhone'] }}
                                </a>
                            </div>
                        @endif
                    </div>
                </div>

                <div class="card">
                    <div class="card-body">
                        <h3 class="fs-16 mb-3">{{ translate('Ticket details') }}</h3>

                        <div class="d-flex justify-content-between align-items-center gap-2 pb-2">
                            <span class="fs-12 text-body-light">{{ translate('Type') }}</span>
                            <span class="badge badge-soft-dark fw-semibold">{{ $ticket['typeLabel'] }}</span>
                        </div>
                        <div class="d-flex justify-content-between align-items-center gap-2 py-2 border-top">
                            <span class="fs-12 text-body-light">{{ translate('Priority') }}</span>
                            <span class="badge {{ $ticket['priorityBadge'] }} fw-semibold">{{ $ticket['priorityLabel'] }}</span>
                        </div>
                        <div class="d-flex justify-content-between align-items-center gap-2 pt-2 border-top">
                            <span class="fs-12 text-body-light">{{ translate('Opened') }}</span>
                            <span class="fs-12 text-dark">{{ $ticket['openedAt'] }}</span>
                        </div>
                    </div>
                </div>
            </div>

            <section class="col-xl-8">
                <div class="card ticket-thread-card chat-panel">
                    <div class="card-header py-3">
                        <h3 class="fs-16 mb-0 d-flex align-items-center gap-2">
                            <i class="fi fi-rr-comment-alt d-flex"></i>
                            {{ translate('Conversation') }}
                        </h3>
                    </div>

                    <div class="card-body p-3 overflow-y-auto msg_history d-flex flex-column-reverse ticket-thread"
                         id="show_msg">
                        @foreach($messages as $message)
                            @include('admin-views.support-ticket.partials._message', ['message' => $message])
                        @endforeach
                    </div>

                    @include('admin-views.support-ticket.partials._composer')
                </div>
            </section>
        </div>

        <span id="get-file-icon"
              data-default-icon="{{ dynamicAsset(path: 'public/assets/back-end/img/default-icon.png') }}"
              data-word-icon="{{ dynamicAsset(path: 'public/assets/back-end/img/word-icon.png') }}"></span>
        <span id="get-video-preview-icon"
              data-icon="{{ dynamicAsset(path: 'public/assets/back-end/img/icons/carbon_play-filled.svg') }}"></span>
    </div>
@endsection

@push('script')
    <script src="{{ dynamicAsset(path: 'public/assets/new/back-end/libs/owl-carousel/owl.carousel.min.js') }}"></script>
    <script src="{{ dynamicAsset(path: 'public/assets/back-end/js/js-zip/jszip.min.js') }}"></script>
    <script src="{{ dynamicAsset(path: 'public/assets/back-end/js/js-zip/FileSaver.min.js') }}"></script>
    <script src="{{ dynamicAsset(path: 'public/assets/back-end/js/message-attachments.js') }}"></script>
    <script src="{{ dynamicAsset(path: 'public/assets/back-end/js/select-multiple-file.js') }}"></script>
    <script src="{{ dynamicAsset(path: 'public/assets/back-end/js/select-multiple-image-for-message.js') }}"></script>
    <script src="{{ dynamicAsset(path: 'public/assets/back-end/js/admin/support-ticket-thread.js') }}"></script>
@endpush
