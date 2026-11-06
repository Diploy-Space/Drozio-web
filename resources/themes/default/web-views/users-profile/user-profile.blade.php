@extends('layouts.front-end.profile')

@section('title', translate('My Profile'))

@section('content')
    <div class="container py-2 py-md-4 p-0 p-md-2 user-profile-container px-5px">
        <div class="row">
            @include('web-views.partials._profile-aside')

            <section class="col-lg-9 __customer-profile px-0">
                <div class="card card-body border-0 mb-10px">
                    <div class="d-flex align-items-center justify-content-between gap-2 mb-3">
                        <h5 class="font-bold m-0 fs-16">{{ translate('Profile Information') }}</h5>

                        <button class="profile-aside-btn btn btn--primary px-2 rounded px-2 py-1 d-lg-none">
                            <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 15 15" fill="none">
                                <path fill-rule="evenodd" clip-rule="evenodd"
                                      d="M7 9.81219C7 9.41419 6.842 9.03269 6.5605 8.75169C6.2795 8.47019 5.898 8.31219 5.5 8.31219C4.507 8.31219 2.993 8.31219 2 8.31219C1.602 8.31219 1.2205 8.47019 0.939499 8.75169C0.657999 9.03269 0.5 9.41419 0.5 9.81219V13.3122C0.5 13.7102 0.657999 14.0917 0.939499 14.3727C1.2205 14.6542 1.602 14.8122 2 14.8122H5.5C5.898 14.8122 6.2795 14.6542 6.5605 14.3727C6.842 14.0917 7 13.7102 7 13.3122V9.81219ZM14.5 9.81219C14.5 9.41419 14.342 9.03269 14.0605 8.75169C13.7795 8.47019 13.398 8.31219 13 8.31219C12.007 8.31219 10.493 8.31219 9.5 8.31219C9.102 8.31219 8.7205 8.47019 8.4395 8.75169C8.158 9.03269 8 9.41419 8 9.81219V13.3122C8 13.7102 8.158 14.0917 8.4395 14.3727C8.7205 14.6542 9.102 14.8122 9.5 14.8122H13C13.398 14.8122 13.7795 14.6542 14.0605 14.3727C14.342 14.0917 14.5 13.7102 14.5 13.3122V9.81219ZM12.3105 7.20869L14.3965 5.12269C14.982 4.53719 14.982 3.58719 14.3965 3.00169L12.3105 0.915687C11.725 0.330188 10.775 0.330188 10.1895 0.915687L8.1035 3.00169C7.518 3.58719 7.518 4.53719 8.1035 5.12269L10.1895 7.20869C10.775 7.79419 11.725 7.79419 12.3105 7.20869ZM7 2.31219C7 1.91419 6.842 1.53269 6.5605 1.25169C6.2795 0.970186 5.898 0.812187 5.5 0.812187C4.507 0.812187 2.993 0.812187 2 0.812187C1.602 0.812187 1.2205 0.970186 0.939499 1.25169C0.657999 1.53269 0.5 1.91419 0.5 2.31219V5.81219C0.5 6.21019 0.657999 6.59169 0.939499 6.87269C1.2205 7.15419 1.602 7.31219 2 7.31219H5.5C5.898 7.31219 6.2795 7.15419 6.5605 6.87269C6.842 6.59169 7 6.21019 7 5.81219V2.31219Z"
                                      fill="white"/>
                            </svg>
                        </button>
                    </div>

                    <div class="row g-3">
                        <div class="col-6 col-lg-3">
                            <a href="{{ route('account-oder') }}" class="card __shadow h-100 text-center text-dark">
                                <div class="card-body">
                                    <img width="22" class="mb-2"
                                         src="{{ theme_asset(path: 'public/assets/front-end/img/icons/track_order.png') }}" alt="">
                                    <h5 class="font-bold fs-16 mb-1">{{ $total_order }}</h5>
                                    <span class="fs-13 text-capitalize">{{ translate('Orders') }}</span>
                                </div>
                            </a>
                        </div>

                        <div class="col-6 col-lg-3">
                            <a href="{{ route('wishlists.index') }}" class="card __shadow h-100 text-center text-dark">
                                <div class="card-body">
                                    <img width="22" class="mb-2"
                                         src="{{ theme_asset(path: 'public/assets/front-end/img/icons/wishlist.png') }}" alt="">
                                    <h5 class="font-bold fs-16 mb-1">{{ $wishlists }}</h5>
                                    <span class="fs-13 text-capitalize">{{ translate('Wish list') }}</span>
                                </div>
                            </a>
                        </div>

                        <div class="col-6 col-lg-3">
                            <a href="{{ route('wallet') }}" class="card __shadow h-100 text-center text-dark">
                                <div class="card-body">
                                    <img width="22" class="mb-2"
                                         src="{{ theme_asset(path: 'public/assets/front-end/img/icons/wallet-sm.png') }}" alt="">
                                    <h5 class="font-bold fs-16 mb-1">{{ webCurrencyConverter(amount: $totalWalletBalance ?? 0) }}</h5>
                                    <span class="fs-13 text-capitalize">{{ translate('My Wallet') }}</span>
                                </div>
                            </a>
                        </div>

                        <div class="col-6 col-lg-3">
                            <a href="{{ route('loyalty') }}" class="card __shadow h-100 text-center text-dark">
                                <div class="card-body">
                                    <img width="22" class="mb-2"
                                         src="{{ theme_asset(path: 'public/assets/front-end/img/icons/coin-success.png') }}" alt="">
                                    <h5 class="font-bold fs-16 mb-1">{{ $total_loyalty_point ?? 0 }}</h5>
                                    <span class="fs-13 text-capitalize">{{ translate('Loyalty point') }}</span>
                                </div>
                            </a>
                        </div>
                    </div>
                </div>

                <div class="card card-body border-0 mb-10px">
                    <div class="d-flex justify-content-between align-items-center gap-2 mb-3">
                        <h5 class="font-bold m-0 fs-16">{{ translate('Personal Details') }}</h5>
                        <a href="{{ route('user-account') }}"
                           class="btn btn--primary text-capitalize btn-sm d-flex align-items-center gap-1">
                            <i class="fi fi-rr-edit" aria-hidden="true"></i>
                            {{ translate('Edit profile') }}
                        </a>
                    </div>

                    <div class="d-flex align-items-center gap-3 mb-3">
                        <img class="rounded-circle avatar-60 object-fit-cover" width="60" height="60" alt=""
                             src="{{ getStorageImages(path: $customer_detail->image_full_url, type: 'avatar') }}">
                        <h6 class="font-bold fs-16 m-0 text-capitalize">
                            {{ $customer_detail['f_name'].' '.$customer_detail['l_name'] }}
                        </h6>
                    </div>

                    <dl class="profile-info-list profile-info-list--two-column">
                        <dt>{{ translate('First Name') }}</dt>
                        <dd>{{ $customer_detail['f_name'] }}</dd>
                        <dt>{{ translate('Last Name') }}</dt>
                        <dd>{{ $customer_detail['l_name'] }}</dd>
                        <dt>{{ translate('Phone') }}</dt>
                        <dd class="d-flex align-items-center gap-1">
                            <a class="text-dark" dir="ltr" href="tel:{{ $customer_detail['phone'] }}">{{ $customer_detail['phone'] }}</a>
                            @if($customer_detail['phone'] && getLoginConfig(key: 'phone_verification'))
                                @if($customer_detail['is_phone_verified'])
                                    <span class="cursor-pointer" data-toggle="tooltip" data-placement="top"
                                          title="{{ translate('Your phone is verified') }}">
                                        <img width="16" alt="" class="dark-support"
                                             src="{{ theme_asset(path: 'public/assets/front-end/img/icons/verified.svg') }}">
                                    </span>
                                @else
                                    <span class="cursor-pointer" data-toggle="tooltip" data-placement="top"
                                          title="{{ translate('Phone not verified.') }} {{ translate('Please verify through the user app') }}">
                                        <img width="16" alt="" class="dark-support"
                                             src="{{ theme_asset(path: 'public/assets/front-end/img/icons/verified-error.svg') }}">
                                    </span>
                                @endif
                            @endif
                        </dd>
                        <dt>{{ translate('Email') }}</dt>
                        <dd class="d-flex align-items-center gap-1">
                            <a class="text-dark text-break" href="mailto:{{ $customer_detail['email'] }}">{{ $customer_detail['email'] }}</a>
                            @if($customer_detail['email'] && getLoginConfig(key: 'email_verification'))
                                @if($customer_detail['is_email_verified'])
                                    <span class="cursor-pointer" data-toggle="tooltip" data-placement="top"
                                          title="{{ translate('Your email is verified') }}">
                                        <img width="16" alt="" class="dark-support"
                                             src="{{ theme_asset(path: 'public/assets/front-end/img/icons/verified.svg') }}">
                                    </span>
                                @else
                                    <span class="cursor-pointer" data-toggle="tooltip" data-placement="top"
                                          title="{{ translate('Email not verified.') }} {{ translate('Please verify through the user app.') }}">
                                        <img width="16" alt="" class="dark-support"
                                             src="{{ theme_asset(path: 'public/assets/front-end/img/icons/verified-error.svg') }}">
                                    </span>
                                @endif
                            @endif
                        </dd>
                    </dl>
                </div>

                <div class="card card-body border-0">
                    <div class="d-flex justify-content-between align-items-center gap-2 mb-3">
                        <h5 class="font-bold m-0 fs-16">{{ translate('My Address') }}</h5>
                        <a href="{{ route('account-address') }}"
                           class="btn btn--primary text-capitalize btn-sm d-flex align-items-center gap-1">
                            <img src="{{ theme_asset(path: 'public/assets/front-end/img/add-address-icon.png') }}" alt="">
                            {{ translate('Add address') }}
                        </a>
                    </div>

                    @if(count($addresses) == 0)
                        <div class="text-center text-capitalize pb-5 pt-5">
                            <img class="mb-4" width="70" alt=""
                                 src="{{ theme_asset(path: 'public/assets/front-end/img/icons/address.svg') }}">
                            <h5 class="fs-14">{{ translate('No address found') }}!</h5>
                        </div>
                    @endif

                    <div class="row g-3">
                        @foreach($addresses as $address)
                            @include('web-views.partials._profile-address-card', ['address' => $address])
                        @endforeach
                    </div>
                </div>
            </section>
        </div>
    </div>
@endsection
