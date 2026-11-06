@extends('layouts.vendor.app')

@section('title',translate('Emergency Contact'))

@push('css_or_js')
    <link rel="stylesheet" href="{{ dynamicAsset(path: 'public/assets/back-end/css/vendor/emergency-contact.css') }}">
@endpush

@section('content')
    <div class="content container-fluid">
        <div class="mb-3">
            <h2 class="h1 mb-0 text-capitalize d-flex align-items-center gap-2">
                <img src="{{dynamicAsset(path: 'public/assets/back-end/img/add-new-delivery-man.png')}}" alt="">
                {{translate('Emergency Contact')}}
            </h2>
        </div>

        <div class="row g-3 mb-3" id="emergency-contact-overview-cards">
            <div class="col-6 col-lg-4">
                <div class="card h-100 emergency-contact-overview-card">
                    <div class="card-body d-flex align-items-center gap-3">
                        <div class="stat-icon badge-soft-primary rounded d-flex align-items-center justify-content-center">
                            <i class="fi fi-sr-address-book d-flex"></i>
                        </div>
                        <div class="min-w-0">
                            <h3 class="mb-0 font-weight-bold emergency-contact-overview-value">{{ $overview['total'] }}</h3>
                            <span class="text-muted fs-12 text-capitalize">{{ translate('Total contacts') }}</span>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-6 col-lg-4">
                <div class="card h-100 emergency-contact-overview-card">
                    <div class="card-body d-flex align-items-center gap-3">
                        <div class="stat-icon badge-soft-success rounded d-flex align-items-center justify-content-center">
                            <i class="fi fi-sr-check-circle d-flex"></i>
                        </div>
                        <div class="min-w-0">
                            <h3 class="mb-0 font-weight-bold emergency-contact-overview-value">{{ $overview['active'] }}</h3>
                            <span class="text-muted fs-12 text-capitalize">{{ translate('Active') }}</span>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-6 col-lg-4">
                <div class="card h-100 emergency-contact-overview-card">
                    <div class="card-body d-flex align-items-center gap-3">
                        <div class="stat-icon badge-soft-secondary rounded d-flex align-items-center justify-content-center">
                            <i class="fi fi-sr-cross-circle d-flex"></i>
                        </div>
                        <div class="min-w-0">
                            <h3 class="mb-0 font-weight-bold emergency-contact-overview-value">{{ $overview['inactive'] }}</h3>
                            <span class="text-muted fs-12 text-capitalize">{{ translate('Inactive') }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="px-3 py-4">
                        <div class="d-flex justify-content-between align-items-center gap-3 flex-wrap">
                            <h5 class="mb-0 d-flex align-items-center gap-2 text-capitalize">
                                {{translate('Contact List')}}
                                <span class="badge badge-soft-dark radius-50 fs-12">{{ $contacts->total() }}</span>
                            </h5>
                            <div class="d-flex align-items-center gap-3 flex-wrap flex-grow-1 justify-content-end">
                                <div class="flex-grow-1 max-w-300 min-w-100-mobile">
                                    <form action="{{url()->current()}}" method="GET">
                                        <div class="input-group">
                                            <input type="search" name="searchValue" class="form-control"
                                                   placeholder="{{translate('Search by Name or Phone')}}"
                                                   aria-label="{{translate('Search')}}"
                                                   value="{{ request('searchValue') }}">
                                            <div class="input-group-append search-submit">
                                                <button type="submit">
                                                    <i class="fi fi-rr-search"></i>
                                                </button>
                                            </div>
                                        </div>
                                    </form>
                                </div>
                                <button type="button" class="btn btn--primary text-nowrap"
                                        title="{{translate('Add new contact')}}"
                                        data-toggle="offcanvas" data-target="#emergencyContactAddOffcanvas">
                                    <i class="tio-add"></i>
                                    {{translate('Add New Contact')}}
                                </button>
                            </div>
                        </div>
                    </div>

                    <div class="table-responsive datatable-custom">
                        <table id="datatable" class="table table-hover table-borderless table-thead-bordered table-nowrap table-align-middle card-table w-100 text-left">
                            <thead class="thead-light thead-50 text-capitalize">
                            <tr>
                                <th>{{translate('Serial')}}</th>
                                <th>{{translate('Name')}}</th>
                                <th>{{translate('Phone')}}</th>
                                <th class="text-center">{{translate('Status')}}</th>
                                <th class="text-center">{{translate('Action')}}</th>
                            </tr>
                            </thead>

                            <tbody>
                            @foreach($contacts as $key => $contact)
                                <tr>
                                    <td>{{ $contacts->firstItem() + $key }}</td>
                                    <td class="text-capitalize">{{ $contact['name'] }}</td>
                                    <td>
                                        <a class="title-color hover-c1" href="tel:{{ $contact['phone'] }}">
                                            {{ $contact['phone'] }}
                                        </a>
                                    </td>
                                    <td>
                                        <form action="{{route('vendor.delivery-man.emergency-contact.index')}}" method="post" id="contact_status{{$contact['id']}}-form" class="contact_status_form">
                                            @csrf @method('patch')
                                            <input hidden name="id" value="{{$contact['id']}}">
                                            <label class="switcher mx-auto">
                                                <input type="checkbox" class="switcher_input toggle-switch-message" id="contact_status{{$contact['id']}}" name="status" value="1" {{ $contact->status == 1 ? 'checked':'' }}
                                                    data-modal-id = "toggle-status-modal"
                                                    data-toggle-id = "contact_status{{$contact['id']}}"
                                                    data-on-image = ""
                                                    data-off-image = ""
                                                    data-on-title = "{{translate('Turn ON The Status').'?'}}"
                                                    data-off-title = "{{translate('Turn OFF The Status').'?'}}"
                                                    data-on-message = "<p>{{translate('Turn on emergency contact?')}}</p>"
                                                    data-off-message = "<p>{{translate('Turn off emergency contact?')}}</p>"
                                                >
                                                <span class="switcher_control"></span>
                                            </label>
                                        </form>
                                    </td>
                                    <td>
                                        <div class="d-flex justify-content-center align-items-center gap-2">
                                            <button type="button" class="btn btn-outline--primary icon-btn"
                                                    title="{{translate('Edit')}}"
                                                    data-toggle="offcanvas"
                                                    data-target="#emergencyContactEditOffcanvas-{{$contact['id']}}">
                                                <i class="fi fi-rr-pencil"></i>
                                            </button>
                                            <a class="btn btn-outline-danger icon-btn delete delete-data" href="javascript:"
                                               data-id="delete-contact-{{$contact->id}}"
                                               title="{{ translate('Delete')}}">
                                                <i class="fi fi-rr-trash"></i>
                                            </a>
                                        </div>
                                        <form action="{{route('vendor.delivery-man.emergency-contact.index')}}"
                                              method="post" id="delete-contact-{{$contact->id}}">
                                            @csrf @method('delete')
                                            <input type="hidden" name="id" value="{{ $contact->id }}">
                                        </form>
                                    </td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                    </div>

                    @if(count($contacts)==0)
                        @include('layouts.vendor.partials._empty-state', ['text'=>'no_contact_found', 'image'=>'default', 'width' => 120])
                    @endif

                    <div class="table-responsive mt-4">
                        <div class="px-4 d-flex justify-content-center justify-content-md-end">
                            {{ $contacts->links() }}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @include('vendor-views.delivery-man.emergency-contact.offcanvas._emergency-contact-add')

    @foreach($contacts as $contact)
        @include('vendor-views.delivery-man.emergency-contact.offcanvas._emergency-contact-edit', ['contact' => $contact])
    @endforeach
@endsection

@push('script_2')
    <script src="{{ dynamicAsset(path: 'public/assets/back-end/js/vendor/emergency-contact.js') }}"></script>
@endpush
