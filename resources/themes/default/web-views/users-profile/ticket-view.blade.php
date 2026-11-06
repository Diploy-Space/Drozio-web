@extends('layouts.front-end.profile')

@section('title', translate('Support ticket'))

@section('content')
    <div class="container py-4 rtl text-align-direction">
        <div class="row">
            @include('web-views.partials._profile-aside')

            <section class="col-lg-9">
                <div class="d-flex align-items-center justify-content-end mb-3 d-lg-none">
                    <button class="profile-aside-btn btn btn--primary px-2 rounded px-2 py-1">
                        <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 15 15" fill="none">
                            <path fill-rule="evenodd" clip-rule="evenodd" d="M7 9.81219C7 9.41419 6.842 9.03269 6.5605 8.75169C6.2795 8.47019 5.898 8.31219 5.5 8.31219C4.507 8.31219 2.993 8.31219 2 8.31219C1.602 8.31219 1.2205 8.47019 0.939499 8.75169C0.657999 9.03269 0.5 9.41419 0.5 9.81219V13.3122C0.5 13.7102 0.657999 14.0917 0.939499 14.3727C1.2205 14.6542 1.602 14.8122 2 14.8122H5.5C5.898 14.8122 6.2795 14.6542 6.5605 14.3727C6.842 14.0917 7 13.7102 7 13.3122V9.81219ZM14.5 9.81219C14.5 9.41419 14.342 9.03269 14.0605 8.75169C13.7795 8.47019 13.398 8.31219 13 8.31219C12.007 8.31219 10.493 8.31219 9.5 8.31219C9.102 8.31219 8.7205 8.47019 8.4395 8.75169C8.158 9.03269 8 9.41419 8 9.81219V13.3122C8 13.7102 8.158 14.0917 8.4395 14.3727C8.7205 14.6542 9.102 14.8122 9.5 14.8122H13C13.398 14.8122 13.7795 14.6542 14.0605 14.3727C14.342 14.0917 14.5 13.7102 14.5 13.3122V9.81219ZM12.3105 7.20869L14.3965 5.12269C14.982 4.53719 14.982 3.58719 14.3965 3.00169L12.3105 0.915687C11.725 0.330188 10.775 0.330188 10.1895 0.915687L8.1035 3.00169C7.518 3.58719 7.518 4.53719 8.1035 5.12269L10.1895 7.20869C10.775 7.79419 11.725 7.79419 12.3105 7.20869ZM7 2.31219C7 1.91419 6.842 1.53269 6.5605 1.25169C6.2795 0.970186 5.898 0.812187 5.5 0.812187C4.507 0.812187 2.993 0.812187 2 0.812187C1.602 0.812187 1.2205 0.970186 0.939499 1.25169C0.657999 1.53269 0.5 1.91419 0.5 2.31219V5.81219C0.5 6.21019 0.657999 6.59169 0.939499 6.87269C1.2205 7.15419 1.602 7.31219 2 7.31219H5.5C5.898 7.31219 6.2795 7.15419 6.5605 6.87269C6.842 6.59169 7 6.21019 7 5.81219V2.31219Z" fill="white"/>
                            </svg>
                    </button>
                </div>

                <div class="__card">
                    <div class="card-header border-0 p-2 p-sm-3">
                        <div class="ticket-head">
                            <div class="ticket-head__top">
                                <div class="ticket-head__author">
                                    <img class="ticket-head__avatar" width="44" height="44" alt="{{ $ticketHeader['customerName'] }}"
                                         src="{{ $ticketHeader['customerAvatar'] }}">
                                    <div class="ticket-head__author-info">
                                        <h6 class="ticket-head__name">{{ $ticketHeader['customerName'] }}</h6>
                                        <div class="ticket-head__meta">
                                            <span>{{ translate('Ticket') }} #{{ $ticket['id'] }}</span>
                                            @if($ticketHeader['createdAt'])
                                                <span class="ticket-head__meta-dot"></span>
                                                <span>{{ $ticketHeader['createdAt'] }}</span>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                                @if($ticketHeader['isClosable'])
                                    <a href="{{ route('support-ticket.close', [$ticket['id']]) }}" class="ticket-head__close"
                                       title="{{ translate('Close this ticket') }}">
                                        <i class="czi-close-circle"></i>
                                        <span class="d-none d-sm-inline">{{ translate('Close this ticket') }}</span>
                                        <span class="d-sm-none">{{ translate('Close') }}</span>
                                    </a>
                                @endif
                            </div>
                            @if($ticket['subject'])
                                <h5 class="ticket-head__subject">{{ $ticket['subject'] }}</h5>
                            @endif
                            <div class="ticket-head__chips">
                                <span class="ticket-chip ticket-chip--neutral">
                                    <i class="czi-document"></i>
                                    {{ $ticketHeader['typeLabel'] }}
                                </span>
                                <span class="ticket-chip ticket-chip--{{ $ticketHeader['statusTone'] }}">
                                    <span class="ticket-chip__dot"></span>
                                    {{ translate('Status') }}: {{ $ticketHeader['statusLabel'] }}
                                </span>
                                <span class="ticket-chip ticket-chip--{{ $ticketHeader['priorityTone'] }}">
                                    <i class="czi-flag"></i>
                                    {{ translate('Priority') }}: {{ $ticketHeader['priorityLabel'] }}
                                </span>
                            </div>
                        </div>
                    </div>

                    <div class="__media-wrapper p-2 p-sm-3 d-flex flex-column">
                        @foreach($messages as $message)
                            @if($message['isIncoming'])
                                <div class="media gap-3">
                                    <div class="media-body d-flex">
                                        <img class="rounded-circle __img-40 mt-2 text-align-direction" alt=""
                                             src="{{ $message['avatar'] }}">
                                        <div class="mx-1 __incoming-msg">
                                            @if($message['text'])
                                                <div class="d-flex justify-content-start">
                                                    <p class="font-size-md mb-1 btn--primary message-text text-break overflow-hidden">{{ $message['text'] }}</p>
                                                </div>
                                            @endif

                                            @include('web-views.partials._ticket-attachments', [
                                                'documents' => $message['documents'],
                                                'media' => $message['media'],
                                                'messageId' => $message['id'],
                                                'isIncoming' => true,
                                            ])

                                            <span class="time_date font-size-ms text-muted">{{ $message['relativeTime'] }}</span>
                                        </div>
                                    </div>
                                </div>
                            @else
                                <div class="media">
                                    <div class="media-body __outgoing-msg">
                                        @if($message['text'])
                                            <div class="d-flex justify-content-end">
                                                <p class="font-size-md mb-1 btn--primary message-text text-break overflow-hidden">{{ $message['text'] }}</p>
                                            </div>
                                        @endif

                                        @include('web-views.partials._ticket-attachments', [
                                            'documents' => $message['documents'],
                                            'media' => $message['media'],
                                            'messageId' => $message['id'],
                                            'isIncoming' => false,
                                        ])

                                        <p class="time_date font-size-ms text-end">{{ $message['relativeTime'] }}</p>
                                    </div>
                                </div>
                            @endif
                        @endforeach
                    </div>

                    @if($ticket->status != 'close')
                    <div class="card-footer py-0 px-0">
                        <form class="needs-validation form-advance-validation form-advance-inputs-validation form-advance-file-validation non-ajax-form-validate" action="{{ route('support-ticket.comment', [$ticket['id']]) }}" enctype="multipart/form-data"
                            method="post" novalidate>
                            @csrf
                            <div class="d-flex flex-wrap align-items-baseline">
                                <div class="px-2 d-flex gap-2">
                                    <label class="m-0 cursor-pointer position-relative text-primary" title="{{ translate('Attach photo or video') }}">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 22 22" fill="none">
                                            <path d="M18.1029 1.83203H3.89453C2.75786 1.83203 1.83203 2.75786 1.83203 3.89453V18.1029C1.83203 19.2395 2.75786 20.1654 3.89453 20.1654H18.1029C19.2395 20.1654 20.1654 19.2395 20.1654 18.1029V3.89453C20.1654 2.75786 19.2395 1.83203 18.1029 1.83203ZM3.89453 3.20703H18.1029C18.4814 3.20703 18.7904 3.51595 18.7904 3.89453V12.7642L15.2539 9.2277C15.1255 9.09936 14.9514 9.02603 14.768 9.02603H14.7653C14.5819 9.02603 14.405 9.09936 14.2776 9.23136L10.3204 13.25L8.65845 11.5945C8.53011 11.4662 8.35595 11.3929 8.17261 11.3929C7.9957 11.3654 7.81053 11.4662 7.6822 11.6009L3.20703 16.1705V3.89453C3.20703 3.51595 3.51595 3.20703 3.89453 3.20703ZM3.21253 18.1304L8.17903 13.0575L13.9375 18.7904H3.89453C3.52603 18.7904 3.22811 18.4952 3.21253 18.1304ZM18.1029 18.7904H15.8845L11.2948 14.2189L14.7708 10.6898L18.7904 14.7084V18.1029C18.7904 18.4814 18.4814 18.7904 18.1029 18.7904Z" fill="currentColor"/>
                                            <path d="M8.12834 9.03012C8.909 9.03012 9.54184 8.39728 9.54184 7.61662C9.54184 6.83597 8.909 6.20312 8.12834 6.20312C7.34769 6.20312 6.71484 6.83597 6.71484 7.61662C6.71484 8.39728 7.34769 9.03012 8.12834 9.03012Z" fill="currentColor"/>
                                        </svg>
                                        <input type="file" id="select-media" class="h-100 position-absolute w-100" data-max-size="{{ getFileUploadMaxSize() }}" hidden multiple accept="{{ getFileUploadFormats(skip: '.svg') }}">
                                    </label>

                                    <label class="m-0 cursor-pointer position-relative text-primary" title="{{ translate('Attach file') }}">
                                        <svg width="20" height="18" viewBox="0 0 20 18" fill="none" xmlns="http://www.w3.org/2000/svg">
                                            <path d="M5.61597 17.2917C4.66813 17.2919 3.7415 17.011 2.95335 16.4845C2.16519 15.958 1.55092 15.2096 1.18827 14.3338C0.825613 13.4581 0.730874 12.4945 0.916037 11.5649C1.1012 10.6353 1.55794 9.78158 2.22847 9.11165L9.2993 2.03999C9.41655 1.92274 9.57557 1.85687 9.74139 1.85687C9.9072 1.85687 10.0662 1.92274 10.1835 2.03999C10.3007 2.15724 10.3666 2.31626 10.3666 2.48207C10.3666 2.64788 10.3007 2.80691 10.1835 2.92415L3.11181 9.99499C2.76945 10.3208 2.49576 10.7118 2.30686 11.145C2.11796 11.5782 2.01768 12.0449 2.01193 12.5175C2.00617 12.99 2.09506 13.459 2.27334 13.8967C2.45163 14.3344 2.71572 14.7319 3.05004 15.066C3.38436 15.4 3.78216 15.6638 4.21999 15.8417C4.65783 16.0196 5.12685 16.1081 5.59941 16.102C6.07198 16.0958 6.53854 15.9951 6.9716 15.8059C7.40465 15.6166 7.79545 15.3426 8.12097 15L17.2543 5.86665C17.6728 5.43446 17.9047 4.85506 17.8999 4.25344C17.895 3.65183 17.6539 3.07623 17.2285 2.65081C16.8031 2.22539 16.2275 1.98425 15.6258 1.97942C15.0242 1.97459 14.4448 2.20645 14.0126 2.62499L6.64764 9.99499C6.45226 10.1904 6.3425 10.4554 6.3425 10.7317C6.3425 11.008 6.45226 11.2729 6.64764 11.4683C6.84301 11.6637 7.108 11.7735 7.3843 11.7735C7.66061 11.7735 7.9256 11.6637 8.12097 11.4683L12.8335 6.75499C12.8911 6.69527 12.96 6.64762 13.0363 6.61483C13.1125 6.58204 13.1945 6.56476 13.2775 6.564C13.3605 6.56324 13.4428 6.57901 13.5196 6.6104C13.5964 6.64179 13.6663 6.68817 13.725 6.74682C13.7837 6.80548 13.8301 6.87524 13.8616 6.95203C13.893 7.02883 13.9089 7.11112 13.9082 7.19411C13.9075 7.27709 13.8903 7.35911 13.8576 7.43538C13.8249 7.51165 13.7773 7.58064 13.7176 7.63832L9.0043 12.3525C8.57454 12.7824 7.99162 13.0239 7.38377 13.024C6.77591 13.0241 6.19293 12.7827 5.76305 12.3529C5.33318 11.9231 5.09164 11.3402 5.09156 10.7324C5.09148 10.1245 5.33288 9.54153 5.76264 9.11165L13.1293 1.74999C13.7935 1.08573 14.6943 0.712511 15.6336 0.712433C16.5729 0.712355 17.4738 1.08542 18.1381 1.74957C18.8023 2.41372 19.1755 3.31454 19.1756 4.25386C19.1757 5.19318 18.8026 6.09406 18.1385 6.75832L9.00514 15.8883C8.56103 16.3347 8.03283 16.6885 7.45109 16.9294C6.86934 17.1703 6.24561 17.2934 5.61597 17.2917Z" fill="currentColor"/>
                                        </svg>
                                        <input type="file" id="select-file" class="h-100 position-absolute w-100" data-max-size="{{ getFileUploadMaxSize(type: 'file') }}" hidden multiple accept="{{ getFileUploadFormats(type: 'file') }}">
                                    </label>
                                </div>
                                <div class="w-0 flex-grow-1">
                                    <textarea class="form-control ticket-view-control px-0 py-3" name="comment" rows="8" placeholder="{{translate('Write your message here')}}..." ></textarea>

                                    <div class="overflow-x-auto pt-3 pb-2">
                                        <div class="d-flex gap-3 align-items-center">
                                            <div class="d-flex gap-3 image-array"></div>
                                            <div class="d-flex gap-3 file-array"></div>
                                        </div>
                                    </div>
                                    <div id="selected-media-container"></div>
                                    <div id="selected-files-container"></div>
                                </div>

                                <button type="submit" class="no-gutter py-0 px-3 m-0 border-0 bg-transparent text-base">
                                    <svg width="31" height="31" viewBox="0 0 31 31" fill="none" xmlns="http://www.w3.org/2000/svg">
                                        <rect width="31" height="31" rx="6" fill="currentColor"/>
                                        <path d="M21.2267 15.5548L10.2267 10.0548C10.1404 10.0116 10.0436 9.99437 9.94779 10.005C9.85198 10.0157 9.7613 10.0538 9.68665 10.1148C9.61536 10.1745 9.56215 10.253 9.53301 10.3413C9.50386 10.4296 9.49993 10.5243 9.52165 10.6148L10.8467 15.4998H16.5017V16.4998H10.8467L9.50165 21.3698C9.48126 21.4453 9.47888 21.5245 9.4947 21.6012C9.51052 21.6778 9.5441 21.7496 9.59273 21.8109C9.64136 21.8722 9.7037 21.9212 9.77472 21.954C9.84574 21.9868 9.92347 22.0025 10.0017 21.9998C10.0799 21.9993 10.157 21.9805 10.2267 21.9448L21.2267 16.4448C21.3086 16.4028 21.3773 16.3391 21.4253 16.2605C21.4733 16.182 21.4987 16.0918 21.4987 15.9998C21.4987 15.9077 21.4733 15.8175 21.4253 15.739C21.3773 15.6605 21.3086 15.5967 21.2267 15.5548Z" fill="white"/>
                                    </svg>
                                </button>
                            </div>
                        </form>
                    </div>
                    @endif
                </div>
            </section>
        </div>
    </div>

    <span id="get-file-icon"
          data-default-icon="{{ theme_asset(path: 'public/assets/front-end/img/default-icon.png') }}"
          data-word-icon="{{ theme_asset(path: 'public/assets/front-end/img/word-icon.png') }}"
          data-pdf-icon="{{ theme_asset(path: 'public/assets/front-end/img/word-icon.png') }}"
          data-zip-icon="{{ theme_asset(path: 'public/assets/front-end/img/default-icon.png') }}"></span>
@endsection

@push('script')
    <script src="{{ theme_asset(path: 'public/assets/front-end/js/select-multiple-file.js') }}" defer></script>
    <script src="{{ theme_asset(path: 'public/assets/front-end/js/select-multiple-image-for-message.js') }}" defer></script>
@endpush
