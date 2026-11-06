@extends('layouts.admin.app')

@section('title', translate('Withdraw Method List'))

@section('content')
    <div class="content container-fluid">
        <div class="mb-4 pb-2">
            <h2 class="h1 mb-0 text-capitalize d-flex align-items-center gap-2">
                <i class="fi fi-sr-credit-card text-primary d-flex"></i>
                {{ translate('Vendor Withdraw Methods') }}
            </h2>
        </div>

        <div class="card card-body">
            <div class="d-flex align-items-center justify-content-between flex-wrap gap-3 mb-4">
                <h3 class="flex-grow-1 mb-0">
                    {{ translate('Withdraw Method List') }}
                    <span class="badge badge-info text-bg-info"> {{ $withdrawalMethods->total() }}</span>
                </h3>
                <div class="d-flex align-items-stretch justify-content-md-end gap-3 flex-wrap flex-grow-1">
                    <form action="{{ url()->current() }}" method="GET" class="min-w-280 w-100-mobile">
                        <div class="input-group">
                            <input id="datatableSearch_" type="search" name="searchValue" class="form-control fs-12"
                                    placeholder="{{ translate('Search by withdraw method name') }}" aria-label="{{ translate('Search') }}"
                                    value="{{ request('searchValue') }}" >
                            <div class="input-group-append search-submit">
                                <button type="submit">
                                    <i class="fi fi-rr-search"></i>
                                </button>
                            </div>
                        </div>
                    </form>
                    <button type="button" class="btn btn-primary fs-12" data-bs-toggle="offcanvas" data-bs-target="#offcanvasWithdraw-add">
                        <i class="fi fi-sr-add d-flex"></i> {{ translate('Add New Method') }}
                    </button>
                </div>
            </div>

            <div class="table-responsive">
                <table id="datatable" class="table table-hover table-borderless table-nowrap align-middle text-dark">
                    <thead class="thead-light thead-50 text-capitalize">
                        <tr>
                            <th>{{ translate('Serial') }}</th>
                            <th>{{ translate('Method name') }}</th>
                            <th>{{ translate('Method fields') }}</th>
                            <th class="text-center">{{ translate('Default method') }}</th>
                            <th class="text-center">{{ translate('Status') }}</th>
                            <th class="text-center">{{ translate('Action') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                    @foreach($withdrawalMethods as $key => $withdrawalMethod)
                        <tr>
                            <td>{{ $withdrawalMethods->firstitem() + $key }}</td>
                            <td>
                                <span class="d-block text-wrap max-w-300">
                                    {{ $withdrawalMethod['method_name'] }}
                                </span>
                            </td>
                            <td>
                                @foreach($withdrawalMethod['method_fields'] as $methodField)
                                <span class="fs-12">
                                    {{ $methodField['input_name'] }}
                                    @if($methodField['is_required'])
                                        <span class="text-danger">*</span>
                                    @endif
                                    @if(!$loop->last)
                                        ,
                                    @endif
                                </span>
                                @endforeach
                            </td>
                            <td>
                                <form action="{{ route($routePrefix . '.default-status') }}" method="post"
                                        id="withdrawal-method-default{{ $withdrawalMethod['id'] }}-form"
                                        class="no-reload-form reload-true">
                                    @csrf
                                    <input type="hidden" name="id" value="{{ $withdrawalMethod['id'] }}">
                                    <label class="switcher mx-auto" for="withdrawal-method-default{{ $withdrawalMethod['id'] }}">
                                        <input
                                            class="switcher_input custom-modal-plugin"
                                            type="checkbox" value="1" name="status"
                                            id="withdrawal-method-default{{ $withdrawalMethod['id'] }}"
                                            {{ $withdrawalMethod['is_default'] == 1 ? 'checked':'' }}
                                            data-modal-type="input-change-form"
                                            data-modal-form="#withdrawal-method-default{{ $withdrawalMethod['id'] }}-form"
                                            data-on-image="{{ dynamicAsset(path: 'public/assets/new/back-end/img/modal/wallet-on.png') }}"
                                            data-off-image="{{ dynamicAsset(path: 'public/assets/new/back-end/img/modal/wallet-off.png') }}"
                                            data-on-title = "{{ translate('Do you want to turn on this as the default withdraw method').'?' }}"
                                            data-off-title = "{{ translate('Do you want to turn off this as the default withdraw method').'?' }}"
                                            data-on-message = "<p>{{ translate('Sets this as the default withdraw method for vendors.') }}</p>"
                                            data-off-message = "<p>{{ translate('Removes this as the default withdraw method for vendors.') }}</p>"
                                            data-on-button-text="{{ translate('Turn On') }}"
                                            data-off-button-text="{{ translate('Turn Off') }}">
                                        <span class="switcher_control"></span>
                                    </label>
                                </form>
                            </td>
                            <td>
                                <form action="{{ route($routePrefix . '.status-update') }}"
                                        method="post" id="withdrawal-method-status{{ $withdrawalMethod['id'] }}-form"
                                        class="no-reload-form">
                                    @csrf
                                    <input type="hidden" name="id" value="{{ $withdrawalMethod['id'] }}">
                                    <label class="switcher mx-auto" for="withdrawal-method-status{{ $withdrawalMethod['id'] }}">
                                        <input
                                            class="switcher_input custom-modal-plugin"
                                            type="checkbox" value="1" name="status"
                                            id="withdrawal-method-status{{ $withdrawalMethod['id'] }}"
                                            {{ $withdrawalMethod['is_active'] == 1 ? 'checked':'' }}
                                            data-modal-type="input-change-form"
                                            data-modal-form="#withdrawal-method-status{{ $withdrawalMethod['id'] }}-form"
                                            data-on-image="{{ dynamicAsset(path: 'public/assets/new/back-end/img/modal/wallet-on.png') }}"
                                            data-off-image="{{ dynamicAsset(path: 'public/assets/new/back-end/img/modal/wallet-off.png') }}"
                                            data-on-title = "{{ translate('Want to Turn ON This Withdraw Method').'?' }}"
                                            data-off-title = "{{ translate('Want to Turn OFF This Withdraw Method').'?' }}"
                                            data-on-message = "<p>{{ translate('Vendors will be able to withdraw with this method.') }}</p>"
                                            data-off-message = "<p>{{ translate('Vendors will no longer be able to withdraw with this method.') }}</p>"
                                            data-on-button-text="{{ translate('Turn On') }}"
                                            data-off-button-text="{{ translate('Turn Off') }}">
                                        <span class="switcher_control"></span>
                                    </label>
                                </form>
                            </td>
                            <td>
                                <div class="d-flex justify-content-center gap-2">
                                    <button type="button" class="btn btn-outline-primary icon-btn" data-bs-toggle="offcanvas" data-bs-target="#offcanvasWithdraw-{{ $withdrawalMethod->id }}">
                                        <i class="fi fi-rr-pencil"></i>
                                    </button>
                                    @if(!$withdrawalMethod->is_default)
                                        <a class="btn btn-outline-danger icon-btn delete-data" href="javascript:" title="{{ translate('Delete') }}" data-id="delete-{{ $withdrawalMethod->id }}">
                                            <i class="fi fi-rr-trash"></i>
                                        </a>
                                    @endif
                                </div>
                                <form action="{{ route($routePrefix . '.delete', [$withdrawalMethod->id]) }}" method="post" id="delete-{{ $withdrawalMethod->id }}">
                                    @csrf @method('delete')
                                </form>
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>

            <div class="table-responsive mt-4">
                <div class="px-4 d-flex justify-content-center justify-content-end">
                    {{ $withdrawalMethods->links() }}
                </div>
            </div>
            @if(count($withdrawalMethods)==0)
                @include('layouts.admin.partials._empty-state',['text'=>'no_withdraw_method_found'],['image'=>'default'])
            @endif
        </div>

        @include('admin-views.withdraw-method.partials._offcanvas', [
            'withdrawalMethod' => null,
            'addTitle' => translate('Add Vendor Withdraw method'),
            'editTitle' => translate('Edit Vendor Withdraw method'),
        ])
        @foreach($withdrawalMethods as $withdrawalMethod)
            @include('admin-views.withdraw-method.partials._offcanvas', [
                'withdrawalMethod' => $withdrawalMethod,
                'addTitle' => translate('Add Vendor Withdraw method'),
                'editTitle' => translate('Edit Vendor Withdraw method'),
            ])
        @endforeach
    </div>

    @include('layouts.admin.partials.offcanvas._withdraw-method-setup')
    <span id="get-withdrawal-method-default-text"
          data-success="{{ translate('Default method updated successfully').'.' }}"
          data-error="{{ translate('Default method update failed').'!!' }}">
    </span>
    <span id="get-add-filed-text"
          data-select-field-type="{{ translate('Select Field Type') }}"
          data-field-label="{{ translate('Field') }}"
          data-required-suffix="{{ translate('Is required') }}"
          data-input-filed="{{ translate('Input Field Type') }}"
          data-string="{{ translate('String') }}"
          data-number="{{ translate('Number') }}"
          data-date="{{ translate('Date') }}"
          data-password="{{ translate('Password') }}"
          data-email="{{ translate('Email') }}"
          data-phone="{{ translate('Phone') }}"
          data-field-name="{{ translate('Field name') }}"
          data-placeholder-text="{{ translate('Placeholder text') }}"
          data-required="{{ translate('This field required') }}"
          data-remove="{{ translate('Remove') }}"
          data-reached-maximum="{{ translate('Reached maximum') }}"
          data-confirm="{{ translate('Okay') }}">
    </span>
@endsection

@push('script')
    <script src="{{ dynamicAsset(path: 'public/assets/new/back-end/js/admin/withdraw-method.js') }}"></script>
@endpush
