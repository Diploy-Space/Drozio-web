@php use function App\Utils\customer_info; @endphp
@extends('theme-views.layouts.profile')
@section('title', translate('My Support Tickets').' | '.$web_config['company_name'].' '.translate('eCommerce'))
@section('content')
    <main class="main-content d-flex flex-column gap-3 py-3 mb-4">
        <div class="container">
            <div class="row g-3">
                @include('theme-views.partials._profile-aside')
                <div class="col-lg-9">
                    <div class="card h-100">
                        <div class="card-body d-flex flex-column gap-3 p-2 p-sm-4">
                            <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
                                <h5 class="mb-0">
                                    {{ translate('My Support Tickets') }}
                                    @if($supportTickets->total() > 0)
                                        <span>({{ $supportTickets->total() }})</span>
                                    @endif
                                </h5>
                                <button type="button" class="btn btn-primary rounded-pill d-inline-flex align-items-center gap-2 px-3 px-sm-4"
                                        data-bs-toggle="modal"
                                        data-bs-target="#reviewModal">
                                    <i class="bi bi-plus-lg"></i>
                                    <span>{{ translate('Create support tickets') }}</span>
                                </button>
                            </div>
                            @include('theme-views.partials._profile-module-tabs')

                            @foreach($supportTickets as $key=>$supportTicket)
                                <div class="bg-light rounded-10">
                                    <div class="border-bottom support-ticket-row border-grey p-3">
                                        <div class="media gap-2 gap-sm-3">
                                            <div class="avatar">
                                                <img loading="lazy" class="img-fit dark-support" alt=""
                                                    src="{{ getStorageImages(path: customer_info()->image_full_url, type:'avatar' ) }}">
                                            </div>
                                            <div class="media-body min-w-0">
                                                <div class="d-flex flex-column gap-1">
                                                    <div class="media align-items-start justify-content-between gap-2">
                                                        <div class="media-body min-w-0">
                                                            <a href="{{route('support-ticket.index',$supportTicket['id'])}}">
                                                                <h6 class="line-limit-2 overflow-wrap-anywhere">{{ customer_info()->f_name }}
                                                                    &nbsp{{ customer_info()->l_name }}</h6>
                                                            </a>
                                                            <div
                                                                class="fs-12 text-muted mb-1 overflow-wrap-anywhere">{{ customer_info()['email'] }}</div>
                                                        </div>
                                                        @if($supportTicket->status != 'close')
                                                            <a href="{{route('support-ticket.close',[$supportTicket['id']])}}"
                                                               class="btn btn-outline-danger fw-semibold text-nowrap flex-shrink-0">{{ translate('Close ticket') }}</a>
                                                        @endif
                                                    </div>

                                                    <div class="d-flex flex-wrap align-items-center gap-2 gap-sm-3">
                                                    <span
                                                        @if($supportTicket->priority == 'Urgent')
                                                            class="badge rounded-pill bg-danger"
                                                        @elseif($supportTicket->priority == 'High')
                                                            class="badge rounded-pill bg-warning"
                                                        @elseif($supportTicket->priority == 'Medium')
                                                            class="badge rounded-pill bg-info"
                                                        @else
                                                            class="badge rounded-pill bg-success"
                                                        @endif
                                                    >
                                                        {{ translate($supportTicket->priority) }}</span>
                                                        <span
                                                            class="{{$supportTicket->status ==  'open' ? 'badge bg-info' : 'badge bg-danger'}} rounded-pill">{{ translate($supportTicket->status) }}</span>
                                                        <span
                                                            class="badge bg-white text-dark">{{ translate($supportTicket->type) }}</span>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="d-flex flex-wrap justify-content-between gap-2 p-3">
                                        <h6 class="text-truncate width--60ch">{{ $supportTicket->subject }}</h6>
                                        <div
                                            class="fs-12">{{date('d M, Y H:i A',strtotime($supportTicket->created_at))}}</div>
                                    </div>
                                </div>
                            @endforeach
                            @if($supportTickets->count()==0)
                                <div class="d-flex justify-content-center align-items-center">
                                    <div class="d-flex flex-column justify-content-center align-items-center gap-2 py-5 w-100">
                                        <img width="80" class="mb-3" src="{{ theme_asset('assets/img/empty-state/empty-ticket.svg') }}" alt="">
                                        <h5 class="text-center text-muted">
                                            {{ translate('No ticket created yet') }}!
                                        </h5>
                                    </div>
                                </div>
                            @endif
                            <div class="border-0">
                                {{$supportTickets->links() }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>
    <div class="modal fade" id="reviewModal" tabindex="-1" aria-labelledby="reviewModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered ticket-modal">
            <div class="modal-content">
                <div class="ticket-modal__header">
                    <span class="ticket-modal__icon">
                        <i class="bi bi-headset"></i>
                    </span>
                    <div class="ticket-modal__heading">
                        <h5 class="ticket-modal__title" id="reviewModalLabel">{{ translate('Submit new ticket') }}</h5>
                        <p class="ticket-modal__subtitle">{{ translate('You will get response') }}.</p>
                    </div>
                    <button type="button" class="ticket-modal__close" data-bs-dismiss="modal"
                            aria-label="{{ translate('Close') }}">
                        <i class="bi bi-x-lg"></i>
                    </button>
                </div>
                <form action="{{ $ticketStoreUrl }}" id="open-ticket" method="post" enctype="multipart/form-data"
                      class="form-advance-validation form-advance-inputs-validation form-advance-file-validation non-ajax-form-validate"
                      novalidate="novalidate">
                    @csrf
                    <div class="ticket-modal__body">
                        <div class="mb-3">
                            <label class="ticket-modal__label" for="ticket-subject">
                                {{ translate('Subject') }} <span class="text-danger">*</span>
                            </label>
                            <input type="text" class="form-control ticket-modal__control" id="ticket-subject"
                                   name="ticket_subject" placeholder="{{ translate('Enter a short summary') }}"
                                   data-required-msg="{{ translate('Ticket subject is required') }}" required>
                        </div>
                        <div class="row g-3 mb-3">
                            <div class="col-sm-6">
                                <label class="ticket-modal__label" for="ticket-type">
                                    {{ translate('Type') }} <span class="text-danger">*</span>
                                </label>
                                <select id="ticket-type" name="ticket_type" class="form-select ticket-modal__control" required>
                                    <option value="Website problem">{{ translate('Website problem') }}</option>
                                    <option value="Partner request">{{ translate('Partner request') }}</option>
                                    <option value="Complaint">{{ translate('Complaint') }}</option>
                                    <option value="Information inquiry">{{ translate('Information inquiry') }}</option>
                                </select>
                            </div>
                            <div class="col-sm-6">
                                <label class="ticket-modal__label" for="ticket-priority">
                                    {{ translate('Priority') }} <span class="text-danger">*</span>
                                </label>
                                <select id="ticket-priority" name="ticket_priority" class="form-select ticket-modal__control" required>
                                    <option value>{{ translate('Choose priority') }}</option>
                                    <option value="Urgent">{{ translate('Urgent') }}</option>
                                    <option value="High">{{ translate('High') }}</option>
                                    <option value="Medium">{{ translate('Medium') }}</option>
                                    <option value="Low">{{ translate('Low') }}</option>
                                </select>
                            </div>
                        </div>
                        <div>
                            <label class="ticket-modal__label" for="ticket-description">{{ translate('Describe your issue') }}</label>
                            <textarea class="form-control ticket-modal__control ticket-modal__textarea" rows="5"
                                      id="ticket-description" name="ticket_description"
                                      maxlength="{{ $ticketDescriptionMaxLength }}"
                                      placeholder="{{ translate('Leave your issue') }}"></textarea>
                            <div class="d-flex justify-content-end fs-12 text-muted mt-1">
                                <span id="ticket-description-count">0</span>/{{ $ticketDescriptionMaxLength }}
                            </div>
                        </div>
                        <div class="mt-3">
                            <span class="ticket-modal__label">{{ translate('Attachment') }}</span>
                            <div class="ticket-attachment-picker d-flex flex-wrap align-items-center gap-2"
                                 data-field-name="image[]"
                                 data-max-filesize="{{ getFileUploadMaxSize() }}"
                                 data-extension-error="{{ translate('Only JPEG, PNG, JPG, and GIF images are allowed.') }}"
                                 data-size-error="{{ translate('File size is too large Maximum').' '.getFileUploadMaxSize().' MB' }}"></div>
                        </div>
                    </div>
                    <div class="ticket-modal__footer">
                        <button type="button" class="btn ticket-modal__button ticket-modal__button--cancel"
                                data-bs-dismiss="modal">{{ translate('Back') }}</button>
                        <button type="submit" class="btn btn-primary ticket-modal__button ticket-modal__button--submit"
                                id="ticket-submit-button" disabled>
                            <span>{{ translate('Submit') }}</span>
                            <i class="bi bi-send"></i>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@push('script')
    <script src="{{ theme_asset(path: 'assets/js/account-ticket.js') }}" defer></script>
@endpush
