@extends('layouts.admin.app')

@section('title', $seller?->shop->name ?? translate('Shop Name'))

@section('content')
    @php($tinCertificates = collect($tinDocuments)->where('certificateUrl', '!=', null))

    <div class="content container-fluid">
        @include('admin-views.vendor.partials._detail-header')

        <div class="card mb-3">
            <div class="card-header border-0 py-4 vendor-detail-header">
                <div class="d-flex flex-wrap gap-3 justify-content-between">
                    <div class="d-flex flex-column flex-sm-row gap-20 align-items-sm-center mw-100">
                        <div class="flex-shrink-0 align-self-start align-self-sm-center overflow-hidden rounded-10 bg-white border p-1">
                            <img class="rounded-10 aspect-1 object-fit-cover" width="112" alt="{{ translate('Store') }}"
                                 src="{{ getStorageImages(path: $seller?->shop?->image_full_url, type: 'shop') }}">
                        </div>

                        <div class="d-flex flex-column gap-2 min-w-0">
                            <div class="d-flex align-items-center flex-wrap gap-2">
                                <h2 class="fs-24 mb-0 d-flex align-items-center gap-2">
                                    <span>{{ $seller->shop ? $seller->shop?->name : translate('Shop Name') . ' : ' . translate('Update Please') }}</span>
                                    @include('admin-views.vendor.partials._verified-badge', ['isVerified' => $seller->hasVerifiedBadge()])
                                </h2>
                                @if($closedBusinesses)
                                    <span class="badge badge-soft-danger fs-12 d-flex align-items-center gap-1">
                                        <i class="fi fi-rr-lock d-flex"></i>{{ translate('Temporary Closed') }}
                                    </span>
                                @elseif($isShopOnVacation)
                                    <span class="badge badge-soft-danger fs-12 d-flex align-items-center gap-1">
                                        <i class="fi fi-rr-umbrella-beach d-flex"></i>{{ translate('On Vacation') }}
                                    </span>
                                @elseif($vacationSchedule)
                                    <span class="badge badge-soft-warning fs-12 d-flex align-items-center gap-1">
                                        <i class="fi fi-rr-clock d-flex"></i>{{ translate('Vacation scheduled') }}
                                    </span>
                                @else
                                    <span class="badge badge-soft-success fs-12">{{ translate('Open') }}</span>
                                @endif
                            </div>

                            <div class="d-flex flex-wrap column-gap-4 row-gap-1 fs-14 text-muted">
                                @if($seller?->shop?->contact)
                                    <span class="d-flex align-items-center gap-2">
                                        <i class="fi fi-rr-phone-call d-flex"></i>{{ $seller->shop->contact }}
                                    </span>
                                @endif
                                @if($seller?->shop?->address)
                                    <span class="d-flex align-items-center gap-2">
                                        <i class="fi fi-rr-marker d-flex"></i>{{ $seller->shop->address }}
                                    </span>
                                @endif
                            </div>

                            @if($closedBusinesses || $vacationSchedule)
                                <div class="d-flex flex-column column-gap-4 row-gap-1 fs-12 text-muted bg-white border rounded-10 px-12 py-2 align-self-start mw-100">
                                    @if($closedBusinesses)
                                        <span class="d-flex align-items-center gap-1">
                                            <i class="fi fi-rr-lock d-flex"></i>{{ translate('Temporarily Closed For') }} :
                                            <span class="text-dark fw-semibold">{{ $closedBusinesses }}</span>
                                        </span>
                                    @elseif($vacationSchedule)
                                        <span class="d-flex align-items-center gap-1">
                                            <i class="fi fi-rr-briefcase d-flex"></i>{{ translate('Vacation For') }} :
                                            <span class="text-dark fw-semibold">{{ $vacationSchedule['businesses'] }}</span>
                                        </span>
                                        <span class="d-flex align-items-center gap-1">
                                            <i class="fi fi-rr-calendar d-flex"></i>{{ translate('Vacation Duration') }} :
                                            <span class="text-dark fw-semibold">
                                                @if($vacationSchedule['is_open_ended'])
                                                    {{ translate('Until turned off') }}
                                                @else
                                                    {{ $vacationSchedule['start_date'] }} - {{ $vacationSchedule['end_date'] }}
                                                @endif
                                            </span>
                                        </span>
                                    @endif
                                </div>
                            @endif
                        </div>
                    </div>

                    <div class="d-flex flex-column align-items-sm-stretch gap-3">
                        <div class="d-flex flex-wrap justify-content-sm-end gap-2">
                            @if ($isVerifiedBadgeEnabled)
                                @if ($seller->is_verified)
                                    <button type="button" class="btn btn-secondary text-capitalize verification-confirm"
                                            data-verification-variant="danger"
                                            data-verification-url="{{ route('admin.people.vendors.verification.revoke') }}"
                                            data-verification-payload='{"vendor_id":{{ $seller['id'] }}}'
                                            data-verification-title="{{ translate('Want to remove the Verified Badge from this vendor?') }}"
                                            data-verification-message="{{ translate('The vendor will lose its verified status, but you can reassign the badge at any time.') }}"
                                            data-verification-confirm="{{ translate('Yes') }}"
                                            data-verification-cancel="{{ translate('No') }}">
                                        {{ translate('Marked as unverified') }}
                                    </button>
                                @else
                                    <button type="button" class="btn btn-success text-capitalize verification-confirm"
                                            data-verification-variant="primary"
                                            data-verification-url="{{ route('admin.people.vendors.verification.grant') }}"
                                            data-verification-payload='{"vendor_ids":[{{ $seller['id'] }}]}'
                                            data-verification-title="{{ translate('Give Verified Badge to this vendor?') }}"
                                            data-verification-message="{{ translate('This will mark the vendor as verified, and a trusted badge will be displayed on the vendor details for customers.') }}"
                                            data-verification-confirm="{{ translate('Yes') }}"
                                            data-verification-cancel="{{ translate('No') }}">
                                        {{ translate('Give verified badge') }}
                                    </button>
                                @endif
                            @endif

                            @if ($seller['status'] == 'pending')
                                <form class="d-inline-block" action="{{ route('admin.people.vendors.update-status') }}" id="reject-form" method="POST">
                                    @csrf
                                    <input type="hidden" name="id" value="{{ $seller['id'] }}">
                                    <input type="hidden" name="status" value="rejected">
                                    <button type="button" class="btn btn-danger form-alert" data-message="{{ translate('Want to reject this vendor').'?' }}" data-id="reject-form">{{ translate('Reject') }}</button>
                                </form>
                                <form class="d-inline-block" action="{{ route('admin.people.vendors.update-status') }}" id="approve-form" method="POST">
                                    @csrf
                                    <input type="hidden" name="id" value="{{ $seller['id'] }}">
                                    <input type="hidden" name="status" value="approved">
                                    <button type="button" class="btn btn-success form-alert" data-message="{{ translate('Want to approve this vendor').'?' }}" data-id="approve-form">{{ translate('Approve') }}</button>
                                </form>
                            @elseif ($seller['status'] == 'approved')
                                <form class="flex-grow-1" action="{{ route('admin.people.vendors.update-status') }}" id="suspend-form" method="POST">
                                    @csrf
                                    <input type="hidden" name="id" value="{{ $seller['id'] }}">
                                    <input type="hidden" name="status" value="suspended">
                                    <button type="button" class="btn btn-danger w-100 px-5 form-alert" data-message="{{ translate('Want to suspend this vendor').'?' }}" data-id="suspend-form">{{ translate('Suspend this vendor') }}</button>
                                </form>
                            @elseif ($seller['status'] == 'suspended' || $seller['status'] == 'rejected')
                                <form class="flex-grow-1" action="{{ route('admin.people.vendors.update-status') }}" id="active-form" method="POST">
                                    @csrf
                                    <input type="hidden" name="id" value="{{ $seller['id'] }}">
                                    <input type="hidden" name="status" value="approved">
                                    <button type="button" class="btn btn-success w-100 px-5 form-alert" data-message="{{ translate('Want to active this vendor').'?' }}" data-id="active-form">{{ translate('Active') }}</button>
                                </form>
                            @endif
                        </div>

                        @if (count($storefrontLinks) > 0)
                            <div class="row g-2">
                                @foreach ($storefrontLinks as $storefrontLink)
                                    <div class="col">
                                        <a href="{{ $storefrontLink['url'] }}" class="btn btn-outline-primary w-100 text-nowrap" target="_blank">
                                            <i class="fi fi-rr-globe"></i>
                                            {{ $storefrontLink['label'] }}
                                        </a>
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            <div class="card-body border-top bg-body">
                @include('admin-views.vendor.partials._overview-modules')
            </div>

            <div class="card-body border-top">
                <div class="row g-4">
                    <div class="col-lg-4 col-md-6">
                        <h4 class="mb-3">{{ translate('Vendor information') }}</h4>

                        <div class="pair-list">
                            <div class="flex-nowrap overflow-wrap-anywhere">
                                <span class="key">{{ translate('Name') }}</span>
                                <span>:</span>
                                <span class="value text-capitalize">{{ $seller['f_name'] . ' ' . $seller['l_name'] }}</span>
                            </div>

                            <div class="flex-nowrap overflow-wrap-anywhere">
                                <span class="key">{{ translate('Email') }}</span>
                                <span>:</span>
                                <span class="value">{{ $seller['email'] }}</span>
                            </div>

                            <div class="flex-nowrap overflow-wrap-anywhere">
                                <span class="key">{{ translate('Phone') }}</span>
                                <span>:</span>
                                <span class="value">{{ $seller['phone'] }}</span>
                            </div>
                        </div>
                    </div>

                    <div class="col-lg-4 col-md-6">
                        <h4 class="mb-3">{{ translate('TIN information') }}</h4>

                        <div class="pair-list">
                            @foreach ($tinDocuments as $tinDocument)
                                <div class="flex-nowrap overflow-wrap-anywhere">
                                    <span class="key">{{ $tinDocument['label'] }}</span>
                                    <span>:</span>
                                    <span class="value">
                                        @if ($tinDocument['number'])
                                            {{ $tinDocument['number'] }}@if ($tinDocument['expireDate']), {{ \Carbon\Carbon::parse($tinDocument['expireDate'])->format('d/m/Y') }}@endif
                                        @else
                                            {{ translate('No TIN added') }}
                                        @endif
                                    </span>
                                </div>
                            @endforeach
                        </div>
                    </div>

                    @if ($seller['status'] != 'pending')
                        <div class="col-lg-4 col-md-6">
                            <h4 class="mb-3">{{ translate('Bank information') }}</h4>

                            <div class="pair-list">
                                <div class="flex-nowrap overflow-wrap-anywhere">
                                    <span class="key">{{ translate('Bank name') }}</span>
                                    <span>:</span>
                                    <span class="value">{{ $seller['bank_name'] ?? translate('No Data Found') }}</span>
                                </div>

                                <div class="flex-nowrap overflow-wrap-anywhere">
                                    <span class="key">{{ translate('Holder name') }}</span>
                                    <span>:</span>
                                    <span class="value">{{ $seller['holder_name'] ?? translate('No Data Found') }}</span>
                                </div>

                                <div class="flex-nowrap overflow-wrap-anywhere">
                                    <span class="key">{{ translate('Branch') }}</span>
                                    <span>:</span>
                                    <span class="value">{{ $seller['branch'] ?? translate('No Data Found') }}</span>
                                </div>

                                <div class="flex-nowrap overflow-wrap-anywhere">
                                    <span class="key">{{ translate('A/C No') }}</span>
                                    <span>:</span>
                                    <span class="value">{{ $seller['account_no'] ?? translate('No Data Found') }}</span>
                                </div>
                            </div>
                        </div>
                    @endif
                </div>

                @if ($tinCertificates->count() > 0)
                    <div class="row g-4 mt-0">
                        @foreach ($tinCertificates as $tinCertificate)
                            <div class="col-lg-4 col-md-6">
                                <h4 class="mb-3">{{ $tinCertificate['label'].' '.translate('TIN docs') }}</h4>

                                <div class="bg-body rounded px-12 py-10 d-flex gap-2 justify-content-between align-items-center flex-nowrap lh-1">
                                    <img width="28" class="aspect-1" src="{{ dynamicAsset(path: 'public/assets/back-end/img/icons/document.svg') }}" alt="">
                                    <div class="flex-grow-1 overflow-hidden">
                                        <h5 class="line-1 mb-1" title="{{ $tinCertificate['certificate'] }}">
                                            {{ $tinCertificate['certificate'] }}
                                        </h5>
                                        <p class="mb-0">{{ translate('Click to view the file') }}</p>
                                    </div>
                                    <a href="{{ $tinCertificate['certificateUrl'] }}" class="btn btn-outline-success icon-btn" download>
                                        <i class="fi fi-sr-down-to-line"></i>
                                    </a>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>

        @if ($seller['status'] != 'pending')
            <div class="card mt-3">
                <div class="card-body">
                    <div class="row justify-content-between align-items-center g-2 mb-3">
                        <div class="col-sm-6">
                            <h3 class="d-flex align-items-center text-capitalize gap-10 mb-0">
                                <img width="20" class="mb-1"
                                    src="{{ dynamicAsset(path: 'public/assets/back-end/img/admin-wallet.png') }}"
                                    alt="">
                                {{ translate('Vendor Wallet') }}
                            </h3>
                        </div>
                    </div>

                    <div class="row g-2" id="order_stats">
                        <div class="col-lg-4">
                            <div class="border rounded h-100 d-flex justify-content-center align-items-center">
                                <div class="card-body d-flex flex-column gap-10 align-items-center justify-content-center">
                                    <img width="48" class="mb-2"
                                        src="{{ dynamicAsset(path: 'public/assets/back-end/img/withdraw.png') }}"
                                        alt="">
                                    <h3 class="for-card-count mb-0 fz-24">
                                        {{ $seller->wallet ? setCurrencySymbol(amount: usdToDefaultCurrency(amount: $seller->wallet->total_earning)) : 0 }}
                                    </h3>
                                    <div class="font-weight-bold text-capitalize mb-30">
                                        {{ translate('Withdrawable balance') }}
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-8">
                            <div class="row g-2">
                                <div class="col-md-6">
                                    <div class="border rounded card-body h-100 justify-content-center">
                                        <div class="d-flex gap-2 justify-content-between align-items-center">
                                            <div class="d-flex flex-column align-items-start">
                                                <h3 class="mb-1 fz-24">
                                                    {{ $seller->wallet ? setCurrencySymbol(amount: usdToDefaultCurrency(amount: $seller->wallet->pending_withdraw)) : 0 }}
                                                </h3>
                                                <div class="text-capitalize mb-0">{{ translate('Pending Withdraw') }}
                                                </div>
                                            </div>
                                            <div>
                                                <img width="40" class="mb-2"
                                                    src="{{ dynamicAsset(path: 'public/assets/back-end/img/pw.png') }}"
                                                    alt="">
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="border rounded card-body h-100 justify-content-center">
                                        <div class="d-flex gap-2 justify-content-between align-items-center">
                                            <div class="d-flex flex-column align-items-start">
                                                <h3 class="mb-1 fz-24">
                                                    {{ $seller->wallet ? setCurrencySymbol(amount: usdToDefaultCurrency(amount: $seller->wallet->commission_given)) : 0 }}
                                                </h3>
                                                <div class="text-capitalize mb-0">
                                                    {{ translate('Total Commission given') }}</div>
                                            </div>
                                            <div>
                                                <img width="40"
                                                    src="{{ dynamicAsset(path: 'public/assets/back-end/img/tcg.png') }}"
                                                    alt="">
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="border rounded card-body h-100 justify-content-center">
                                        <div class="d-flex gap-2 justify-content-between align-items-center">
                                            <div class="d-flex flex-column align-items-start">
                                                <h3 class="mb-1 fz-24">
                                                    {{ $seller->wallet ? setCurrencySymbol(amount: usdToDefaultCurrency(amount: $seller->wallet->withdrawn)) : 0 }}
                                                </h3>
                                                <div class="text-capitalize mb-0">
                                                    {{ translate('Already Withdrawn') }}
                                                </div>
                                            </div>
                                            <div>
                                                <img width="40"
                                                    src="{{ dynamicAsset(path: 'public/assets/back-end/img/aw.png') }}"
                                                    alt="">
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="border rounded card-body h-100 justify-content-center">
                                        <div class="d-flex gap-2 justify-content-between align-items-center">
                                            <div class="d-flex flex-column align-items-start">
                                                <h3 class="mb-1 fz-24">
                                                    {{ $seller->wallet ? setCurrencySymbol(amount: usdToDefaultCurrency(amount: $seller->wallet->delivery_charge_earned)) : 0 }}
                                                </h3>
                                                <div class="text-capitalize mb-0">
                                                    {{ translate('Total delivery charge earned') }}</div>
                                            </div>
                                            <div>
                                                <img width="40"
                                                    src="{{ dynamicAsset(path: 'public/assets/back-end/img/tdce.png') }}"
                                                    alt="">
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="border rounded card-body h-100 justify-content-center">
                                        <div class="d-flex gap-2 justify-content-between align-items-center">
                                            <div class="d-flex flex-column align-items-start">
                                                <h3 class="mb-1 fz-24">
                                                    {{ $seller->wallet ? setCurrencySymbol(amount: usdToDefaultCurrency(amount: $seller->wallet->total_tax_collected)) : 0 }}
                                                </h3>
                                                <div class="text-capitalize mb-0">{{ translate('Total tax given') }}
                                                </div>
                                            </div>
                                            <div>
                                                <img width="40"
                                                    src="{{ dynamicAsset(path: 'public/assets/back-end/img/ttg.png') }}"
                                                    alt="">
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="border rounded card-body h-100 justify-content-center">
                                        <div class="d-flex gap-2 justify-content-between align-items-center">
                                            <div class="d-flex flex-column align-items-start">
                                                <h3 class="mb-1 fz-24">
                                                    {{ $seller->wallet ? setCurrencySymbol(amount: usdToDefaultCurrency(amount: $seller->wallet->collected_cash)) : 0 }}
                                                </h3>
                                                <div class="text-capitalize mb-0">{{ translate('Collected cash') }}</div>
                                            </div>
                                            <div>
                                                <img width="40"
                                                    src="{{ dynamicAsset(path: 'public/assets/back-end/img/cc.png') }}"
                                                    alt="">
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        @endif
    </div>

    <div id="file-assets"
        data-picture-icon="{{ dynamicAsset(path: 'public/assets/back-end/img/icons/picture.svg') }}"
        data-document-icon="{{ dynamicAsset(path: 'public/assets/back-end/img/icons/document.svg') }}"
        data-blank-thumbnail="{{ dynamicAsset(path: 'public/assets/back-end/img/blank.png') }}">
    </div>
@endsection


@push('script')
    <script src="{{ dynamicAsset(path: 'public/assets/back-end/js/file-upload/pdf.min.js') }}"></script>
    <script src="{{ dynamicAsset(path: 'public/assets/back-end/js/file-upload/pdf-worker.min.js') }}"></script>
    <script src="{{ dynamicAsset(path: 'public/assets/back-end/js/file-upload/document-view.js') }}"></script>
    @if ($isVerifiedBadgeEnabled)
        <script src="{{ dynamicAsset(path: 'public/assets/back-end/js/vendor-verification.js') }}"></script>
    @endif
@endpush
