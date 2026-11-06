@extends('layouts.admin.app')

@section('title', translate('Currency'))

@push('css_or_js')
    <link rel="stylesheet" href="{{ dynamicAsset(path: 'public/assets/back-end/css/admin/currency-setup.css') }}">
@endpush

@php
    $isMultiCurrency = $currencyModel['value'] == 'multi_currency';
    $defaultCurrencyCode = getCurrencyCode(type: 'default');
@endphp

@section('content')
    <div class="content container-fluid">
        <div class="mb-4 pb-2">
            <h2 class="h1 mb-0 text-capitalize d-flex align-items-center gap-2">
                <img src="{{ dynamicAsset(path: 'public/assets/back-end/img/system-setting.png') }}" alt="">
                {{ translate('System Setup') }}
            </h2>
        </div>

        @include('admin-views.system-setup.system-settings-inline-menu')

        <div class="row row-cols-2 row-cols-xl-4 g-3 mb-3" id="currency-metric-cards">
            <div class="col">
                <div class="card h-100 currency-metric-card">
                    <div class="card-body d-flex align-items-center gap-3">
                        <div class="stat-icon bg-primary bg-opacity-10 text-primary rounded d-flex align-items-center justify-content-center">
                            <i class="fi fi-sr-coins fs-5 d-flex"></i>
                        </div>
                        <div class="min-w-0">
                            <h3 class="mb-0 fw-bold lh-1">{{ $currencies->total() }}</h3>
                            <span class="text-muted fs-12 text-capitalize">{{ translate('Total currencies') }}</span>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col">
                <div class="card h-100 currency-metric-card">
                    <div class="card-body d-flex align-items-center gap-3">
                        <div class="stat-icon bg-success bg-opacity-10 text-success rounded d-flex align-items-center justify-content-center">
                            <i class="fi fi-sr-check-circle fs-5 d-flex"></i>
                        </div>
                        <div class="min-w-0">
                            <h3 class="mb-0 fw-bold lh-1">{{ $activeCurrencies->count() }}</h3>
                            <span class="text-muted fs-12 text-capitalize">{{ translate('Active') }}</span>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col">
                <div class="card h-100 currency-metric-card">
                    <div class="card-body d-flex align-items-center gap-3">
                        <div class="stat-icon bg-info bg-opacity-10 text-info rounded d-flex align-items-center justify-content-center">
                            <i class="fi fi-sr-star fs-5 d-flex"></i>
                        </div>
                        <div class="min-w-0">
                            <h3 class="mb-0 fw-bold lh-1 text-uppercase">{{ $defaultCurrencyCode }}</h3>
                            <span class="text-muted fs-12 text-capitalize">{{ translate('Default currency') }}</span>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col">
                <div class="card h-100 currency-metric-card">
                    <div class="card-body d-flex align-items-center gap-3">
                        <div class="stat-icon bg-warning bg-opacity-10 text-warning rounded d-flex align-items-center justify-content-center">
                            <i class="fi fi-sr-refresh fs-5 d-flex"></i>
                        </div>
                        <div class="min-w-0">
                            <h3 class="mb-0 fw-bold lh-1 text-capitalize">{{ $isMultiCurrency ? translate('Multi') : translate('Single') }}</h3>
                            <span class="text-muted fs-12 text-capitalize">{{ translate('Currency mode') }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="bg-info bg-opacity-10 fs-12 px-12 py-10 text-dark rounded d-flex gap-2 align-items-center mb-3">
            <i class="fi fi-sr-lightbulb-on text-info"></i>
            <span>
                {{ translate('Exchange rates are changeable, so please keep them updated.') }}
                {{ translate('Changing the default currency updates business settings automatically.') }}
            </span>
        </div>

        <div class="card">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center gap-3 flex-wrap mb-4">
                    <h3 class="mb-0 text-capitalize">
                        {{ translate('Currency list') }}
                        <span class="badge text-dark bg-body-secondary fw-semibold rounded-50">{{ $currencies->total() }}</span>
                    </h3>
                    <div class="d-flex flex-wrap gap-3 align-items-stretch currency-list-actions">
                        <form action="" method="GET" class="mb-0">
                            <div class="input-group currency-search">
                                <input id="datatableSearch_" type="search" name="searchValue" class="form-control"
                                       placeholder="{{ translate('Search by currency name') }}" value="{{ request()->searchValue }}">
                                <div class="input-group-append search-submit">
                                    <button type="submit"><i class="fi fi-rr-search"></i></button>
                                </div>
                            </div>
                        </form>
                        <button type="button" class="btn btn-primary text-nowrap" data-bs-toggle="offcanvas"
                                data-bs-target="#addCurrencyOffcanvas" aria-controls="addCurrencyOffcanvas">
                            <i class="fi fi-sr-add"></i>
                            {{ translate('Add New Currency') }}
                        </button>
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="table table-hover table-borderless table-thead-bordered table-nowrap table-align-middle card-table text-start">
                        <thead class="thead-light thead-50 text-capitalize">
                        <tr>
                            <th>{{ translate('Serial') }}</th>
                            <th>{{ translate('Currency Name') }}</th>
                            <th>{{ translate('Currency symbol') }}</th>
                            <th>{{ translate('Currency code') }}</th>
                            @if($isMultiCurrency)
                                <th>{{ translate('Exchange Rate') }} ({{ '1'.' '.$defaultCurrencyCode.' '.'='.'?' }})</th>
                            @endif
                            <th>{{ translate('Status') }}</th>
                            <th class="text-center">{{ translate('Action') }}</th>
                        </tr>
                        </thead>
                        <tbody>
                        @foreach($currencies as $key =>$currency)
                            <tr>
                                <td>{{$currencies->firstitem()+ $key }}</td>
                                <td class="max-w-200 overflow-hidden text-wrap">
                                    <div class="d-flex gap-2 align-items-center">
                                        <span>{{ $currency->name }}</span>
                                        @if(in_array($currency->id, [1, 2, 3, 4, 5, 6, 7]))
                                            <span class="tooltip-icon cursor-pointer" data-bs-toggle="tooltip"
                                                  aria-label="{{ translate('This currency cannot be deleted as it is associated with some features.') .' '. translate('To remove it from the website, disable its status instead.') }}"
                                                  data-bs-title="{{ translate('This currency cannot be deleted as it is associated with some features.') .' '. translate('To remove it from the website, disable its status instead.') }}">
                                                <img width="15" src="{{ dynamicAsset(path: 'public/assets/backend/media/icons/default-currency-icon.svg') }}" alt="">
                                            </span>
                                        @endif
                                    </div>
                                </td>
                                <td class="max-w-200 overflow-hidden text-wrap">{{ $currency->symbol }}</td>
                                <td class="max-w-200 overflow-hidden text-wrap">{{ $currency->code }}</td>
                                @if($isMultiCurrency)
                                    <td>{{ $currency->exchange_rate }}</td>
                                @endif
                                <td>
                                    @if($default['value'] != $currency->id)
                                        @if(($currency->status == 0) || ($currency->status == 1 && ($digitalPaymentStatus ? $currency->must_required_for_gateway != 1 : 1)))
                                            @include("admin-views.system-setup.currency._status-button-partial")
                                        @else
                                            <label class="switcher" for="currency-status{{ $currency['id'] }}"
                                                   data-bs-toggle="modal"
                                                   data-bs-target="#currency-modal{{ $currency['id'] }}">
                                                <input type="checkbox" class="switcher_input" disabled
                                                       id="currency-status{{$currency['id']}}" name="status"
                                                       value="1"
                                                       {{$currency->status ? 'checked' : '' }}
                                                       data-modal-id="toggle-status-modal"
                                                       data-toggle-id="currency-status{{$currency['id']}}"
                                                       data-on-image="currency-on.png"
                                                       data-off-image="currency-off.png"
                                                       data-on-title="{{ translate('Want to Turn ON :item', ['item' => translate('Currency Status')]).'?' }}"
                                                       data-off-title="{{ translate('Want to Turn OFF :item', ['item' => translate('Currency Status')]).'?' }}"
                                                       data-on-message="<p>{{ translate('If enabled this currency will be available throughout the entire system') }}</p>"
                                                       data-off-message="<p>{{ translate('If disabled this currency will be hidden from the entire system') }}</p>">
                                                <span class="switcher_control"></span>
                                            </label>
                                        @endif
                                    @else
                                        <label class="badge text-bg-info badge-info badge-sm">{{ translate('Default') }}</label>
                                    @endif
                                </td>
                                <td>
                                    <div class="d-flex justify-content-center">
                                        <div class="dropdown">
                                            <button class="btn btn-outline-primary btn-outline-primary-dark icon-btn" data-bs-toggle="dropdown">
                                                <i class="fi fi-sr-menu-dots-vertical"></i>
                                            </button>
                                            <div class="dropdown-menu">
                                                @if($default['value'] != $currency->id)
                                                    <button class="align-items-center d-flex dropdown-item gap-2 mark-as-default-currency"
                                                            data-ajax="{{ route('admin.settings.system-setup.currency.check-currency-update') }}"
                                                            data-code="{{ $currency->code }}">
                                                        <i class="fi fi-rr-check-circle mt-1"></i>
                                                        {{ translate('Mark As Default') }}
                                                    </button>
                                                @endif

                                                @if($currency->code != 'USD')
                                                    <a class="dropdown-item d-flex align-items-center gap-2 pointer"
                                                       data-bs-toggle="offcanvas"
                                                       data-bs-target="#offcanvas-currency-{{ str_replace(' ', '-', $currency->code) }}">
                                                        <i class="fi fi-rr-pen-circle mt-1"></i>
                                                        {{ translate('Edit') }}
                                                    </a>
                                                @else
                                                    <a class="dropdown-item d-flex align-items-center gap-2">
                                                        <i class="fi fi-rr-pen-circle mt-1"></i>
                                                        {{ translate('Non-editable') }}
                                                    </a>
                                                @endif

                                                @if($default['value'] == $currency->id && !in_array($currency->id, [1, 2, 3, 4, 5, 6, 7]))
                                                    <button class="align-items-center d-flex dropdown-item gap-2 default-currency-delete-alert">
                                                        <i class="fi fi-rr-trash mt-1"></i>
                                                        {{ translate('Delete') }}
                                                    </button>
                                                @elseif(!in_array($currency->id, [1, 2, 3, 4, 5, 6, 7]))
                                                    <button class="align-items-center d-flex dropdown-item gap-2"
                                                            data-bs-toggle="modal"
                                                            data-bs-target="#currencyDeleteModal{{ $currency->id }}">
                                                        <i class="fi fi-rr-trash mt-1"></i>
                                                        {{ translate('Delete') }}
                                                    </button>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="table-responsive mt-4">
                    <div class="px-4 d-flex justify-content-lg-end">
                        {{ $currencies->links() }}
                    </div>
                </div>
                @if(count($currencies) == 0)
                    @include('layouts.admin.partials._empty-state', ['text' => 'no_currency_found'], ['image'=>'default'])
                @endif
            </div>
        </div>

        @include("admin-views.system-setup.currency._currency-offcanvas")
    </div>

    <div class="offcanvas offcanvas-end" tabindex="-1" id="addCurrencyOffcanvas" aria-labelledby="addCurrencyOffcanvasLabel">
        <div class="offcanvas-header bg-body">
            <div>
                <h3 class="mb-1">{{ translate('Add New Currency') }}</h3>
                <p class="mb-0 fs-12 text-muted">{{ translate('Add different types of currency for different types of country.') }}</p>
            </div>
            <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
        </div>
        <div class="offcanvas-body">
            <form action="{{ route('admin.settings.system-setup.currency.store') }}" method="post" class="d-flex flex-column h-100" novalidate="novalidate">
                @csrf
                <div class="d-flex flex-column gap-3 flex-grow-1">
                    <div class="form-group">
                        <label class="form-label text-capitalize" for="name">
                            {{ translate('Currency Name') }} <span class="text-danger">*</span>
                            <span class="tooltip-icon" data-bs-toggle="tooltip" data-bs-placement="right"
                                  aria-label="{{ translate('Add the name of the currency you want to add') }}"
                                  data-bs-title="{{ translate('Add the name of the currency you want to add') }}">
                                <i class="fi fi-sr-info"></i>
                            </span>
                        </label>
                        <input type="text" name="name" class="form-control" id="name"
                               placeholder="{{ translate('Ex').': United States Dollar' }}"
                               data-required-msg="{{ translate(':field is required', ['field' => translate('Currency Name')]) }}" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label text-capitalize" for="symbol">
                            {{ translate('Currency symbol') }} <span class="text-danger">*</span>
                            <span class="tooltip-icon" data-bs-toggle="tooltip" data-bs-placement="right"
                                  aria-label="{{ translate('Add the symbol of the currency you want to add') }}"
                                  data-bs-title="{{ translate('Add the symbol of the currency you want to add') }}">
                                <i class="fi fi-sr-info"></i>
                            </span>
                        </label>
                        <input type="text" name="symbol" class="form-control" id="symbol"
                               data-required-msg="{{ translate(':field is required', ['field' => translate('Currency symbol')]) }}"
                               placeholder="{{ translate('Ex').':'.'$' }}" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label text-capitalize" for="currency_code">
                            {{ translate('Currency code') }} <span class="text-danger">*</span>
                            <span class="tooltip-icon" data-bs-toggle="tooltip" data-bs-placement="right"
                                  aria-label="{{ translate('Add the code of the currency you want to add') }}"
                                  data-bs-title="{{ translate('Add the code of the currency you want to add') }}">
                                <i class="fi fi-sr-info"></i>
                            </span>
                        </label>
                        <input type="text" name="code" class="form-control" id="currency_code"
                               data-required-msg="{{ translate(':field is required', ['field' => translate('Currency code')]) }}"
                               placeholder="{{ translate('Ex').':'.'USD' }}" required>
                    </div>
                    @if($isMultiCurrency)
                        <div class="form-group">
                            <label class="form-label text-capitalize" for="exchange_rate">
                                {{ translate('Exchange Rate') }} <span class="text-danger">*</span>
                                <span class="tooltip-icon" data-bs-toggle="tooltip" data-bs-placement="right"
                                      aria-label="{{ translate('Exchange rate for the currency you are adding.') }}"
                                      data-bs-title="{{ translate('Exchange rate for the currency you are adding.') }}">
                                    <i class="fi fi-sr-info"></i>
                                </span>
                            </label>
                            <input type="number" min="1" max="1000000" name="exchange_rate" step="any" class="form-control" id="exchange_rate"
                                   data-required-msg="{{ translate(':field is required', ['field' => translate('Exchange Rate')]) }}"
                                   placeholder="{{ translate('Ex').':'.'120' }}" required>
                        </div>
                    @endif
                </div>
                <div class="d-flex justify-content-end flex-wrap gap-3 pt-3 border-top">
                    <button type="reset" class="btn btn-secondary px-3 px-sm-4 w-120">{{ translate('Reset') }}</button>
                    <button type="submit" class="btn btn-primary px-3 px-sm-4"><i class="fi fi-sr-disk"></i> {{ translate('Submit') }}</button>
                </div>
            </form>
        </div>
    </div>

    <span id="get-currency-warning-message"
          data-title="{{ translate('Warning') }}!"
          data-message="{{ translate('This currency is currently set as the system default currency.').' '. translate('To delete this currency, please assign another currency as a default first.') }}"></span>

    <span id="get-delete-currency-message" data-success="{{ translate(':item removed successfully', ['item' => translate('Currency')]).'!' }}"
          data-warning="{{ translate('This Currency cannot be removed due to payment gateway dependency').'!' }}"></span>

    <div class="modal fade" id="defaultCurrencyChangeModal" tabindex="-1" aria-labelledby="defaultCurrencyChangeModal" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content shadow-lg">
                <div class="modal-header border-0 pb-0 d-flex justify-content-end">
                    <button type="button" class="btn-close border-0 btn-circle bg-section2 shadow-none" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body px-20 pb-20 pt-0"></div>
            </div>
        </div>
    </div>

    @foreach($currencies as $key =>$currency)
        <form action="{{ route('admin.settings.system-setup.currency.delete') }}" method="post">
            @csrf
            <input type="hidden" name="id" value="{{ $currency->id }}">
            <div class="modal fade" id="currencyDeleteModal{{ $currency->id }}" tabindex="-1"
                 aria-labelledby="currencyDeleteModal{{ $currency->id }}" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content">
                        <div class="modal-header border-0 pb-0 d-flex justify-content-end">
                            <button type="button" class="btn-close border-0 btn-circle bg-section2 shadow-none" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body px-20 py-0 mb-30">
                            <div class="d-flex flex-column align-items-center text-center mb-30">
                                <img src="{{dynamicAsset(path: 'public/assets/new/back-end/img/modal/delete.png')}}" width="80" class="mb-20" alt="">
                                <h2 class="modal-title mb-3">{{ translate('Want to delete this currency').' ?' }}</h2>
                                <div class="text-center">
                                    {{ translate('Are you sure you want to delete this currency').' ?' }}
                                    {{ translate('It will be permanently deleted from your database') }}
                                </div>
                            </div>
                            <div class="d-flex justify-content-center gap-3">
                                <button type="button" class="btn btn-secondary max-w-120 flex-grow-1" data-bs-dismiss="modal">
                                    {{ translate('No') }}
                                </button>
                                <button type="{{ getDemoModeFormButton(type: 'button') }}"
                                        class="btn btn-danger max-w-120 flex-grow-1 {{ getDemoModeFormButton(type: 'class') }}"
                                        data-bs-dismiss="modal">
                                    {{ translate('Yes Delete') }}
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    @endforeach

    @include("layouts.admin.partials.offcanvas._currency-setup")
@endsection

@push('script')
    <script src="{{ dynamicAsset(path: 'public/assets/backend/admin/js/system-setup/system-setup.js') }}"></script>
@endpush
