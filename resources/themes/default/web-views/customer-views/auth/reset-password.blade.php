@extends('layouts.front-end.profile')

@section('title', translate('Reset Password'))

@section('content')
    <div class="container py-4 py-lg-5 my-4">
        <div class="row justify-content-center">
            <div class="col-lg-8 col-md-10">
                <h2 class="h3 mb-4">{{ translate('Reset your password')}}</h2>
                <p class="font-size-md">{{ translate('Change your password in two easy steps.')}} {{ translate('This helps to keep your new password secure.')}}</p>
                <ol class="list-unstyled font-size-md">
                    <li><span class="text-primary mr-2">{{ translate('1')}}.</span>{{ translate('New Password.')}}</li>
                    <li><span class="text-primary mr-2">{{ translate('2')}}.</span>{{ translate('Confirm Password.')}}
                    </li>
                </ol>
                <div class="card py-2 mt-4">
                    <form class="card-body" method="POST"
                          action="{{ route('customer.auth.password-recovery') }}">
                        @csrf
                        <div class="form-group d-none">
                            <input type="hidden" name="reset_token" value="{{ $token }}" required>
                            <input type="hidden" name="identity" value="{{ request('identity') }}" required>
                        </div>

                        <div class="form-group">
                            <label for="si-password">{{ translate('New password')}}</label>
                            <div class="password-toggle">
                                <input class="form-control rtl" name="password" type="password" id="si-password"
                                       required placeholder="{{ translate('Enter new password') }}">
                                <label class="password-toggle-btn">
                                    <input class="custom-control-input" type="checkbox"><i
                                        class="czi-eye password-toggle-indicator"></i><span
                                        class="sr-only">{{ translate('Show password')}} </span>
                                </label>
                                <div class="invalid-feedback">{{ translate('Please provide valid password.')}}</div>
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="si-password">{{ translate('Confirm password')}}</label>
                            <div class="password-toggle rtl">
                                <input class="form-control" name="confirm_password" type="password" id="si-password"
                                       required placeholder="{{ translate('Enter confirm password') }}">
                                <label class="password-toggle-btn">
                                    <input class="custom-control-input" type="checkbox"><i
                                        class="czi-eye password-toggle-indicator"></i><span
                                        class="sr-only">{{ translate('Show password')}} </span>
                                </label>
                                <div class="invalid-feedback">{{ translate('Please provide valid password.')}}</div>
                            </div>
                        </div>

                        <button class="btn btn--primary" type="submit">{{ translate('Reset password') }}</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection
