@extends('layouts.vendor.app')

@section('title', translate('Inbox'))

@section('content')
    <div class="content container-fluid">
        <div class="mb-3">
            <h2 class="h1 mb-0 text-capitalize d-flex align-items-center gap-2">
                <img width="20" alt=""
                     src="{{ dynamicAsset(path: 'public/assets/back-end/img/support-ticket.png') }}">
                {{ translate('Inbox') }}
            </h2>
        </div>

        <div class="row g-3">
            @foreach($inboxSections as $section)
                <div class="col-lg-6">
                    <div class="card inbox-panel h-100" id="{{ $section['type'] }}" data-inbox-panel>
                        <div class="p-3 border-bottom">
                            <div class="d-flex align-items-center justify-content-between gap-2 mb-3">
                                <h3 class="mb-0 fs-16 font-weight-bold d-flex align-items-center gap-2">
                                    {{ $section['title'] }}
                                    <span class="badge badge-soft-dark radius-50">
                                        {{ count($section['conversations']) }}
                                    </span>
                                </h3>
                                @if($section['unreadCount'] > 0)
                                    <span class="chat-tab-count">{{ $section['unreadCount'] }}</span>
                                @endif
                            </div>

                            <form class="search-form inbox-search-form w-100">
                                <div class="search-input-group w-100">
                                    <i class="tio-search search-icon" aria-hidden="true"></i>
                                    <input type="text" class="w-100 inbox-search-input"
                                           placeholder="{{ $section['searchPlaceholder'] }}...">
                                </div>
                            </form>
                        </div>

                        <div class="inbox_chat inbox-list flex-grow-1 overflow-y-auto">
                            @forelse($section['conversations'] as $conversation)
                                @include('vendor-views.chatting.partials._conversation-row', ['conversation' => $conversation])
                            @empty
                                @include('layouts.vendor.partials._empty-state', [
                                    'image' => $section['emptyImage'],
                                    'text' => $section['emptyText'],
                                    'width' => 120,
                                    'class' => 'h-100 px-3',
                                ])
                            @endforelse

                            <div class="inbox-no-result d-none h-100">
                                @include('layouts.vendor.partials._empty-state', [
                                    'image' => 'default',
                                    'text' => 'No result found',
                                    'width' => 120,
                                    'class' => 'h-100 px-3',
                                ])
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
@endsection

@push('script')
    <script src="{{ dynamicAsset(path: 'public/assets/back-end/js/vendor/messages-inbox.js') }}"></script>
@endpush
