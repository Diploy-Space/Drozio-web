@extends('theme-views.layouts.profile')

@section('title', translate('Support ticket').' | '.$web_config['company_name'].' '.translate('eCommerce'))

@section('content')
    <main class="main-content d-flex flex-column gap-3 py-3 mb-5">
        <div class="container">
            <div class="row g-3">
                @include('theme-views.partials._profile-aside')
                <div class="col-lg-9">
                    <div class="card ov-hidden border-0">
                        <div
                            class="bg-light rounded-10 border-grey d-flex gap-3 flex-wrap align-items-start justify-content-between p-3 m-3">
                            <div class="media flex-wrap gap-3 min-w-0">
                                <div class="avatar avatar-lg rounded-circle">
                                    <img loading="lazy" class="img-fit rounded-circle dark-support aspect-1 max-w-140px" alt="{{ $ticketHeader['customerName'] }}"
                                         src="{{ $ticketHeader['customerAvatar'] }}">
                                </div>
                                <div class="media-body min-w-0">
                                    <div class="d-flex flex-column gap-1">
                                        <h6 class="mb-0 overflow-wrap-anywhere line-clamp-2">{{ $ticketHeader['customerName'] }}</h6>
                                        <div class="fs-12 text-muted overflow-wrap-anywhere">{{ $ticketHeader['customerEmail'] }}</div>
                                        <div class="d-flex flex-wrap align-items-center column-gap-4 row-gap-1 mt-1">
                                            <div class="d-flex align-items-center gap-2">
                                                <div class="fw-bold">{{ translate('Status') }}:</div>
                                                <span class="text-{{ $ticketHeader['statusTone'] }} fw-semibold">{{ $ticketHeader['statusLabel'] }}</span>
                                            </div>
                                            <div class="d-flex align-items-center gap-2">
                                                <div class="fw-bold">{{ translate('Priority') }}:</div>
                                                <span class="text-{{ $ticketHeader['priorityTone'] }} fw-semibold">{{ $ticketHeader['priorityLabel'] }}</span>
                                            </div>
                                            <div class="d-flex align-items-center gap-2">
                                                <div class="fw-bold">{{ translate('Type') }}:</div>
                                                <span class="fw-semibold overflow-wrap-anywhere">{{ $ticketHeader['typeLabel'] }}</span>
                                            </div>
                                        </div>
                                        @if($ticket?->subject)
                                            <div class="d-flex align-items-start gap-2 fs-14 mt-2">
                                                <div class="fw-bold text-nowrap">{{ translate('Subject') }}:</div>
                                                <span class="text-info fw-semibold max-w-500 overflow-wrap-anywhere">{{ $ticket->subject }}</span>
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            </div>
                            @if($ticketHeader['isClosable'])
                                <a href="{{ route('support-ticket.close', [$ticket['id']]) }}"
                                   class="btn btn-outline-danger rounded">{{ translate('Close this ticket') }}</a>
                            @endif
                        </div>
                        <div class="messaging">
                            <div class="inbox_msg custom-scrollbar p-3 msg-history __h-30rem">
                                @foreach($messages as $message)
                                    @if($message['isIncoming'])
                                        <div class="received_msg">
                                            @if($message['text'])
                                                <p class="message_text mw-100 overflow-wrap-anywhere">{{ $message['text'] }}</p>
                                            @endif

                                            @include('theme-views.partials._ticket-attachments', [
                                                'documents' => $message['documents'],
                                                'media' => $message['media'],
                                                'messageId' => $message['id'],
                                                'isIncoming' => true,
                                            ])

                                            <span class="time_date">{{ $message['time'] }}</span>
                                        </div>
                                    @else
                                        <div class="outgoing_msg">
                                            @if($message['text'])
                                                <p class="message_text mw-100 overflow-wrap-anywhere">{{ $message['text'] }}</p>
                                            @endif

                                            @include('theme-views.partials._ticket-attachments', [
                                                'documents' => $message['documents'],
                                                'media' => $message['media'],
                                                'messageId' => $message['id'],
                                                'isIncoming' => false,
                                            ])

                                            <span class="time_date d-flex justify-content-end">{{ $message['time'] }}</span>
                                        </div>
                                    @endif
                                @endforeach
                            </div>

                            @if($ticket->status ==  'open')
                                <div class="type_msg">
                                    <form action="{{route('support-ticket.comment',[$ticket['id']])}}" method="post" enctype="multipart/form-data" class="form-advance-validation form-advance-inputs-validation form-advance-file-validation non-ajax-form-validate" novalidate="novalidate">
                                        @csrf
                                        <div class="input_msg_write border-top py-2 px-2 px-sm-3 d-flex align-items-start justify-content-between gap-2 gap-sm-3 lh-base">
                                            <div class="d-flex align-items-center gap-2 pt-2">
                                                <label class="m-0 cursor-pointer position-relative text-primary" title="{{ translate('Attach photo or video') }}">
                                                    <i class="bi bi-image fs-16"></i>
                                                    <input type="file" id="select-media" class="h-100 position-absolute w-100" hidden multiple
                                                           data-max-size="{{ getFileUploadMaxSize() }}"
                                                           accept="{{ getFileUploadFormats(skip: '.svg') }}">
                                                </label>
                                                <label class="m-0 cursor-pointer position-relative text-primary" title="{{ translate('Attach file') }}">
                                                    <i class="bi bi-paperclip fs-16"></i>
                                                    <input type="file" id="select-file" class="h-100 position-absolute w-100" hidden multiple
                                                           data-max-size="{{ getFileUploadMaxSize(type: 'file') }}"
                                                           accept="{{ getFileUploadFormats(type: 'file') }}">
                                                </label>
                                            </div>

                                            <div class="w-0 flex-grow-1">
                                                <textarea class="w-100 custom-height h--5rem" id="msgInputValueTicket"
                                                          name="comment" rows="6"
                                                          placeholder="{{translate('Start typing').'...'}}"></textarea>

                                                <div class="overflow-x-auto pb-2 pt-1">
                                                    <div class="d-flex gap-3 align-items-center">
                                                        <div class="d-flex gap-3 image-array"></div>
                                                        <div class="d-flex gap-3 file-array"></div>
                                                    </div>
                                                </div>
                                                <div id="selected-media-container"></div>
                                                <div id="selected-files-container"></div>
                                            </div>

                                            <button class="btn btn-primary px-2 py-1 lh-1 rounded" type="submit">
                                                <i class="bi bi-send-fill fs-16"></i>
                                            </button>
                                        </div>
                                    </form>
                                </div>
                            @endif

                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>
    <span id="get-file-icon"
          data-default-icon="{{ theme_asset('assets/img/default-icon.png') }}"
          data-word-icon="{{ theme_asset('assets/img/word-icon.png') }}"></span>
@endsection

@push('script')
    <script src="{{ theme_asset('assets/js/select-multiple-file.js') }}" defer></script>
    <script src="{{ theme_asset('assets/js/select-multiple-image-for-message.js') }}" defer></script>
    <script>
        'use strict';
        $(document).ready(function () {
            let messageHistory = $(".msg-history");
            messageHistory.stop().animate({scrollTop: messageHistory[0].scrollHeight}, 1000);
        });
    </script>
@endpush
