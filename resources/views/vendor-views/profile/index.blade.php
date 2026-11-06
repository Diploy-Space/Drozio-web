@extends('layouts.vendor.app')

@section('title', translate('Bank Information View'))

@section('content')
    <div class="content container-fluid">
        <div class="card mb-3">
            <div class="card-body d-flex flex-wrap align-items-center gap-3">
                <span class="d-flex align-items-center justify-content-center bank-info-card rounded-10 h-40 min-w-40 text-primary flex-shrink-0">
                    <i class="fi fi-sr-bank d-flex"></i>
                </span>
                <div>
                    <h2 class="h1 mb-1 text-capitalize">{{ translate('My bank information') }}</h2>
                    <p class="fs-12 mb-0 text-muted">
                        {{ translate('Admin sends your approved withdraw payouts to this account') }}.
                    </p>
                </div>
            </div>
        </div>

        <div class="row g-3">
            <div class="col-xl-5">
                <div class="card bank-info-card border p-20 d-flex flex-column gap-3 bg-img bg-contain bg-bottom"
                     style="background-image: url({{ dynamicAsset(path: 'public/assets/back-end/img/wallet-bg.png') }});">
                    <div class="d-flex justify-content-between align-items-start gap-2">
                        <div class="min-w-0">
                            <span class="d-block fs-12 text-muted">{{ translate('Bank Name') }}</span>
                            <strong class="d-block title-color fs-16 text-break">
                                {{ $vendor->bank_name ?: translate('No Data Found') }}
                            </strong>
                        </div>
                        <i class="fi fi-sr-bank text-primary fs-16 d-flex"></i>
                    </div>

                    <div>
                        <span class="d-block fs-12 text-muted">{{ translate('Account Number') }}</span>
                        <strong class="d-block title-color fs-16 text-break">
                            {{ $vendor->account_no ?: translate('No Data Found') }}
                        </strong>
                    </div>

                    <div class="d-flex flex-wrap justify-content-between gap-2">
                        <div class="min-w-0">
                            <span class="d-block fs-12 text-muted">{{ translate('Holder Name') }}</span>
                            <strong class="d-block title-color text-break">
                                {{ $vendor->holder_name ?: translate('No Data Found') }}
                            </strong>
                        </div>
                        <div class="min-w-0 text-end">
                            <span class="d-block fs-12 text-muted">{{ translate('Branch Name') }}</span>
                            <strong class="d-block title-color text-break">
                                {{ $vendor->branch ?: translate('No Data Found') }}
                            </strong>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-xl-7">
                <div class="card h-100">
                    <div class="card-header">
                        <h3 class="mb-1 d-flex align-items-center gap-2">
                            {{ translate('Account details') }}
                            <i class="fi fi-sr-info cursor-pointer text-9B9B9B fs-14 d-flex" data-toggle="tooltip"
                               title="{{ translate('Update your bank details with correct information').'. '.translate('It will be used for your withdraw request transactions by admin').'.' }}"></i>
                        </h3>
                        <p class="fs-12 mb-0 text-muted">
                            {{ translate('Keep these details up to date so payouts are not delayed') }}.
                        </p>
                    </div>
                    <div class="card-body">
                        <form action="{{ route('vendor.profile.update-bank-info', [$vendor->id]) }}" method="post">
                            @csrf
                            <div class="bg-light rounded-10 p-20 mb-3">
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <div class="form-group mb-0">
                                            <label for="bank-name" class="form-label">
                                                {{ translate('Bank Name') }} <span class="text-danger">*</span>
                                            </label>
                                            <input type="text" class="form-control" id="bank-name" name="bank_name"
                                                   value="{{ old('bank_name', $vendor->bank_name) }}"
                                                   placeholder="{{ translate('Type bank name') }}" required>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group mb-0">
                                            <label for="branch-name" class="form-label">
                                                {{ translate('Branch Name') }} <span class="text-danger">*</span>
                                            </label>
                                            <input type="text" class="form-control" id="branch-name" name="branch"
                                                   value="{{ old('branch', $vendor->branch) }}"
                                                   placeholder="{{ translate('Type branch name') }}" required>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group mb-0">
                                            <label for="holder-name" class="form-label">
                                                {{ translate('Holder Name') }} <span class="text-danger">*</span>
                                            </label>
                                            <input type="text" class="form-control" id="holder-name" name="holder_name"
                                                   value="{{ old('holder_name', $vendor->holder_name) }}"
                                                   placeholder="{{ translate('Type account holder name') }}" required>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group mb-0">
                                            <label for="account-number" class="form-label">
                                                {{ translate('Account Number') }} <span class="text-danger">*</span>
                                            </label>
                                            <input type="text" class="form-control" id="account-number" name="account_no"
                                                   value="{{ old('account_no', $vendor->account_no) }}"
                                                   placeholder="{{ translate('Type account number') }}" required>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="d-flex flex-wrap justify-content-end gap-3">
                                <button type="reset" class="btn btn-secondary px-3 px-sm-4 min-w-120">
                                    {{ translate('Reset') }}
                                </button>
                                <button type="submit" class="btn btn--primary px-3 px-sm-4 min-w-120">
                                    <i class="fi fi-sr-disk"></i>
                                    {{ translate('Save Information') }}
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
