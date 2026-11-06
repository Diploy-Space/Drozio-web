@extends('layouts.front-end.profile')

@section('title', translate('My Support Tickets'))

@section('content')

    <div class="modal fade rtl text-align-direction" id="open-ticket" tabindex="-1" role="dialog"
         aria-labelledby="open-ticket-title" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered ticket-modal" role="document">
            <div class="modal-content">
                <div class="ticket-modal__header">
                    <span class="ticket-modal__icon">
                        <i class="czi-support"></i>
                    </span>
                    <div class="ticket-modal__heading">
                        <h5 class="ticket-modal__title" id="open-ticket-title">{{ translate('Submit new ticket') }}</h5>
                        <p class="ticket-modal__subtitle">{{ translate('You will get response') }}.</p>
                    </div>
                    <button type="button" class="ticket-modal__close" data-dismiss="modal"
                            aria-label="{{ translate('Close') }}">
                        <i class="czi-close"></i>
                    </button>
                </div>
                <form class="form-advance-validation form-advance-inputs-validation form-advance-file-validation non-ajax-form-validate"
                      method="post" action="{{ $ticketStoreUrl }}" id="open-ticket-form"
                      enctype="multipart/form-data" novalidate="novalidate">
                    @csrf
                    <div class="ticket-modal__body">
                        <div class="form-group">
                            <label class="ticket-modal__label" for="ticket-subject">
                                {{ translate('Subject') }} <span class="text-danger">*</span>
                            </label>
                            <input type="text" class="form-control ticket-modal__control" id="ticket-subject"
                                   name="ticket_subject" placeholder="{{ translate('Enter a short summary') }}" required>
                        </div>
                        <div class="form-row">
                            <div class="form-group col-sm-6">
                                <label class="ticket-modal__label" for="ticket-type">
                                    {{ translate('Type') }} <span class="text-danger">*</span>
                                </label>
                                <select class="form-control custom-select ticket-modal__control" id="ticket-type"
                                        name="ticket_type" required>
                                    <option value="Website problem">{{ translate('Website problem') }}</option>
                                    <option value="Partner request">{{ translate('Partner request') }}</option>
                                    <option value="Complaint">{{ translate('Complaint') }}</option>
                                    <option value="Information inquiry">{{ translate('Information inquiry') }}</option>
                                </select>
                            </div>
                            <div class="form-group col-sm-6">
                                <label class="ticket-modal__label" for="ticket-priority">
                                    {{ translate('Priority') }} <span class="text-danger">*</span>
                                </label>
                                <select class="form-control custom-select ticket-modal__control" id="ticket-priority"
                                        name="ticket_priority" required>
                                    <option value>{{ translate('Choose priority') }}</option>
                                    <option value="Urgent">{{ translate('Urgent') }}</option>
                                    <option value="High">{{ translate('High') }}</option>
                                    <option value="Medium">{{ translate('Medium') }}</option>
                                    <option value="Low">{{ translate('Low') }}</option>
                                </select>
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="ticket-modal__label" for="ticket-description">{{ translate('Describe your issue') }}</label>
                            <textarea class="form-control ticket-modal__control ticket-modal__textarea" rows="5"
                                      id="ticket-description" name="ticket_description"
                                      maxlength="{{ $ticketDescriptionMaxLength }}"
                                      placeholder="{{ translate('Write here') }}..."></textarea>
                            <div class="d-flex justify-content-end fs-12 text-muted mt-1">
                                <span id="ticket-description-count">0</span>/{{ $ticketDescriptionMaxLength }}
                            </div>
                        </div>
                        <div class="form-group mb-0">
                            <label class="ticket-modal__label" for="attachmentfiles">{{ translate('Attachment') }}</label>
                            <div class="d-flex flex-wrap align-items-center upload_images_area">
                                <div class="d-flex flex-wrap filearray"></div>
                                <div class="selected-files-container"></div>
                                <label class="ticket-modal__upload" for="attachmentfiles">
                                    <i class="czi-cloud-upload"></i>
                                    <span>{{ translate('Upload') }}</span>
                                    <input type="file" id="attachmentfiles" class="attachmentfiles"
                                           data-max-size="{{ getFileUploadMaxSize() }}" hidden multiple
                                           accept="{{ getFileUploadFormats(skip: '.svg,.gif,.webp') }}">
                                </label>
                            </div>
                        </div>
                    </div>
                    <div class="ticket-modal__footer">
                        <button type="button" class="btn ticket-modal__button ticket-modal__button--cancel" data-dismiss="modal">
                            {{ translate('Close') }}
                        </button>
                        <button type="submit" class="btn btn--primary ticket-modal__button ticket-modal__button--submit" id="ticket-submit-button" disabled>
                            <span>{{ translate('Submit a ticket') }}</span>
                            <i class="czi-send"></i>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="container py-4 rtl text-align-direction">
        <div class="row">
            @include('web-views.partials._profile-aside')

            <section class="col-lg-9 __customer-profile d-none d-md-block">

                <div class="mb-3 d-lg-none text-right">
                    <button class="profile-aside-btn btn btn--primary px-2 rounded px-2 py-1">
                        <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 15 15" fill="none">
                            <path fill-rule="evenodd" clip-rule="evenodd" d="M7 9.81219C7 9.41419 6.842 9.03269 6.5605 8.75169C6.2795 8.47019 5.898 8.31219 5.5 8.31219C4.507 8.31219 2.993 8.31219 2 8.31219C1.602 8.31219 1.2205 8.47019 0.939499 8.75169C0.657999 9.03269 0.5 9.41419 0.5 9.81219V13.3122C0.5 13.7102 0.657999 14.0917 0.939499 14.3727C1.2205 14.6542 1.602 14.8122 2 14.8122H5.5C5.898 14.8122 6.2795 14.6542 6.5605 14.3727C6.842 14.0917 7 13.7102 7 13.3122V9.81219ZM14.5 9.81219C14.5 9.41419 14.342 9.03269 14.0605 8.75169C13.7795 8.47019 13.398 8.31219 13 8.31219C12.007 8.31219 10.493 8.31219 9.5 8.31219C9.102 8.31219 8.7205 8.47019 8.4395 8.75169C8.158 9.03269 8 9.41419 8 9.81219V13.3122C8 13.7102 8.158 14.0917 8.4395 14.3727C8.7205 14.6542 9.102 14.8122 9.5 14.8122H13C13.398 14.8122 13.7795 14.6542 14.0605 14.3727C14.342 14.0917 14.5 13.7102 14.5 13.3122V9.81219ZM12.3105 7.20869L14.3965 5.12269C14.982 4.53719 14.982 3.58719 14.3965 3.00169L12.3105 0.915687C11.725 0.330188 10.775 0.330188 10.1895 0.915687L8.1035 3.00169C7.518 3.58719 7.518 4.53719 8.1035 5.12269L10.1895 7.20869C10.775 7.79419 11.725 7.79419 12.3105 7.20869ZM7 2.31219C7 1.91419 6.842 1.53269 6.5605 1.25169C6.2795 0.970186 5.898 0.812187 5.5 0.812187C4.507 0.812187 2.993 0.812187 2 0.812187C1.602 0.812187 1.2205 0.970186 0.939499 1.25169C0.657999 1.53269 0.5 1.91419 0.5 2.31219V5.81219C0.5 6.21019 0.657999 6.59169 0.939499 6.87269C1.2205 7.15419 1.602 7.31219 2 7.31219H5.5C5.898 7.31219 6.2795 7.15419 6.5605 6.87269C6.842 6.59169 7 6.21019 7 5.81219V2.31219Z" fill="white"/>
                            </svg>
                    </button>
                </div>

                <div class="card __card h-100">
                    <div class="card-body">
                        <div class="">
                            <div class="d-flex align-items-center justify-content-between gap-2 mb-3">
                                <h5 class="mb-0 fs-16 font-bold">{{ translate('Support ticket') }}</h5>
                                <button type="submit" class="btn btn--primary btn-sm font-semibold float-end text-capitalize" data-toggle="modal" data-target="#open-ticket">
                                    {{ translate('Add New Ticket') }}
                                </button>
                            </div>
                            @include('web-views.partials._profile-module-tabs')

                            @if ($supportTickets->count() >0)
                                <div class="table-responsive">
                                    <table class="table mb-0 __table-2 fs-13">
                                        <thead class="thead-light fs-13 font-semibold">
                                        <tr>
                                            <th class="border-0">
                                                <span class="d-block fs-13 font-semibold">
                                                    {{ translate('Topic') }}
                                                </span>
                                            </th>
                                            <th class="border-0">
                                                <span class="d-block fs-13 font-semibold">
                                                    {{translate('Submission date')}}
                                                </span>
                                            </th>
                                            <th class="border-0">
                                                <span class="d-block fs-13 font-semibold">
                                                    {{translate('Type')}}
                                                </span>
                                            </th>
                                            <th class="border-0">
                                                <span class="d-block fs-13 font-semibold">
                                                    {{translate('Status')}}
                                                </span>
                                            </th>
                                            <th class="border-0 text-center">
                                                <span class="d-block fs-13 font-semibold">
                                                    {{translate('Action')}}
                                                </span>
                                            </th>
                                        </tr>
                                        </thead>

                                        <tbody>
                                        @foreach($supportTickets as $ticket)
                                            <tr>
                                                <td>
                                                    <span class="marl fs-13 font-semibold d-block max-w-320">
                                                        {{ Str::limit($ticket['subject'], 80, '...') }}
                                                    </span>
                                                </td>
                                                <td>
                                                    <span class="text-secondary-50 fs-12 font-semibold">
                                                        {{Carbon\Carbon::createFromFormat('Y-m-d H:i:s',$ticket['created_at'])->format('Y-m-d h:i A')}}
                                                    </span>
                                                </td>
                                                <td><span class="fs-13 font-semibold">{{translate($ticket['type'])}}</span></td>
                                                <td>
                                                    <span class="badge __badge rounded-full {{$ticket['status']=='open' ? 'badge-warning': 'badge-danger'}}">
                                                        {{translate($ticket['status'])}}
                                                    </span>
                                                </td>
                                                <td>
                                                    <div class="btn--container flex-nowrap justify-content-center">
                                                        <a class="__action-btn btn-shadow rounded-full text-primary"
                                                           href="{{route('support-ticket.index',$ticket['id'])}}">
                                                            <i class="fa fa-eye"></i>
                                                        </a>
                                                        <button class="__action-btn btn-shadow rounded-full text-danger marl delete-ticket-by-modal" data-link="{{ route('support-ticket.delete',['id'=>$ticket->id])}}">
                                                            <i class="czi-trash"></i>
                                                        </button>
                                                    </div>

                                                </td>
                                            </tr>
                                        @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            @endif

                            <div class="py-3">
                                {!! $supportTickets->links() !!}
                            </div>
                        </div>

                        @if ($supportTickets->count() <=0)
                            <div class="d-flex justify-content-center align-items-center h-75">
                                <div class="text-center pt-3 text-capitalize">
                                    <img src="{{theme_asset(path: 'public/assets/front-end/img/icons/nodata.svg')}}" alt="" width="70">
                                    <h5 class="fs-14 mt-4">{{translate('No ticket found')}}!</h5>
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
            </section>
        </div>

        <div class="d-flex align-items-center justify-content-between gap-2 mb-4 d-md-none">
            <h5 class="mb-0 fs-16 font-bold">{{translate('Support ticket')}}</h5>

            <button class="profile-aside-btn btn btn--primary px-2 rounded px-2 py-1 d-lg-none">
                <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 15 15" fill="none">
                    <path fill-rule="evenodd" clip-rule="evenodd" d="M7 9.81219C7 9.41419 6.842 9.03269 6.5605 8.75169C6.2795 8.47019 5.898 8.31219 5.5 8.31219C4.507 8.31219 2.993 8.31219 2 8.31219C1.602 8.31219 1.2205 8.47019 0.939499 8.75169C0.657999 9.03269 0.5 9.41419 0.5 9.81219V13.3122C0.5 13.7102 0.657999 14.0917 0.939499 14.3727C1.2205 14.6542 1.602 14.8122 2 14.8122H5.5C5.898 14.8122 6.2795 14.6542 6.5605 14.3727C6.842 14.0917 7 13.7102 7 13.3122V9.81219ZM14.5 9.81219C14.5 9.41419 14.342 9.03269 14.0605 8.75169C13.7795 8.47019 13.398 8.31219 13 8.31219C12.007 8.31219 10.493 8.31219 9.5 8.31219C9.102 8.31219 8.7205 8.47019 8.4395 8.75169C8.158 9.03269 8 9.41419 8 9.81219V13.3122C8 13.7102 8.158 14.0917 8.4395 14.3727C8.7205 14.6542 9.102 14.8122 9.5 14.8122H13C13.398 14.8122 13.7795 14.6542 14.0605 14.3727C14.342 14.0917 14.5 13.7102 14.5 13.3122V9.81219ZM12.3105 7.20869L14.3965 5.12269C14.982 4.53719 14.982 3.58719 14.3965 3.00169L12.3105 0.915687C11.725 0.330188 10.775 0.330188 10.1895 0.915687L8.1035 3.00169C7.518 3.58719 7.518 4.53719 8.1035 5.12269L10.1895 7.20869C10.775 7.79419 11.725 7.79419 12.3105 7.20869ZM7 2.31219C7 1.91419 6.842 1.53269 6.5605 1.25169C6.2795 0.970186 5.898 0.812187 5.5 0.812187C4.507 0.812187 2.993 0.812187 2 0.812187C1.602 0.812187 1.2205 0.970186 0.939499 1.25169C0.657999 1.53269 0.5 1.91419 0.5 2.31219V5.81219C0.5 6.21019 0.657999 6.59169 0.939499 6.87269C1.2205 7.15419 1.602 7.31219 2 7.31219H5.5C5.898 7.31219 6.2795 7.15419 6.5605 6.87269C6.842 6.59169 7 6.21019 7 5.81219V2.31219Z" fill="white"/>
                    </svg>
            </button>
        </div>

        <div class="d-flex flex-column gap-3 d-md-none">
            @foreach($supportTickets as $ticket)
            <div class="bg-white rounded p-3 shadow-sm get-view-by-onclick" data-link="{{ route('support-ticket.index', $ticket['id']) }}">
                <div class="d-flex justify-content-between gap-2 align-items-end">
                    <div class="d-flex flex-column gap-1 fs-12 flex-grow-1 overflow-hidden">
                        <div class="d-flex gap-2 fs-12 font-semibold">
                            <div class="text-muted text-nowrap flex-shrink-0">{{translate('Topic')}} :</div>
                            <div class="line--limit-2 text-break">{{ $ticket['subject'] }}</div>
                        </div>
                        <div class="d-flex gap-2 fs-12 font-semibold">
                            <div class="text-muted text-nowrap flex-shrink-0">{{translate('Submission date')}} :</div>
                            <div>{{Carbon\Carbon::createFromFormat('Y-m-d H:i:s',$ticket['created_at'])->format('Y-m-d h:i A')}}</div>
                        </div>
                        <div class="d-flex gap-2 fs-13 font-semibold">
                            <div class="text-muted text-nowrap flex-shrink-0">{{translate('Type')}} :</div>
                            <div class="text-break">{{ translate($ticket['type']) }}</div>
                        </div>
                        <div class="d-flex gap-2 fs-12 font-semibold">
                            <div class="text-muted text-nowrap flex-shrink-0">{{translate('Status')}} :</div>
                            <div>
                                <span class="badge __badge rounded-full {{$ticket['status']=='open' ? 'badge-warning': 'badge-danger'}}">
                                    {{translate($ticket['status'])}}
                                </span>
                            </div>
                        </div>
                    </div>
                    <div class="flex-shrink-0">
                        <button class="__action-btn btn-shadow rounded-full text-danger stopPropagation marl delete-ticket-by-modal fs-15" data-link="{{ route('support-ticket.delete',['id'=>$ticket->id])}}">
                            <i class="czi-trash"></i>
                        </button>
                    </div>
                </div>
            </div>
            @endforeach

            @if ($supportTickets->count() <=0)
                <div class="d-flex justify-content-center align-items-center h-100">
                    <div class="text-center pt-3 text-capitalize">
                        <img src="{{ theme_asset(path: 'public/assets/front-end/img/icons/nodata.svg') }}" alt="" width="70">
                        <h5 class="fs-14 mt-4">{{ translate('No ticket found') }}!</h5>
                    </div>
                </div>
            @endif

            {!! $supportTickets->links() !!}
        </div>

    </div>

    <div class="bottom-sticky_offset"></div>
    <div class="bottom-sticky_ele bg-white d-md-none p-3">
        <button type="submit" class="btn btn--primary w-100 font-semibold" data-toggle="modal" data-target="#open-ticket">
            {{translate('Add New Ticket')}}
        </button>
    </div>

    <div class="modal fade" id="delete-ticket-modal">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header border-0">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body pb-5">
                    <div class="text-center">
                        <img src="{{theme_asset(path: 'public/assets/front-end/img/icons/ticket-delete.png')}}" alt="">
                        <h6 class="font-semibold mt-3 mb-1">{{translate('Delete this ticket')}}?</h6>
                        <p class="mb-4">
                            <small>{{translate('This ticket will be removed from this list')}}</small>
                        </p>
                    </div>
                    <div class="d-flex gap-3 justify-content-center">
                        <a href="javascript:" class="btn btn-primary __rounded-10" id="delete-ticket-link">
                            {{translate('Remove')}}
                        </a>
                        <button class="btn btn-soft-secondary bg--secondary __rounded-10" data-dismiss="modal">
                            {{translate('Cancel')}}
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('script')
<script src="{{ theme_asset(path: 'public/assets/front-end/js/account-ticket.blade.js') }}" defer></script>
@endpush
