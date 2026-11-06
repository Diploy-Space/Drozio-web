@extends('layouts.blank')

@section('title', '6valley Software Update')

@section('content')
    <div class="update-content">
        <div class="text-center text-white mb-4">
            <h2>{{ "6valley Software Update" }}</h2>
            <h6 class="fw-normal">
                {{ "Please confirm your purchase information to update the software" }}
            </h6>
        </div>

        @if($isUpdateUnfinished ?? false)
            <div class="alert alert-warning d-flex flex-column gap-1 mb-4" role="alert">
                <span class="fw-semibold">{{ "The files for version ".$installedFilesVersion." are installed, but the update has not finished yet." }}</span>
                <span>{{ "If an error appeared while uploading the update, that is expected. Submit this form once to complete the update." }}</span>
            </div>
        @endif

        <div class="card justify-content-center">
            <div class="p-4 mx-xl-4 px-md-5">
                <form method="POST" action="{{ Route::has('update-system') ? route('update-system') : 'javascript:void(0)' }}">
                    @csrf
                    <div class="bg-light p-4 rounded mb-4">
                        <div class="px-xl-2 pb-sm-3">
                            <div class="row gy-4">
                                <div class="col-md-6">
                                    <div class="from-group">
                                        <label for="person_name" class="d-flex align-items-center gap-2 mb-2">
                                            <span class="fw-medium">{{ "Name" }}</span>
                                            <span class="cursor-pointer" data-bs-toggle="tooltip"
                                                  data-bs-placement="top" data-bs-custom-class="custom-tooltip"
                                                  data-bs-html="true"
                                                  data-bs-title="{{ 'Enter your real full name.' }}">
                                                <img class="svg" alt=""
                                                     src="{{ dynamicAsset(path: 'public/assets/installation/assets/img/svg-icons/info2.svg') }}">
                                            </span>
                                        </label>
                                        <input type="text" id="person_name" class="form-control" name="name"
                                               placeholder="Ex: John Doe"
                                               value="{{ envFileValue('ADMIN_NAME') }}" required>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="from-group">
                                        <label for="person_email" class="d-flex align-items-center gap-2 mb-2">
                                            <span class="fw-medium">{{ "Email" }}</span>
                                            <span class="cursor-pointer" data-bs-toggle="tooltip"
                                                  data-bs-placement="top" data-bs-custom-class="custom-tooltip"
                                                  data-bs-html="true"
                                                  data-bs-title="{{ 'Enter your valid email address' }}">
                                                <img class="svg" alt=""
                                                     src="{{ dynamicAsset(path: 'public/assets/installation/assets/img/svg-icons/info2.svg') }}">
                                            </span>
                                        </label>
                                        <input type="email" id="person_email" class="form-control" name="email"
                                               placeholder="{{ 'Ex: your-mail@example.com' }}"
                                               value="{{ envFileValue('ADMIN_IDENTIFIER') != '' ? base64_decode(envFileValue('ADMIN_IDENTIFIER')) : '' }}"
                                               required>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="from-group">
                                        <label for="username" class="d-flex align-items-center gap-2 mb-2">
                                            <span class="fw-medium">{{ "Username" }}</span>
                                            <span class="cursor-pointer" data-bs-toggle="tooltip"
                                                  data-bs-placement="top" data-bs-custom-class="custom-tooltip"
                                                  data-bs-html="true"
                                                  data-bs-title="{{ 'The username of your account' }}">
                                                  <img class="svg" alt=""
                                                      src="{{ dynamicAsset(path: 'public/assets/installation/assets/img/svg-icons/info2.svg') }}">
                                            </span>
                                        </label>
                                        <input type="text" id="username" class="form-control" name="username"
                                               value="{{ envFileValue('BUYER_USERNAME') }}"
                                               placeholder="{{ "Ex: John Doe" }}" required>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="from-group">
                                        <label for="purchase_key" class="mb-2">{{ "Purchase Code" }}</label>
                                        <input type="text" id="purchase_key" class="form-control"
                                               name="purchase_key" value="{{ envFileValue('PURCHASE_CODE') }}"
                                               placeholder="{{ "Ex: 19xxxxxx-ca5c-49c2-83f6-696a738b0000" }}" required>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="text-center">
                        <button type="submit" class="btn btn-dark px-sm-5">{{ "Update" }}</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection
