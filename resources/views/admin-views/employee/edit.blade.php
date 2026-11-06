@extends('layouts.admin.app')

@section('title', translate('Employee Edit'))
@section('content')
<div class="content container-fluid">
    <div class="mb-4 d-flex align-items-center gap-3 min-w-0">
        <a class="btn btn-outline-secondary icon-btn flex-shrink-0"
           href="{{ route('admin.people.employee.list') }}"
           data-bs-toggle="tooltip" data-bs-title="{{ translate('Back to employee list') }}">
            <i class="fi fi-rr-angle-left"></i>
        </a>
        <div class="min-w-0">
            <h2 class="h1 mb-1 text-capitalize d-flex align-items-center gap-2 flex-wrap min-w-0">
                <img src="{{ dynamicAsset(path: 'public/assets/back-end/img/add-new-employee.png') }}" width="20" alt="">
                {{ $employee['name'] }}
                <span class="badge {{ $employee['status'] ? 'badge-soft-success' : 'badge-soft-danger' }}">
                    {{ $employee['status'] ? translate('Active') : translate('Inactive') }}
                </span>
            </h2>
            <span class="fs-12 text-body-light">{{ translate('The role you pick decides which modules this employee can open.') }}</span>
        </div>
    </div>

    <form action="{{route('admin.people.employee.update',[$employee['id']])}}" method="post" enctype="multipart/form-data"
            class="text-start form-advance-validation form-advance-inputs-validation form-advance-file-validation non-ajax-form-validate" novalidate="novalidate">
        @csrf
        <div class="card">
            <div class="card-body">
                <h3 class="mb-0 page-header-title d-flex text-capitalize align-items-center gap-2 border-bottom pb-3 mb-3">
                    <i class="fi fi-sr-user"></i>
                    {{translate('General Information')}}
                </h3>
                <div class="row g-4">

                    <div class="col-md-4">
                        <input type="hidden" name="id" value="{{$employee['id']}}">
                        <div class="form-group">
                            <label for="name" class="mb-2">{{translate('Full name')}} <span class="text-danger">*</span> </label>
                            <input type="text" name="name" class="form-control" id="name"
                                   placeholder="{{translate('Ex')}} : John Doe"
                                   value="{{$employee['name']}}" required data-required-msg="{{ translate('Full name field is required')}}">
                        </div>
                    </div>

                    <div class="col-md-4">
                        <div class="form-group">
                            <label for="phone" class="mb-2">{{translate('Phone')}} <span class="text-danger">*</span></label>
                            <input class="form-control form-control-user"
                                   type="tel" value="{{$employee ? $employee->phone  : ''}}"
                                   placeholder="{{ translate('Ex').': 017xxxxxxxx' }}" name="phone">
                        </div>
                    </div>

                    <div class="col-md-4">
                        <div class="form-group">
                            <label for="role_id" class="mb-2">{{translate('Role')}} <span class="text-danger">*</span></label>
                            <div class="select-wrapper">
                                <select class="form-select" name="role_id" id="role_id">
                                    <option value="0" selected disabled>{{'---'.translate('Select').'---'}}</option>
                                    @foreach($adminRoles as $adminRole)
                                        <option value="{{$adminRole->id}}" {{$adminRole['id']==$employee['admin_role_id']?'selected':''}}>{{ ucfirst($adminRole->name) }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-4">
                        <div class="form-group">
                            <label for="identify_type" class="mb-2">{{translate('Identify Type')}}</label>
                            <div class="select-wrapper">
                                <select class="form-select" name="identify_type" id="identify_type">
                                    <option value="" selected disabled>{{translate('Select Identify Type')}} </option>
                                    <option value="nid" {{$employee->identify_type == 'nid' ?'selected' : ''}}>{{translate('NID')}}</option>
                                    <option value="passport" {{$employee->identify_type == 'passport' ?'selected' : ''}}>{{translate('Passport')}}</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-8">
                        <div class="form-group">
                            <label for="identify_number" class="mb-2">{{translate('Identify Number')}}</label>
                            <input type="number" name="identify_number" value="{{$employee->identify_number}}" class="form-control"
                                   placeholder="{{translate('Ex').':'.'9876123123'}}" id="identify_number">
                        </div>
                    </div>

                    <div class="col-md-12">
                        <div class="p-12 p-sm-20 bg-section rounded">
                            <div class="row g-4">
                                <div class="col-md-4">
                                    <div class="card shadow-none">
                                        <div class="card-body">
                                            <div class="d-flex flex-column gap-20">
                                                <div>
                                                    <label for="" class="form-label fw-semibold mb-1 text-capitalize">
                                                        {{ translate('Employee Image') }} <span class="text-danger">*</span>
                                                    </label>
                                                    <p class="fs-12 mb-0">
                                                        {{ translate('Displayed as the employee avatar in the system.') }}
                                                    </p>
                                                </div>
                                                <div class="upload-file">
                                                    <input type="file" name="image" class="upload-file__input single_file_input" value="{{ getStorageImages(path: $employee->image_full_url, type: 'backend-placeholder') ?? '' }}" {{ $employee?->image_full_url ? '' : 'required' }}
                                                    data-max-size="{{ getFileUploadMaxSize() }}"
                                                           accept="{{ getFileUploadFormats(skip: '.svg,.gif') }}"
                                                           data-required-msg="{{ translate('Employee image field is required') }}">
                                                    <label
                                                        class="upload-file__wrapper">
                                                        <div class="upload-file-textbox text-center {{ $employee?->image_full_url ? 'd-none' : '' }}">
                                                            <img width="34" height="34" class="svg img-fluid" src="{{ getStorageImages(path: $employee?->image_full_url, type: 'backend-placeholder') }}" alt="image upload">
                                                            <h6 class="mt-1 fw-medium lh-base text-center text-capitalize">
                                                                <span class="text-info">{{ translate('Click to upload') }}</span>
                                                                <br>
                                                                {{ translate('Or drag and drop') }}
                                                            </h6>
                                                        </div>
                                                        <img class="upload-file-img" loading="lazy" src="{{ !empty($employee?->image_full_url) ? getStorageImages(path: $employee?->image_full_url, type: 'backend-placeholder') ?? '' :  '' }}"
                                                             data-default-src="{{ !empty($employee?->image_full_url) ? getStorageImages(path: $employee?->image_full_url,type: 'backend-placeholder') ?? '' :  '' }}" alt="">
                                                    </label>
                                                    <div class="overlay">
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
                                                    {{ getFileUploadFormats(skip: '.svg,.gif', asBladeMessage: true).' '. translate('Image size'). ' : '. translate('Max').' '. getFileUploadMaxSize() . 'MB' }}
                                                    <span class="fw-medium">
                                                        ({{ translate('Ratio') }} {{ "1:1" }})
                                                    </span>
                                                </p>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="col-md-8">
                                    <div class="card shadow-none h-100">
                                        <div class="card-body">
                                            <div class="d-flex flex-column gap-20">
                                                <div>
                                                    <label for="" class="form-label fw-semibold mb-1 text-capitalize">
                                                        {{ translate('Employee Identity Image') }}
                                                    </label>
                                                    <p class="fs-12 mb-0">
                                                        {{ translate('Upload the documents that help verify and identify the employee.') }}
                                                    </p>
                                                </div>
                                                <div class="d-flex flex-column">
                                                    <div class="position-relative">
                                                        <div class="multi_image_picker d-flex gap-20 pt-20"
                                                             data-ratio="1/1"
                                                             data-max-count="5"
                                                             data-max-filesize="{{ getFileUploadMaxSize() }}"
                                                             data-field-name="identity_image[]"
                                                             data-allowed-formats="{{ getFileUploadFormats(skip: '.svg,.gif') }}"
                                                             data-validation-error-msg="{{ translate('File size is too large Maximum').' '.getFileUploadMaxSize().' '.'MB' }}"
                                                        >
                                                            <div class="dir-ltr">
                                                                <div class="imageSlide_prev">
                                                                    <div class="d-flex justify-content-center align-items-center h-100">
                                                                        <button type="button"
                                                                                class="btn btn-circle border-0 bg-primary text-white">
                                                                            <i class="fi fi-sr-angle-left"></i>
                                                                        </button>
                                                                    </div>
                                                                </div>
                                                                <div class="imageSlide_next">
                                                                    <div class="d-flex justify-content-center align-items-center h-100">
                                                                        <button type="button"
                                                                                class="btn btn-circle border-0 bg-primary text-white">
                                                                            <i class="fi fi-sr-angle-right"></i>
                                                                        </button>
                                                                    </div>
                                                                </div>
                                                            </div>

                                                            @if($employee['identify_image'])
                                                                @foreach($employee->identify_images_full_url as $img)
                                                                    @php($unique_id = rand(1111, 9999))
                                                                    <div class="upload-file m-0 position-relative">
                                                                        <label class="upload-file__wrapper">
                                                                            <img class="upload-file-img" loading="lazy"
                                                                                 id="additional_Image_{{ $unique_id }}"
                                                                                 src="{{ getStorageImages(path: $img, type:'backend-basic') }}"
                                                                                 data-default-src="{{ getStorageImages(path: $img, type:'backend-basic') }}"
                                                                                 alt="">
                                                                        </label>
                                                                        <div class="overlay">
                                                                            <div
                                                                                class="d-flex gap-10 justify-content-center align-items-center h-100">
                                                                                <button type="button"
                                                                                        class="btn btn-outline-info icon-btn view_btn"
                                                                                        data-img="#additional_Image_{{ $unique_id }}">
                                                                                    <i class="fi fi-sr-eye"></i>
                                                                                </button>
                                                                            </div>
                                                                        </div>
                                                                    </div>
                                                                @endforeach
                                                            @endif
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card mt-3">
            <div class="card-body">
                <h3 class="mb-0 page-header-title d-flex align-items-center gap-2 border-bottom pb-3 mb-3">
                    <i class="fi fi-sr-user"></i>
                    {{translate('Account Information')}}
                </h3>
                <div class="row g-4">
                    <div class="col-md-4">
                        <div class="form-group">
                            <label for="email" class="mb-2">{{translate('Email')}}</label>
                            <input type="email" name="email" value="{{$employee['email']}}" class="form-control"
                                id="email" placeholder="{{translate('Ex').':'.'ex@gmail.com'}}" required data-required-msg="{{ translate('Email field is required')}}">
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label for="user_password" class="mb-2 d-flex gap-2 align-items-center">
                                {{translate('Password')}}
                                <span class="input-label-secondary cursor-pointer" data-bs-toggle="tooltip" data-bs-title="{{translate('Use at least 8 characters, including one uppercase letter.').','.translate('One lowercase letter').','.translate('One digit').','.translate('One special character').','.translate('And no spaces').'.'}}">
                                        <i class="fi fi-rr-info"></i>
                                    </span>
                            </label>
                            <div class="input-group">
                                <input type="password" class="js-toggle-password form-control password-check" name="password"  id="user_password" placeholder="{{ translate('Password minimum 8 characters') }}">
                                <div id="changePassTarget" class="input-group-append changePassTarget">
                                    <a class="text-body-light" href="javascript:">
                                        <i id="changePassIcon" class="fi fi-sr-eye"></i>
                                    </a>
                                </div>
                            </div>
                            <span class="text-danger password-error"></span>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label for="confirm_password" class="mb-2">
                                {{translate('Confirm password')}}
                            </label>

                            <div class="input-group">
                                <input type="password" class="js-toggle-password form-control" name="confirm_password"  id="confirm_password" placeholder="{{ translate('Confirm password') }}">
                                <div id="changeConfirmPassTarget" class="input-group-append changePassTarget">
                                    <a class="text-body-light" href="javascript:">
                                        <i id="changeConfirmPassIcon" class="fi fi-sr-eye"></i>
                                    </a>
                                </div>

                            </div>
                            <span class="text-danger confirm-password-error"></span>
                        </div>
                    </div>
                </div>
                <div class="d-flex justify-content-end gap-3 mt-4">
                    <button type="reset" class="btn btn-secondary">{{translate('Reset')}}</button>
                    <button type="submit" class="btn btn-primary">{{translate('Update')}}</button>
                </div>
            </div>
        </div>
    </form>
</div>

<span id="extension-error" data-text="{{ translate('Please only input a png or jpg type file') }}"></span>
<span id="size-error" data-text="{{ translate('File size is too large') }}"></span>
@endsection

@push('script')
    <script src="{{ dynamicAsset(path: 'public/assets/backend/admin/js/user-management/employee.js') }}"></script>
@endpush

