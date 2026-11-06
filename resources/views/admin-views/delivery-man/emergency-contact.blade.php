@extends('layouts.admin.app')

@section('title',translate('Emergency Contact'))

@push('css_or_js')
    <link rel="stylesheet" href="{{ dynamicAsset(path: 'public/assets/back-end/css/admin/emergency-contact.css') }}">
@endpush

@section('content')
    <div class="content container-fluid">
        <div class="mb-3">
            <h2 class="h1 mb-0 text-capitalize d-flex align-items-center gap-2">
                <img src="{{dynamicAsset(path: 'public/assets/back-end/img/add-new-delivery-man.png')}}" alt="">
                {{translate('Emergency Contact')}}
            </h2>
        </div>

        <div class="row row-cols-2 row-cols-lg-3 g-3 mb-3" id="emergency-contact-overview-cards">
            <div class="col">
                <div class="card h-100 emergency-contact-overview-card">
                    <div class="card-body d-flex align-items-center gap-3">
                        <div class="stat-icon bg-primary bg-opacity-10 text-primary rounded d-flex align-items-center justify-content-center">
                            <i class="fi fi-sr-address-book fs-5 d-flex"></i>
                        </div>
                        <div class="min-w-0">
                            <h3 class="mb-0 fw-bold emergency-contact-overview-value">{{ $overview['total'] }}</h3>
                            <span class="text-muted fs-12 text-capitalize">{{ translate('Total contacts') }}</span>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col">
                <div class="card h-100 emergency-contact-overview-card">
                    <div class="card-body d-flex align-items-center gap-3">
                        <div class="stat-icon bg-success bg-opacity-10 text-success rounded d-flex align-items-center justify-content-center">
                            <i class="fi fi-sr-check-circle fs-5 d-flex"></i>
                        </div>
                        <div class="min-w-0">
                            <h3 class="mb-0 fw-bold emergency-contact-overview-value">{{ $overview['active'] }}</h3>
                            <span class="text-muted fs-12 text-capitalize">{{ translate('Active') }}</span>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col">
                <div class="card h-100 emergency-contact-overview-card">
                    <div class="card-body d-flex align-items-center gap-3">
                        <div class="stat-icon bg-secondary bg-opacity-10 text-secondary rounded d-flex align-items-center justify-content-center">
                            <i class="fi fi-sr-cross-circle fs-5 d-flex"></i>
                        </div>
                        <div class="min-w-0">
                            <h3 class="mb-0 fw-bold emergency-contact-overview-value">{{ $overview['inactive'] }}</h3>
                            <span class="text-muted fs-12 text-capitalize">{{ translate('Inactive') }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-body d-flex flex-column gap-20">
                        <div class="d-flex justify-content-between align-items-center gap-20 flex-wrap">
                            <h3 class="mb-0 d-flex align-items-center gap-2 text-capitalize">
                                {{translate('Contact List')}}
                                <span class="badge text-dark bg-body-secondary fw-semibold rounded-50">
                                    {{ $contacts->total() }}
                                </span>
                            </h3>
                            <div class="d-flex align-items-center gap-3 flex-wrap flex-grow-1 justify-content-end">
                                <div class="max-w-300 min-w-100-mobile flex-grow-1">
                                    <form action="{{ url()->current() }}" method="get">
                                        <div class="input-group">
                                            <input type="search" name="searchValue" class="form-control"
                                                   placeholder="{{translate('Search by Name or Phone')}}"
                                                   value="{{ request('searchValue') }}">
                                            <div class="input-group-append search-submit">
                                                <button type="submit">
                                                    <i class="fi fi-rr-search"></i>
                                                </button>
                                            </div>
                                        </div>
                                    </form>
                                </div>
                                <button class="btn btn-primary text-nowrap" title="{{translate('Add new contact')}}"
                                        data-bs-toggle="offcanvas" href="#emergencyContactAddOffcanvas">
                                    + {{translate('Add New Contact')}}
                                </button>
                            </div>
                        </div>

                        <div class="table-responsive">
                            <table id="datatable" class="table table-hover table-borderless align-middle">
                                <thead class="text-capitalize">
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
                                            <a class="text-dark text-hover-primary" href="tel:{{ $contact['phone'] }}">
                                                {{ $contact['phone'] }}
                                            </a>
                                        </td>
                                        <td>
                                            <div class="d-flex justify-content-center">
                                                <form action="{{ route('admin.people.delivery-man.emergency-contact.ajax-status-change') }}" method="post" id="emergency-contact-status{{$contact->id}}-form" class="no-reload-form">
                                                    @csrf
                                                    <input name="id" value="{{$contact['id']}}" hidden>
                                                    <label class="switcher mx-auto" for="emergency-contact-status{{$contact->id}}">
                                                        <input
                                                            class="switcher_input custom-modal-plugin"
                                                            type="checkbox" value="1" name="status"
                                                            id="emergency-contact-status{{$contact->id}}"
                                                            {{ $contact['status'] == 1 ? 'checked' : '' }}
                                                            data-modal-type="input-change-form"
                                                            data-modal-form="#emergency-contact-status{{$contact->id}}-form"
                                                            data-on-image="{{ dynamicAsset(path: 'public/assets/backend/media/toggle-modal-icons/turn-on.svg') }}"
                                                            data-off-image="{{ dynamicAsset(path: 'public/assets/backend/media/toggle-modal-icons/turn-off.svg') }}"
                                                            data-on-title = "{{translate('Turn ON The Status').'?'}}"
                                                            data-off-title = "{{translate('Turn OFF The Status').'?'}}"
                                                            data-on-message="<p>{{translate('Turn on emergency contact?')}}</p>"
                                                            data-off-message="<p>{{translate('Turn off emergency contact?')}}</p>"
                                                            data-on-button-text="{{ translate('Yes') }}"
                                                            data-off-button-text="{{ translate('No') }}">
                                                        <span class="switcher_control"></span>
                                                    </label>
                                                </form>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="d-flex justify-content-center align-items-center gap-2">
                                                <a class="btn btn-outline-primary icon-btn"
                                                   title="{{translate('Edit')}}"
                                                   data-bs-toggle="offcanvas" href="#emergencyContactEditOffcanvas-{{$contact['id']}}">
                                                    <i class="fi fi-rr-pencil"></i>
                                                </a>
                                                <a class="btn btn-outline-danger icon-btn delete delete-data" href="javascript:"
                                                   data-id="delete-contact-{{$contact->id}}"
                                                   title="{{ translate('Delete')}}">
                                                    <i class="fi fi-rr-trash"></i>
                                                </a>
                                            </div>
                                            <form action="{{route('admin.people.delivery-man.emergency-contact.destroy')}}"
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

                        <div class="table-responsive mt-4">
                            <div class="px-4 d-flex justify-content-center justify-content-md-end">
                                {{ $contacts->links() }}
                            </div>
                        </div>

                        @if(count($contacts)==0)
                            @include('layouts.admin.partials._empty-state',['text'=>'no_contact_found'],['image'=>'default'])
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

    @include('admin-views.delivery-man.offcanvas._emergency-contact-add')

    @foreach($contacts as $contact)
        @include('admin-views.delivery-man.offcanvas._emergency-contact-edit', ['contact' => $contact])
    @endforeach
@endsection

@push('script')
    <script src="{{dynamicAsset(path: 'public/assets/back-end/js/admin/emergency-contact.js')}}"></script>
@endpush
