@extends('layouts.admin.app')

@section('title', translate('Messages'))

@push('css_or_js')
    <link rel="stylesheet" href="{{ dynamicAsset(path: 'public/assets/new/back-end/libs/owl-carousel/owl.carousel.min.css') }}"/>
@endpush

@section('content')
    <div class="content container-fluid chat-workspace">
        <div class="mb-4">
            <h2 class="h1 mb-0 text-capitalize d-flex align-items-center gap-2">
                <img width="20" alt=""
                     src="{{ dynamicAsset(path: 'public/assets/back-end/img/support-ticket.png') }}">
                {{ translate('Messages') }}
            </h2>
        </div>

        <div class="row g-3">
            <div class="col-xl-3 col-lg-4 chatSel">
                <div class="card chat-panel">
                    <div class="p-3 border-bottom d-flex flex-column gap-3">
                        <ul class="nav nav-pills nav--tab gap-1">
                            <li class="nav-item flex-grow-1">
                                <a class="nav-link justify-content-center {{ $userType === 'delivery-man' ? '' : 'active' }}"
                                   href="{{ route('admin.people.messages.index', ['type' => 'customer']) }}">
                                    {{ translate('Customers') }}
                                    @if($unreadTotals['customer'] > 0)
                                        <span class="chat-tab-count">{{ $unreadTotals['customer'] }}</span>
                                    @endif
                                </a>
                            </li>
                            <li class="nav-item flex-grow-1">
                                <a class="nav-link justify-content-center {{ $userType === 'delivery-man' ? 'active' : '' }}"
                                   href="{{ route('admin.people.messages.index', ['type' => 'delivery-man']) }}">
                                    {{ translate('Delivery men') }}
                                    @if($unreadTotals['delivery-man'] > 0)
                                        <span class="chat-tab-count">{{ $unreadTotals['delivery-man'] }}</span>
                                    @endif
                                </a>
                            </li>
                        </ul>

                        <form class="search-form" id="chat-search-form">
                            <div class="form-group mb-0">
                                <div class="input-group">
                                    <input type="search" class="form-control" id="myInput"
                                           placeholder="{{ $userType === 'delivery-man' ? translate('Search delivery men') : translate('Search customers') }}...">
                                    <div class="input-group-append search-submit">
                                        <button type="submit">
                                            <i class="fi fi-rr-search"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>

                    <div class="inbox_chat flex-grow-1 overflow-y-auto">
                        @forelse($conversations as $conversation)
                            @include('admin-views.chatting.partials._conversation-row', ['conversation' => $conversation])
                        @empty
                            @include('layouts.admin.partials._empty-state-svg', [
                                'image' => $userType === 'delivery-man' ? 'deliveryman' : 'no-customer-found',
                                'text' => $userType === 'delivery-man'
                                    ? 'No delivery man conversation yet'
                                    : 'No customer conversation yet',
                            ])
                        @endforelse

                        <div class="empty-state-for-chatting-msg d-none">
                            @include('layouts.admin.partials._empty-state-svg', [
                                'image' => 'no-result-found',
                                'text' => 'No result found',
                            ])
                        </div>
                    </div>
                </div>
            </div>

            <section class="col-xl-9 col-lg-8">
                <div class="card chat-panel">
                    @if(isset($activeUser))
                        <div class="d-flex flex-wrap gap-3 justify-content-between align-items-center border-bottom p-3">
                            <div class="d-flex align-items-center gap-3">
                                <img width="40" class="avatar avatar-circle object-fit-cover flex-shrink-0"
                                     id="profile_image" src="{{ $activeUser['image'] }}" alt="">
                                <div>
                                    <a class="fw-semibold text-dark text-hover-primary d-block" id="profile_name"
                                       href="{{ $activeUser['detailsRoute'] }}" target="_blank">
                                        {{ $activeUser['name'] }}
                                    </a>
                                    <a class="fs-12 text-body-light" id="profile_phone"
                                       href="tel:{{ str_replace(' ', '', $activeUser['phone']) }}">
                                        {{ $activeUser['phone'] }}
                                    </a>
                                </div>
                            </div>

                            <a class="btn btn-outline-primary btn-sm user-details-route" target="_blank"
                               href="{{ $activeUser['detailsRoute'] }}">
                                <i class="fi fi-rr-user"></i>
                                {{ translate('View details') }}
                            </a>
                        </div>

                        <div class="card-body p-3 overflow-y-auto flex-grow-1 msg_history d-flex flex-column-reverse"
                             id="chatting-messages-section">
                            @include('admin-views.chatting.messages', [
                                'counterparty' => $activeUser,
                                'chattingMessages' => $chattingMessages,
                            ])
                        </div>

                        @include('admin-views.chatting.partials._composer', [
                            'counterparty' => $activeUser,
                            'userType' => $userType,
                        ])
                    @else
                        <div class="d-flex justify-content-center align-items-center flex-grow-1">
                            @include('layouts.admin.partials._empty-state-svg', [
                                'image' => $userType === 'delivery-man' ? 'deliveryman' : 'no-customer-found',
                                'text' => count($conversations) > 0
                                    ? 'Select a conversation to start messaging'
                                    : 'No conversation to show yet',
                            ])
                        </div>
                    @endif
                </div>
            </section>
        </div>

        <span id="chatting-post-url"
              data-param="{{ $userType === 'delivery-man' ? 'delivery_man_id' : 'user_id' }}"
              data-url="{{ route('admin.people.messages.message') }}?{{ $userType === 'delivery-man' ? 'delivery_man_id' : 'user_id' }}="></span>
        <span id="exceeds10MBSizeLimit" data-text="{{ translate('File exceeds 10MB size limit') }}"></span>
        <span id="chatting-row-labels" data-you="{{ translate('You') }}"
              data-just-now="{{ translate('Just now') }}"
              data-attachment="{{ translate('Sent an attachment') }}"
              data-empty="{{ translate('Write a message or attach a file') }}"></span>
        <span id="chatting-upload-progress" data-uploading="{{ translate('Uploading files') }}"
              data-uploaded="{{ translate('Files uploaded') }}"></span>
        <span id="get-file-icon"
              data-default-icon="{{ dynamicAsset(path: 'public/assets/back-end/img/default-icon.png') }}"
              data-word-icon="{{ dynamicAsset(path: 'public/assets/back-end/img/default-icon.png') }}"></span>
        <span id="message-media-error"
              data-text="{{ translate('File size is too large') }} {{ translate('Please upload a smaller file') }}"></span>
        <span id="get-video-preview-icon"
              data-icon="{{ dynamicAsset(path: 'public/assets/back-end/img/icons/carbon_play-filled.svg') }}"></span>
    </div>
@endsection

@push('script')
    <script src="{{ dynamicAsset(path: 'public/assets/back-end/js/js-zip/jszip.min.js') }}"></script>
    <script src="{{ dynamicAsset(path: 'public/assets/back-end/js/js-zip/FileSaver.min.js') }}"></script>
    <script src="{{ dynamicAsset(path: 'public/assets/back-end/js/admin/chatting.js') }}"></script>
    <script src="{{ dynamicAsset(path: 'public/assets/back-end/js/message-attachments.js') }}"></script>
    <script src="{{ dynamicAsset(path: 'public/assets/back-end/js/picmo-emoji.js') }}"></script>
    <script src="{{ dynamicAsset(path: 'public/assets/back-end/js/emoji.js') }}"></script>
    <script src="{{ dynamicAsset(path: 'public/assets/back-end/js/select-multiple-file.js') }}"></script>
    <script src="{{ dynamicAsset(path: 'public/assets/back-end/js/select-multiple-image-for-message.js') }}"></script>
    <script src="{{ dynamicAsset(path: 'public/assets/new/back-end/libs/owl-carousel/owl.carousel.min.js') }}"></script>
@endpush
