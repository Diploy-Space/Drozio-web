@extends('layouts.vendor.app')

@section('title', translate('Messages'))

@push('css_or_js')
    <link rel="stylesheet" href="{{ dynamicAsset(path: 'public/assets/back-end/css/owl.min.css') }}"/>
@endpush

@section('content')
    <div class="content container-fluid chat-workspace">
        <div class="mb-3 d-flex align-items-center gap-3 min-w-0">
            <a class="btn btn-outline-secondary icon-btn flex-shrink-0" href="{{ $inboxRoute }}"
               data-toggle="tooltip" title="{{ translate('Back to inbox') }}">
                <i class="fi fi-rr-angle-left"></i>
            </a>
            <div class="min-w-0">
                <h2 class="h1 mb-1 d-flex align-items-center gap-2 min-w-0">
                    <img width="20" alt=""
                         src="{{ dynamicAsset(path: 'public/assets/back-end/img/support-ticket.png') }}">
                    <span class="line-1">{{ $listTitle }}</span>
                </h2>
                <span class="fs-12 text-body-light d-flex align-items-center gap-1">
                    <i class="fi fi-rr-comment-alt d-flex"></i>
                    {{ count($conversations) }} {{ translate('Conversations') }}
                </span>
            </div>
        </div>

        <div class="row">
            <div class="col-xl-3 col-lg-4 chatSel">
                <div class="card chat-panel">
                    <div class="p-3 border-bottom">
                        <form class="search-form w-100" id="chat-search-form">
                            <div class="search-input-group w-100">
                                <i class="tio-search search-icon" aria-hidden="true"></i>
                                <input type="text" class="w-100" id="myInput"
                                       placeholder="{{ $searchPlaceholder }}...">
                            </div>
                        </form>
                    </div>

                    <div class="inbox_chat flex-grow-1 overflow-y-auto">
                        @foreach($conversations as $conversation)
                            @include('vendor-views.chatting.partials._conversation-row', ['conversation' => $conversation])
                        @endforeach

                        <div class="empty-state-for-chatting-msg d-none">
                            @include('layouts.vendor.partials._empty-state', [
                                'image' => 'default',
                                'text' => 'No result found',
                                'width' => 160,
                            ])
                        </div>
                    </div>
                </div>
            </div>

            <section class="col-xl-9 col-lg-8 mt-4 mt-lg-0">
                <div class="card chat-panel">
                    <div class="d-flex flex-wrap gap-3 align-items-center border-bottom p-3">
                        <div class="media align-items-center gap-3">
                            <div class="avatar avatar-sm avatar-circle border">
                                <img class="avatar-img user-avatar-image" id="profile_image"
                                     src="{{ $activeUser['image'] }}" alt="">
                            </div>
                            <div class="media-body">
                                <a class="profile-name font-weight-bold text-dark d-block mb-1" id="profile_name"
                                   href="{{ $activeUser['detailsRoute'] }}" target="_blank">
                                    {{ $activeUser['name'] }}
                                </a>
                                <a class="fs-12 text-muted text-decoration-none" id="profile_phone"
                                   href="tel:{{ str_replace(' ', '', $activeUser['phone']) }}">
                                    {{ $activeUser['phone'] }}
                                </a>
                            </div>
                        </div>
                    </div>

                    <div class="card-body p-3 overflow-y-auto flex-grow-1 msg_history d-flex flex-column-reverse"
                         id="chatting-messages-section">
                        @include('vendor-views.chatting.messages', [
                            'counterparty' => $activeUser,
                            'chattingMessages' => $chattingMessages,
                        ])
                    </div>

                    @include('vendor-views.chatting.partials._composer', [
                        'counterparty' => $activeUser,
                        'userType' => $userType,
                    ])
                </div>
            </section>
        </div>

        <span id="chatting-post-url"
              data-param="{{ $userType === 'delivery-man' ? 'delivery_man_id' : 'user_id' }}"
              data-url="{{ route('vendor.messages.message') }}?{{ $userType === 'delivery-man' ? 'delivery_man_id' : 'user_id' }}="></span>
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
    <script src="{{ dynamicAsset(path: 'public/assets/back-end/js/vendor/chatting.js') }}"></script>
    <script src="{{ dynamicAsset(path: 'public/assets/back-end/js/message-attachments.js') }}"></script>
    <script src="{{ dynamicAsset(path: 'public/assets/back-end/js/picmo-emoji.js') }}"></script>
    <script src="{{ dynamicAsset(path: 'public/assets/back-end/js/emoji.js') }}"></script>
    <script src="{{ dynamicAsset(path: 'public/assets/back-end/js/select-multiple-file.js') }}"></script>
    <script src="{{ dynamicAsset(path: 'public/assets/back-end/js/select-multiple-image-for-message.js') }}"></script>
    <script src="{{ dynamicAsset(path: 'public/assets/back-end/js/owl.min.js') }}"></script>
@endpush
