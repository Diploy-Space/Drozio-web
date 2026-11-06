<div class="list_filter" data-type="{{ $conversation['type'] }}"
     data-search="{{ $conversation['name'].' '.$conversation['phone'] }}">
    <a class="chat_list get-ajax-message-view d-flex align-items-start gap-3 p-3 text-decoration-none {{ $conversation['isActive'] ? 'active bg-soft-secondary' : '' }}"
       href="{{ $conversation['chatRoute'] }}" data-user-id="{{ $conversation['id'] }}">
        <img class="avatar avatar-circle flex-shrink-0 chat-row-avatar" alt=""
             src="{{ $conversation['image'] }}">

        <div class="chat-row-body">
            <div class="d-flex align-items-center justify-content-between gap-2">
                <span class="font-weight-bold text-dark line-1" title="{{ $conversation['name'] }}">
                    {{ $conversation['name'] }}
                </span>
                <span class="chat-row-time fs-12 text-muted text-nowrap flex-shrink-0">
                    {{ $conversation['time']->diffForHumans(short: true) }}
                </span>
            </div>

            <div class="d-flex align-items-center justify-content-between gap-2">
                <p class="chat-row-preview fs-12 text-muted line-1 mb-0">
                    @if($conversation['sentByViewer'])
                        <span class="text-dark">{{ translate('You') }}:</span>
                    @endif
                    @if($conversation['message'])
                        {{ $conversation['message'] }}
                    @elseif($conversation['hasAttachment'])
                        {{ translate('Sent an attachment') }}
                    @endif
                </p>

                @if($conversation['unreadCount'] > 0)
                    <span class="chat-unread-count flex-shrink-0" id="count-unread-messages-{{ $conversation['id'] }}">
                        {{ $conversation['unreadCount'] }}
                    </span>
                @endif
            </div>

            <span class="fs-12 text-muted line-1 d-block">{{ $conversation['phone'] }}</span>
        </div>
    </a>
</div>
