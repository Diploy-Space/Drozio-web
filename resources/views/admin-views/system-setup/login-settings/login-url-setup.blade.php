@php
    use Illuminate\Support\Facades\Session;
@endphp
@extends('layouts.admin.app')

@section('title', translate('Login Url Setup'))

@section('content')
@php($direction = Session::get('direction'))
<div class="content container-fluid">
    <div class="mb-4 pb-2">
        <h2 class="h1 mb-0 text-capitalize d-flex align-items-center gap-2">
            <i class="fi fi-sr-sign-in-alt text-primary d-flex"></i>
            {{ translate('Login Settings') }}
        </h2>
    </div>
    @include('admin-views.system-setup.login-settings.partials.login-settings-menu')

    <div class="d-flex flex-column gap-3">
        <div class="bg-info bg-opacity-10 fs-12 px-12 py-10 text-dark rounded d-flex gap-2 align-items-center">
            <i class="fi fi-sr-lightbulb-on text-info"></i>
            <span>
                {{ translate('Set the URLs users follow to reach each login page.') }}.
            </span>
        </div>
        <form action="{{route('admin.settings.system-setup.login-settings.login-url-setup')}}" method="post" class="form-advance-validation form-advance-inputs-validation form-advance-file-validation non-ajax-form-validate" novalidate="novalidate">
            @csrf

            <div class="card">
                <div class="card-header">
                    <h3 class="mb-1 text-capitalize d-flex align-items-center gap-2">
                        <i class="fi fi-sr-shield-keyhole text-primary d-flex"></i>
                        {{ translate('Admin Login Page') }}
                    </h3>
                    <p class="mb-0 fs-12 text-muted">
                        {{ translate('Access the admin login page at this URL') }}
                    </p>
                </div>
                <div class="card-body">
                    <div class="p-12 p-sm-20 bg-section rounded">
                        <div class="d-flex flex-wrap gap-sm-20 gap-3 justify-content-end align-items-center">
                            <div class="flex-grow-1">
                                @php($adminLoginUrl = getWebConfig('admin_login_url'))
                                <div class="input-group border rounded trans3">
                                    <span class="input-group-text custom flex-grow-1 flex-xl-grow-0">{{ url('/').'/login/' }}</span>
                                    <input type="text" class="form-control" name="url" value="{{ $adminLoginUrl }}"  data-required-msg="{{ translate(':field is required', ['field' => translate('Admin login url')]) }}" required>
                                    <input type="hidden" class="form-control" name="type" value="admin_login_url">
                                </div>
                            </div>
                            <button type="{{ getDemoModeFormButton(type: 'button') }}" id="submit" class="btn btn-primary px-4 w-120 h-40 {{ getDemoModeFormButton(type: 'class') }}"><i class="fi fi-sr-disk"></i> {{translate('Save')}}</button>
                        </div>
                    </div>
                </div>
            </div>
        </form>
        <form action="{{route('admin.settings.system-setup.login-settings.login-url-setup')}}" method="post" class="form-advance-validation form-advance-inputs-validation form-advance-file-validation non-ajax-form-validate" novalidate="novalidate">
            @csrf
            <div class="card">
                <div class="card-header">
                    <h3 class="mb-1 text-capitalize d-flex align-items-center gap-2">
                        <i class="fi fi-sr-shield-keyhole text-primary d-flex"></i>
                        {{ translate('Employee Login Page') }}
                    </h3>
                    <p class="mb-0 fs-12 text-muted">
                        {{ translate('Access the employee login page at this URL') }}
                    </p>
                </div>
                <div class="card-body">
                    <div class="p-12 p-sm-20 bg-section rounded">
                        <div class="d-flex flex-wrap gap-sm-20 gap-3 justify-content-end align-items-center">
                            <div class="flex-grow-1">
                                @php($employeeLoginUrl = getWebConfig('employee_login_url'))
                                <div class="input-group border rounded trans3">
                                    <span class="input-group-text custom flex-grow-1 flex-xl-grow-0">{{ url('/').'/login/' }}</span>
                                    <input type="text" class="form-control" name="url" value="{{ $employeeLoginUrl }}" data-required-msg="{{ translate(':field is required', ['field' => translate('Employee login url')]) }}" required>
                                    <input type="hidden" class="form-control" name="type" value="employee_login_url">
                                </div>
                            </div>
                            <button type="{{ getDemoModeFormButton(type: 'button') }}" id="submit" class="btn btn-primary px-4 w-120 h-40 {{ getDemoModeFormButton(type: 'class') }}">
                                <i class="fi fi-sr-disk"></i> {{translate('Save')}}
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>
@include("layouts.admin.partials.offcanvas._login-url")
@endsection
