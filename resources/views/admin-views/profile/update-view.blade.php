@extends('layouts.admin.app')

@section('title', translate('Profile Settings'))

@section('content')
    <div class="content container-fluid">

        <div class="d-flex justify-content-between align-items-center gap-3 flex-wrap mb-3 mb-sm-20">
            <h2 class="mb-0 text-capitalize d-flex align-items-center gap-2">
                <img width="20" src="{{ dynamicAsset(path: 'public/assets/back-end/img/profile_setting.png') }}" alt="">
                {{ translate('Profile Information') }}
            </h2>
        </div>

        <form action="{{ route('admin.profile.update', ['id' => $admin->id]) }}" method="post"
              enctype="multipart/form-data" id="admin-profile-form" class="form-advance-validation form-advance-file-validation"
              novalidate="novalidate">
            @csrf
            <div class="card mb-3 mb-sm-20" id="general-div">
                <div class="card-header">
                    <div class="d-flex align-items-center gap-3">
                        <img src="{{ dynamicAsset(path: 'public/assets/back-end/img/icons/user-1.svg') }}" alt="">
                        <div>
                            <h4 class="card-title m-0 fs-16">{{ translate('Basic Information') }}</h4>
                            <p class="fs-12 mb-0">{{ translate('Update your photo, name and login email') }}</p>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <div class="row gy-3">
                        <div class="col-lg-4">
                            <div class="p-12 p-sm-20 bg-section rounded h-100">
                                <div class="d-flex flex-column justify-content-center gap-20 h-100">
                                    <div class="d-flex flex-column align-items-center">
                                        <label class="form-label fw-semibold mb-1 text-capitalize">
                                            {{ translate('Profile image') }}
                                        </label>
                                        <p class="fs-12 mb-0">{{ translate('Upload your profile image') }}</p>
                                    </div>
                                    <div class="upload-file">
                                        <input type="file" name="image" class="upload-file__input single_file_input"
                                               data-required-msg="{{ translate('Image is required') }}"
                                               data-max-size="{{ getFileUploadMaxSize() }}"
                                               accept="{{ getFileUploadFormats() }}" value="" required="">
                                        <label class="upload-file__wrapper rounded-circle">
                                            <div class="upload-file-textbox text-center">
                                                <img width="34" height="34" class="svg"
                                                     src="{{ dynamicAsset(path: 'public/assets/new/back-end/img/svg/image-upload.svg') }}"
                                                     alt="image upload">
                                                <h6 class="mt-1 fw-medium lh-base text-center">
                                                    <span class="text-info">{{ translate('Click to upload') }}</span>
                                                    <br>
                                                    {{ translate('Or drag and drop') }}
                                                </h6>
                                            </div>
                                            <img class="upload-file-img" loading="lazy"
                                                 src="{{ $admin?->image ? getStorageImages(path: $admin->image_full_url, type: 'backend-profile') : '' }}"
                                                 data-default-src="" alt="">
                                        </label>
                                        <div class="overlay rounded-circle">
                                            <div class="d-flex gap-10 justify-content-center align-items-center h-100">
                                                <button type="button" class="btn btn-outline-info icon-btn view_btn">
                                                    <i class="fi fi-sr-eye"></i>
                                                </button>
                                                <button type="button" class="btn btn-outline-info icon-btn edit_btn">
                                                    <i class="fi fi-rr-camera"></i>
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                    <p class="fs-10 mb-0 text-center">
                                        {{ getFileUploadFormats() }} : {{ translate('Max') }} {{ getFileUploadMaxSize() }} MB
                                    </p>
                                </div>
                            </div>
                        </div>

                        <div class="col-lg-8">
                            <div class="p-12 p-sm-20 bg-section rounded h-100">
                                <div class="row gy-3">
                                    <div class="col-md-6">
                                        <div class="form-group mb-0">
                                            <label class="form-label" for="firstNameLabel">
                                                {{ translate('Full name') }}
                                                <span class="text-danger">*</span>
                                                <span class="tooltip-icon cursor-pointer" data-bs-toggle="tooltip"
                                                      data-bs-title="{{ translate('This will be displayed as your profile name') }}">
                                                    <i class="fi fi-sr-info"></i>
                                                </span>
                                            </label>
                                            <input type="text" class="form-control" name="name" id="firstNameLabel"
                                                   placeholder="{{ translate('Ex') }}: ABC"
                                                   value="{{ $admin->name }}">
                                        </div>
                                    </div>

                                    <div class="col-md-6">
                                        <div class="form-group mb-0">
                                            <label class="form-label" for="phoneLabel">
                                                {{ translate('Phone number') }}
                                                <span class="text-danger">*</span>
                                            </label>
                                            <input type="tel" class="form-control" name="phone" id="phoneLabel"
                                                   placeholder="{{ translate('Ex') }}: 123456789"
                                                   value="{{ $admin->phone }}">
                                        </div>
                                    </div>

                                    <div class="col-12">
                                        <div class="form-group mb-0">
                                            <label class="form-label" for="newEmailLabel">
                                                {{ translate('Email') }}
                                                <span class="text-danger">*</span>
                                                <span class="tooltip-icon cursor-pointer" data-bs-toggle="tooltip"
                                                      data-bs-title="{{ translate('You can login to your panel by using this email') }}">
                                                    <i class="fi fi-sr-info"></i>
                                                </span>
                                            </label>
                                            <input type="email" class="form-control" name="email" id="newEmailLabel"
                                                   placeholder="{{ translate('Ex') }}: admin@admin.com"
                                                   value="{{ $admin->email }}">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="d-flex justify-content-end gap-3 mt-3">
                        <button type="button" data-id="admin-profile-form"
                                data-message="{{ translate('Want to update admin information').'?' }}"
                                class="btn btn-primary px-4 w-120 {{ config('app.mode') != 'demo' ? 'form-alert' : 'call-demo-alert' }}">
                            <i class="fi fi-sr-disk"></i>
                            {{ translate('Save') }}
                        </button>
                    </div>
                </div>
            </div>
        </form>

        <div class="card" id="password-div">
            <div class="card-header">
                <div class="d-flex align-items-center gap-3">
                    <img src="{{ dynamicAsset(path: 'public/assets/back-end/img/icons/password-lock.svg') }}" alt="">
                    <div>
                        <h4 class="card-title m-0 fs-16">{{ translate('Change Password') }}</h4>
                        <p class="fs-12 mb-0">{{ translate('Use a strong password to keep your panel secure') }}</p>
                    </div>
                </div>
            </div>
            <div class="card-body">
                <form class="change-admin-password-form"
                      action="{{ route('admin.profile.update', ['id' => $admin->id]) }}" method="post">
                    @csrf @method('patch')

                    <div class="p-12 p-sm-20 bg-section rounded">
                        <div class="row gy-3">
                            <div class="col-md-6">
                                <div class="form-group mb-0">
                                    <label class="form-label" for="newPassword">
                                        {{ translate('New password') }}
                                        <span class="text-danger">*</span>
                                    </label>
                                    <div class="input-group">
                                        <input type="password"
                                               class="js-toggle-password form-control password-check password-input-element"
                                               id="newPassword" name="password" required minlength="8" autocomplete="off"
                                               placeholder="{{ translate('Enter new password') }}">
                                        <div id="changePassTarget" class="input-group-append changePassTarget">
                                            <a class="text-body-light" href="javascript:">
                                                <i id="changePassIcon" class="fi fi-sr-eye"></i>
                                            </a>
                                        </div>
                                    </div>
                                    <span class="text-danger pt-1 d-block password-error"></span>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="form-group mb-0">
                                    <label class="form-label" for="confirmNewPasswordLabel">
                                        {{ translate('Confirm password') }}
                                        <span class="text-danger">*</span>
                                    </label>
                                    <div class="input-group">
                                        <input type="password"
                                               class="js-toggle-password form-control confirm-password-check confirm-password-input-element"
                                               id="confirmNewPasswordLabel" name="confirm_password" required autocomplete="off"
                                               placeholder="{{ translate('Enter confirm password') }}">
                                        <div id="changeConfirmPassTarget" class="input-group-append changePassTarget">
                                            <a class="text-body-light" href="javascript:">
                                                <i id="changeConfirmPassIcon" class="fi fi-sr-eye"></i>
                                            </a>
                                        </div>
                                    </div>
                                    <span class="text-danger pt-1 d-block confirm-password-error"></span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <ul class="list-unstyled d-flex flex-wrap gap-3 fs-12 mt-3 mb-0">
                        <li class="d-flex align-items-center gap-2">
                            <i class="fi fi-sr-check-circle text-success d-flex"></i>
                            {{ translate('At least :count characters', ['count' => 8]) }}
                        </li>
                        <li class="d-flex align-items-center gap-2">
                            <i class="fi fi-sr-check-circle text-success d-flex"></i>
                            {{ translate('At least one uppercase letter') }}
                        </li>
                        <li class="d-flex align-items-center gap-2">
                            <i class="fi fi-sr-check-circle text-success d-flex"></i>
                            {{ translate('One lowercase letter') }}
                        </li>
                        <li class="d-flex align-items-center gap-2">
                            <i class="fi fi-sr-check-circle text-success d-flex"></i>
                            {{ translate('One digit') }}
                        </li>
                        <li class="d-flex align-items-center gap-2">
                            <i class="fi fi-sr-check-circle text-success d-flex"></i>
                            {{ translate('One special character') }}
                        </li>
                        <li class="d-flex align-items-center gap-2">
                            <i class="fi fi-sr-check-circle text-success d-flex"></i>
                            {{ translate('And no spaces') }}
                        </li>
                    </ul>

                    <div class="d-flex justify-content-end gap-3 mt-3">
                        <button type="{{ getDemoModeFormButton(type: 'button') }}"
                                class="btn btn-primary px-4 w-120 {{ getDemoModeFormButton(type: 'class') }}">
                            <i class="fi fi-sr-disk"></i>
                            {{ translate('Save') }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection
