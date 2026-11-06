@foreach($chattingMessages as $message)
    @if($message['isIncoming'])
        <div class="incoming_msg d-flex align-items-end gap-2">
            <div class="flex-shrink-0 chat-avatar-slot">
                @if($message['showAvatar'])
                    <img class="avatar-img user-avatar-image border inbox-user-avatar-25 rounded-circle aspect-1"
                         src="{{ $counterparty['image'] }}" alt="">
                @endif
            </div>
            <div class="received_msg d-flex flex-column align-items-start">
                @include('admin-views.partials._message-attachments', [
                    'documents' => $message['documents'],
                    'media' => $message['media'],
                    'messageId' => $message['id'],
                    'isIncoming' => true,
                ])

                @if($message['text'])
                    <div class="message-text-section rounded mt-2">
                        <p class="m-0 pb-1">{{ $message['text'] }}</p>
                    </div>
                @endif

                <span class="chat-bubble-meta" data-bs-toggle="tooltip" data-bs-custom-class="chatting-time min-w-0"
                      data-bs-title="{{ $message['fullTime'] }}">
                    {{ $message['time'] }}
                </span>
            </div>
        </div>
    @else
        <div class="outgoing_msg">
            <div class="sent_msg px-2 d-flex flex-column align-items-end">
                @include('admin-views.partials._message-attachments', [
                    'documents' => $message['documents'],
                    'media' => $message['media'],
                    'messageId' => $message['id'],
                    'isIncoming' => false,
                ])

                @if($message['text'])
                    <div class="message-text-section rounded-10 mt-2 overflow-wrap-anywhere">
                        <p class="m-0 pb-1">{{ $message['text'] }}</p>
                    </div>
                @endif

                <span class="chat-bubble-meta" data-bs-toggle="tooltip" data-bs-custom-class="chatting-time min-w-0"
                      data-bs-title="{{ $message['fullTime'] }}">
                    {{ $message['time'] }}
                    <i class="fi {{ $message['isSeen'] ? 'fi-sr-check-double text-primary' : 'fi-rr-check' }}"
                       title="{{ $message['isSeen'] ? translate('Seen') : translate('Sent') }}"></i>
                </span>
            </div>
        </div>
    @endif

    @if($message['startsDay'])
        <div class="chat-day-divider">
            <span>{{ $message['dateLabel'] }}</span>
        </div>
    @endif
@endforeach
