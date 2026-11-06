@extends('layouts.admin.app')

@section('title', translate('Employee list'))

@section('content')
    <div class="content container-fluid">
        <div class="mb-4">
            <h2 class="h1 mb-0 text-capitalize d-flex align-items-center gap-2">
                <img src="{{dynamicAsset(path: 'public/assets/back-end/img/employee.png')}}" width="20" alt="">
                {{translate('Employee list')}}
                <span class="badge badge-soft-dark radius-50">{{ $totalEmployees }}</span>
            </h2>
        </div>

        @include('admin-views.employee.partials._list-overview')

        <div class="card">
            <div class="card-body">
                <div class="d-flex justify-content-between gap-3 flex-wrap align-items-center mb-4">
                    <h3 class="mb-0 text-capitalize gap-2">
                        {{translate('Employee table')}}
                        <span class="badge badge-info text-bg-info">{{$employees->total()}}</span>
                    </h3>

                    <div class="d-flex flex-wrap gap-3 align-items-center justify-content-start justify-content-md-end min-w-100-mobile">
                        <div class="flex-grow-1 max-w-300 min-w-100-mobile">
                            <form action="{{ url()->current() }}" method="GET">
                                <div class="form-group mb-0">
                                    <div class="input-group">
                                        <input type="search" name="searchValue" class="form-control"
                                               placeholder="{{translate('Search by Name or Email or Phone')}}"
                                               value="{{ request('searchValue') }}">
                                        <div class="input-group-append search-submit">
                                            <button type="submit">
                                                <i class="fi fi-rr-search"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </form>
                        </div>

                        <a type="button" class="btn btn-outline-primary" href="{{route('admin.people.employee.export',['searchValue'=>request('searchValue')])}}">
                            <i class="fi fi-sr-inbox-in"></i>
                            <span class="fs-12">{{ translate('Export') }}</span>
                        </a>

                        <a href="{{route('admin.people.employee.add-new')}}" class="btn btn-primary text-nowrap">
                            <i class="fi fi-rr-plus-small"></i>
                            <span class="text">{{translate('Add New')}}</span>
                        </a>
                    </div>
                </div>

                <div class="table-responsive datatable-custom">
                    <table class="table table-hover table-borderless table-thead-bordered table-nowrap align-middle card-table w-100">
                        <thead class="thead-light thead-50 text-capitalize">
                            <tr>
                                <th>{{translate('Serial')}}</th>
                                <th>{{translate('Employee')}}</th>
                                <th>{{translate('Contact information')}}</th>
                                <th>{{translate('Role')}}</th>
                                <th class="text-center">{{translate('Status')}}</th>
                                <th class="text-center">{{translate('Action')}}</th>
                            </tr>
                        </thead>
                        <tbody>
                        @foreach($employees as $key => $employee)
                            <tr>
                                <td>{{$employees->firstItem()+$key}}</td>
                                <td>
                                    <a href="javascript:" data-id="{{ $employee['id'] }}"
                                       class="get-employee-details text-dark text-hover-primary d-flex align-items-center gap-10">
                                        <img class="avatar rounded-circle object-fit-cover" width="40" alt=""
                                             src="{{getStorageImages(path: $employee->image_full_url,type:'backend-profile')}}">
                                        <div class="text-capitalize max-w-200 line-1">
                                            {{$employee['name']}}
                                        </div>
                                    </a>
                                </td>
                                <td>
                                    @if(!empty($employee['email']))
                                        <div class="mb-1">
                                            <strong>
                                                <a class="text-dark text-hover-primary"
                                                   href="mailto:{{ $employee['email'] }}">{{ $employee['email'] }}</a>
                                            </strong>
                                        </div>
                                    @endif
                                    @if(!empty($employee['phone']))
                                        <a class="text-dark text-hover-primary"
                                           href="tel:{{ $employee['phone'] }}">{{ $employee['phone'] }}</a>
                                    @endif
                                </td>
                                <td>
                                    @if($employee?->role?->name)
                                        <label class="badge badge-soft-info">{{ ucwords($employee->role->name) }}</label>
                                    @else
                                        <span class="text-body-light">{{ translate('Not found') }}</span>
                                    @endif
                                </td>
                                <td class="text-center">
                                    @if($employee['id'] == 1)
                                        <label class="badge badge-primary-light">{{ translate('Admin') }}</label>
                                    @else
                                        <form action="{{route('admin.people.employee.status')}}" method="post" id="employee-id-{{$employee['id']}}-form" class="no-reload-form">
                                            @csrf
                                            <input type="hidden" name="id" value="{{$employee['id']}}">
                                            <label class="switcher mx-auto" for="employee-id-{{$employee['id']}}">
                                                <input
                                                    class="switcher_input custom-modal-plugin"
                                                    type="checkbox" value="1" name="status"
                                                    id="employee-id-{{$employee['id']}}"
                                                    {{$employee->status?'checked':''}}
                                                    data-modal-type="input-change-form"
                                                    data-modal-form="#employee-id-{{$employee['id']}}-form"
                                                    data-on-image="{{ dynamicAsset(path: 'public/assets/new/back-end/img/modal/employee-on.png') }}"
                                                    data-off-image="{{ dynamicAsset(path: 'public/assets/new/back-end/img/modal/employee-off.png') }}"
                                                    data-on-title = "{{translate('Want to Turn ON :item', ['item' => translate('Employee Status')]).'?'}}"
                                                    data-off-title = "{{translate('Want to Turn OFF :item', ['item' => translate('Employee Status')]).'?'}}"
                                                    data-on-message = "<p>{{translate('This employee can log in and perform their role.')}}</p>"
                                                    data-off-message = "<p>{{translate('This employee cannot log in or perform their role.')}}</p>"
                                                    data-on-button-text="{{ translate('Turn On') }}"
                                                    data-off-button-text="{{ translate('Turn Off') }}">
                                                <span class="switcher_control"></span>
                                            </label>
                                        </form>
                                    @endif
                                </td>
                                <td class="text-center">
                                    @if($employee['id'] == 1)
                                        <label class="badge badge-primary-light">{{ translate('Default') }}</label>
                                    @else
                                        <div class="d-flex gap-10 justify-content-center">
                                            <a class="btn btn-outline-info icon-btn get-employee-details"
                                               title="{{translate('View')}}" data-bs-toggle="tooltip"
                                               href="javascript:" data-id="{{ $employee['id'] }}">
                                                <i class="fi fi-rr-eye"></i>
                                            </a>
                                            <a href="{{route('admin.people.employee.update',[$employee['id']])}}"
                                               class="btn btn-outline-primary icon-btn"
                                               title="{{translate('Edit')}}" data-bs-toggle="tooltip">
                                               <i class="fi fi-rr-pencil"></i>
                                            </a>
                                        </div>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="mt-4">
                    <div class="px-4 d-flex justify-content-center justify-content-md-end">
                        {{$employees->links()}}
                    </div>
                </div>
                @if(count($employees)==0)
                    <div class="w-100">
                        @include('layouts.admin.partials._empty-state',['text'=>'no_employee_found'],['image'=>'default'])
                    </div>
                @endif
            </div>
        </div>
    </div>

    <div class="offcanvas offcanvas-end" tabindex="-1" id="offcanvasEmployeeDetails"
         aria-labelledby="offcanvasEmployeeDetailsLabel" style="--bs-offcanvas-width: 520px;"></div>

    <span id="get-employee-details-url" data-url="{{ route('admin.people.employee.quick-view') }}"></span>
    <span id="employee-not-found-message" data-text="{{ translate('Employee not found') }}"></span>
@endsection

@push('script')
    <script src="{{ dynamicAsset(path: 'public/assets/backend/admin/js/user-management/employee-details.js') }}"></script>
@endpush
