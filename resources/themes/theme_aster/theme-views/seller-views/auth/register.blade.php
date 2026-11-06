@extends('theme-views.layouts.app')

@section('title', translate('Vendor Apply').' | '.$web_config['company_name'].' '.translate('eCommerce'))

@push('css_or_js')
    <link rel="stylesheet" href="{{ theme_asset(path: 'assets/plugins/daterangepicker/daterangepicker.css') }}">
@endpush

@section('content')
    <main class="main-content d-flex flex-column gap-3 py-3 mb-sm-5">
        <form id="seller-registration" action="{{route('vendor.auth.registration.add') }}" method="POST" class="form-advance-validation form-advance-file-validation"
              enctype="multipart/form-data">
            @csrf
            <div class="first-el">
                <section>
                    <div class="container">
                        <div class="create-an-account p-3 p-sm-4">
                            <img src="{{theme_asset('assets/img/media/form-bg.png') }}" alt=""
                                 class="create-an-account-bg-img">
                            <div class="row">
                                @include('theme-views.seller-views.auth.partial.header')
                                <div class="col-lg-8">
                                    <div class="bg-white p-3 p-sm-4 rounded">
                                        <h4 class="mb-4">{{ translate('Create an Account') }}</h4>
                                        <div class="row">
                                            <div class="col-sm-6">
                                                <div class="form-group mb-4">
                                                    <label for="email">
                                                        {{ translate('Email') }}
                                                        <span class="text-danger">*</span>
                                                        <span class="text-danger fs-12 mail-error"></span>
                                                    </label>
                                                    <input class="form-control" type="email" id="email" name="email"
                                                           placeholder="{{ translate('Ex') }}: example@gmail.com"
                                                           required>
                                                </div>
                                            </div>
                                            <div class="col-sm-6">
                                                <div class="form-group mb-4">
                                                    <label for="tel">
                                                        {{ translate('Phone') }}
                                                        <span class="text-danger">*</span>
                                                        <span class="text-danger fs-12 phone-error"></span>
                                                    </label>
                                                    <div>
                                                        <input
                                                            class="form-control form-control-user phone-input-with-country-picker"
                                                            type="tel" name="phone"
                                                            placeholder="{{ translate('Enter phone number') }}"
                                                            required>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-sm-6">
                                                <div class="form-group mb-4">
                                                    <label for="password">
                                                        {{ translate('Password') }}
                                                        <span class="text-danger">*</span>
                                                        <span class="text-danger fs-12 password-error"></span>
                                                    </label>
                                                    <div class="input-inner-end-ele">
                                                        <input class="form-control password-check" type="password"
                                                               id="password" name="password"
                                                               value="{{old('password') }}"
                                                               placeholder="{{ translate('Enter your password') }}" required>
                                                        <i class="bi bi-eye-slash-fill togglePassword"></i>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-sm-6">
                                                <div class="form-group mb-4">
                                                    <label for="confirm_password">
                                                        {{ translate('Confirm password') }}
                                                        <span class="text-danger">*</span>
                                                        <span class="text-danger fs-12 confirm_password-error"></span>
                                                    </label>
                                                    <div class="input-inner-end-ele">
                                                        <input class="form-control" type="password"
                                                               id="confirm_password" name="confirm_password"
                                                               placeholder="{{ translate('Confirm password') }}"
                                                               required>
                                                        <i class="bi bi-eye-slash-fill togglePassword"></i>
                                                    </div>
                                                    <small class="text-danger confirm-password-error"></small>
                                                </div>
                                            </div>
                                            <div class="col-12">
                                                <div class="d-flex justify-content-end">
                                                    <button type="button" class="btn btn-primary proceed-to-next-btn">
                                                        {{ translate('Proceed to next') }}
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>

                @include('theme-views.seller-views.auth.partial.why-with-us')
                @include('theme-views.seller-views.auth.partial.business-process')
                @include('theme-views.seller-views.auth.partial.download-app')
                @include('theme-views.seller-views.auth.partial.faq')
            </div>

            @include('theme-views.seller-views.auth.partial.vendor-information-form')
        </form>

        <div class="modal fade registration-success-modal" tabindex="-1" aria-labelledby="toggle-modal"
             aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content shadow-lg">
                    <div class="modal-header border-0 pb-0 d-flex justify-content-end">
                        <button type="button" class="btn-close border-0" data-dismiss="modal" aria-label="Close"><i
                                class="tio-clear"></i></button>
                    </div>
                    <div class="modal-body px-4 px-sm-5 pt-0">
                        <div class="d-flex flex-column align-items-center text-center gap-2 mb-2">
                            <img src="{{ theme_asset('assets/img/congratulations.png') }}" width="70" class="mb-3 mb-20"
                                 alt="">
                            <h5 class="modal-title">{{ translate('Congratulations') }}</h5>
                            <div class="text-center">
                                {{ translate('Registration successful').', '.translate('Please-wait for admin approval').'.'.translate('You will get a mail soon') }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <span id="get-confirm-and-cancel-button-text" data-sure="{{ translate('Are you sure').'?'}}"
              data-message="{{ translate('Want to apply as a vendor').'?'}}"
              data-confirm="{{ translate('Yes') }}" data-cancel="{{ translate('No') }}"></span>
        <span id="proceed-to-next-validation-message"
              data-mail-error="{{ translate('Enter your email').'.'}}"
              data-phone-error="{{ translate('Enter phone number').'.'}}"
              data-valid-mail="{{ translate('Please enter a valid email address').'.'}}"
              data-enter-password="{{ translate('Enter your password').'.'}}"
              data-enter-confirm-password="{{ translate('Enter confirm password').'.'}}"
              data-password-not-match="{{ translate('Passwords do not match').'.'}}"
        >

    </span>
    </main>
@endsection
@push('script')
    <script>
        $('#vendor-apply-submit').on('click', function(e) {
            e.preventDefault();
            submitRegistration();
        });
    </script>

    <script src="{{theme_asset('assets/js/vendor-registration.js') }}" defer></script>
    <script src="{{theme_asset('assets/js/password-strength.js') }}" defer></script>
    <script src="https://cdn.jsdelivr.net/npm/moment@2.29.1/min/moment.min.js" defer></script>
    <script src="{{theme_asset('assets/plugins/daterangepicker/daterangepicker.min.js') }}" defer></script>
    <script src="{{ theme_asset('assets/js/file-upload/pdf.min.js') }}" defer></script>
    <script src="{{ theme_asset('assets/js/file-upload/pdf-worker.min.js') }}" defer></script>
    <script src="{{ theme_asset('assets/js/file-upload/multiple-document-upload.js') }}" defer></script>
    <script>
        $(document).ready(function () {
            $(".js-daterangepicker_single-date-with-placeholder").daterangepicker({
                singleDatePicker: true,
                autoUpdateInput: false,
                locale: {
                    cancelLabel: 'Clear'
                }
            });
            $(".js-daterangepicker_single-date-with-placeholder").on('apply.daterangepicker', function (ev, picker) {
                $(this).val(picker.startDate.format('MM/DD/YYYY'));
            });

            $(".js-daterangepicker_single-date-with-placeholder").on('cancel.daterangepicker', function (ev, picker) {
                $(this).val('');
            });

            function checkPasswordMatch() {
                const password = $('#password').val();
                const confirmPassword = $('#confirm_password').val();

                if (confirmPassword.length > 0 && password !== confirmPassword) {
                    $('.confirm-password-error').text('Password and confirm password does not match.');
                } else {
                    $('.confirm-password-error').text('');
                }

                $('.password-error').text('');
                $('.confirm_password-error').text('');
            }
            $('#user_password, #confirm_password').on('keyup change', function () {
                checkPasswordMatch();
            });
        });

    </script>
@endpush
