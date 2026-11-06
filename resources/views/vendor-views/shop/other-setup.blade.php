@extends('layouts.vendor.app')

@section('title', translate('Other Setup'))

@section('content')
    <div class="content container-fluid">
        <h2 class="h1 mb-0 text-capitalize d-flex mb-3">
            {{ translate('Business Setup') }}
        </h2>

        @include('vendor-views.shop.inline-menu')

        <form action="{{ route('vendor.shop.update-other-settings') }}" method="post" enctype="multipart/form-data" class="form-advance-validation  form-advance-inputs-validation form-advance-file-validation non-ajax-form-validate" novalidate="novalidate">
            @csrf
            <div class="card card-body mb-3">
                <div class="mb-4">
                    <h3 class="mb-1">{{ translate('Order Setup') }}</h3>
                    <p class="fs-12 mb-0">
                        {{ translate('Here you can configure the settings related to orders') }}
                    </p>
                </div>

                <div class="bg-light rounded-10 p-20 mb-3">
                    <div class="row mb-n3">
                        @if ($showMinimumOrderAmount)
                            <div class="col-lg-6 mb-3">
                                <div class="form-group mb-0">
                                    <label class="form-label" for="minimum-order-amount">
                                        {{ translate('Retail minimum order amount') }}
                                        ({{ getCurrencySymbol() }})
                                        <i class="fi fi-sr-info cursor-pointer text-9B9B9B fs-12" data-toggle="tooltip"
                                           title="{{ translate('Minimum order amount customers must reach for your products.') }}"></i>
                                    </label>
                                    <input type="number" step="any" class="form-control w-100"
                                           id="minimum-order-amount" name="minimum_order_amount" min="0"
                                           value="{{ old('minimum_order_amount', usdToDefaultCurrency(amount: $vendor->minimum_order_amount) ?? 0) }}"
                                           placeholder="{{ translate('Ex') }}: {{ getCurrencySymbol() }}300">
                                </div>
                            </div>
                        @endif

                        @if ($showRetailFreeDelivery)
                            <div class="col-lg-6 mb-3">
                                <div class="form-group mb-0">
                                    <div class="d-flex justify-content-between align-items-center gap-2 mb-2">
                                        <label class="form-label mb-0" for="free-delivery-over-amount">
                                            {{ translate('Retail free delivery over amount') }}
                                            ({{ getCurrencySymbol() }})
                                            <i class="fi fi-sr-info cursor-pointer text-9B9B9B fs-12" data-toggle="tooltip"
                                               title="{{ translate('Order amount that qualifies for free delivery.') }}"></i>
                                        </label>
                                        <label class="switcher mb-0" for="free-delivery-status">
                                            <input type="checkbox" class="switcher_input toggle-switch-message"
                                                   name="free_delivery_status" id="free-delivery-status"
                                                   {{ $retailFreeDelivery['is_on'] ? 'checked' : '' }}
                                                   data-modal-id="toggle-modal" data-toggle-id="free-delivery-status"
                                                   data-on-image="free-delivery-on.png"
                                                   data-off-image="free-delivery-on.png"
                                                   data-on-title="{{ translate('Want to Turn ON :item', ['item' => translate('Free Delivery')]) }}"
                                                   data-off-title="{{ translate('Want to Turn OFF :item', ['item' => translate('Free Delivery')]) }}"
                                                   data-on-message="<p>{{ translate('If enabled the free delivery feature will be shown in the system') }}</p>"
                                                   data-off-message="<p>{{ translate('If disabled the free delivery feature will be hidden from the system') }}</p>">
                                            <span class="switcher_control"></span>
                                        </label>
                                    </div>
                                    <input type="number" step="any" class="form-control js-free-delivery-amount" name="free_delivery_over_amount"
                                           id="free-delivery-over-amount" min="0"
                                           data-status-toggle="#free-delivery-status"
                                           value="{{ $retailFreeDelivery['amount'] }}"
                                           {{ $retailFreeDelivery['is_on'] ? 'required' : '' }}
                                           placeholder="{{ translate('Ex') }}: {{ getCurrencySymbol() }}300">
                                </div>
                            </div>
                        @endif

                        <div class="col-lg-6 mb-3">
                            <div class="form-group mb-0">
                                <label class="form-label" for="stock-limit">
                                    {{ translate('Retail Re-order Level') }}
                                    <i class="fi fi-sr-info cursor-pointer text-9B9B9B fs-12" data-toggle="tooltip"
                                       title="{{ translate('Stock level at which the system alerts you.') }}"></i>
                                </label>
                                <input type="number" class="form-control" id="stock-limit" name="stock_limit" min="0"
                                       value="{{ old('stock_limit', $vendor?->stock_limit ?? 0) }}"
                                       placeholder="{{ translate('Ex') }}: 100">
                                <small class="text-muted d-block mt-1">
                                    {{ translate('Set the stock limit for the reorder level.') }}
                                </small>
                            </div>
                        </div>
                    </div>
                </div>

                @if ($showWholesaleMinimumOrderAmount || $showWholesaleFreeDelivery)
                    <div class="bg-light rounded-10 p-20">
                        <div class="row mb-n3">
                            @if ($showWholesaleMinimumOrderAmount)
                                <div class="col-lg-6 mb-3">
                                    <div class="form-group mb-0">
                                        <label class="form-label" for="wholesale-minimum-order-amount">
                                            {{ translate('Wholesale minimum order amount') }}
                                            ({{ getCurrencySymbol() }})
                                            <i class="fi fi-sr-info cursor-pointer text-9B9B9B fs-12" data-toggle="tooltip"
                                               title="{{ translate('Minimum order amount customers must reach for your wholesale products.') }}"></i>
                                        </label>
                                        <input type="number" step="any" class="form-control w-100"
                                               id="wholesale-minimum-order-amount" name="wholesale_minimum_order_amount" min="0"
                                               value="{{ old('wholesale_minimum_order_amount', usdToDefaultCurrency(amount: $vendor['wholesale_minimum_order_amount'] ?? 0) ?? 0) }}"
                                               placeholder="{{ translate('Ex') }}: {{ getCurrencySymbol() }}300">
                                    </div>
                                </div>
                            @endif
                            @if ($showWholesaleFreeDelivery)
                            <div class="col-lg-6 mb-3">
                                <div class="form-group mb-0">
                                    <div class="d-flex justify-content-between align-items-center gap-2 mb-2">
                                        <label class="form-label mb-0" for="wholesale-free-delivery-over-amount">
                                            {{ translate('Wholesale free delivery over amount') }}
                                            ({{ getCurrencySymbol() }})
                                            <i class="fi fi-sr-info cursor-pointer text-9B9B9B fs-12" data-toggle="tooltip"
                                               title="{{ translate('Wholesale orders reaching this amount get free delivery.') }}"></i>
                                        </label>
                                        <label class="switcher mb-0" for="wholesale-free-delivery-status">
                                            <input type="checkbox" class="switcher_input toggle-switch-message"
                                                   name="wholesale_free_delivery_status" id="wholesale-free-delivery-status"
                                                   {{ $wholesaleFreeDelivery['is_on'] ? 'checked' : '' }}
                                                   data-modal-id="toggle-modal" data-toggle-id="wholesale-free-delivery-status"
                                                   data-on-image="free-delivery-on.png"
                                                   data-off-image="free-delivery-on.png"
                                                   data-on-title="{{ translate('Want to Turn ON :item', ['item' => translate('Free Delivery')]) }}"
                                                   data-off-title="{{ translate('Want to Turn OFF :item', ['item' => translate('Free Delivery')]) }}"
                                                   data-on-message="<p>{{ translate('If enabled the free delivery feature will be shown in the system') }}</p>"
                                                   data-off-message="<p>{{ translate('If disabled the free delivery feature will be hidden from the system') }}</p>">
                                            <span class="switcher_control"></span>
                                        </label>
                                    </div>
                                    <input type="number" step="any" class="form-control js-free-delivery-amount" name="wholesale_free_delivery_over_amount"
                                           id="wholesale-free-delivery-over-amount" min="0"
                                           data-status-toggle="#wholesale-free-delivery-status"
                                           value="{{ $wholesaleFreeDelivery['amount'] }}"
                                           {{ $wholesaleFreeDelivery['is_on'] ? 'required' : '' }}
                                           placeholder="{{ translate('Ex') }}: {{ getCurrencySymbol() }}300">
                                </div>
                            </div>
                            @endif
                        </div>
                    </div>
                @endif
            </div>

            <div class="card card-body">
                <div class="mb-4">
                    <h3 class="mb-1">{{ translate('TIN Document') }}</h3>
                    <p class="fs-12 mb-0">
                        {{ translate('Use this section to configure your necessary TIN certificate') }}.
                    </p>
                </div>

                <div class="d-flex flex-column gap-3">
                    @foreach ($tinDocuments as $moduleKey => $document)
                        @include('vendor-views.shop.partials._tin-document', [
                            'moduleKey' => $moduleKey,
                            'document' => $document,
                        ])
                    @endforeach
                </div>
            </div>

            <div class="d-flex flex-wrap justify-content-end gap-3 mt-4">
                <a href="{{ route('vendor.shop.other-setup') }}" class="btn btn-secondary px-3 px-sm-4 min-w-120">
                    {{ translate('Reset') }}
                </a>
                <button type="submit" class="btn btn--primary px-3 px-sm-4 min-w-120">
                    <i class="fi fi-sr-disk"></i>
                    {{ translate('Save Information') }}
                </button>
            </div>
        </form>
    </div>
    @include('layouts.vendor.partials.offcanvas._shop-other-setup')
@endsection

@push('script')
    <script src="{{ dynamicAsset(path: 'public/assets/backend/vendor/js/business-settings/shop-settings.js') }}"></script>
    <script src="{{ dynamicAsset(path: 'public/assets/back-end/js/file-upload/pdf.min.js') }}"></script>
    <script src="{{ dynamicAsset(path: 'public/assets/back-end/js/file-upload/pdf-worker.min.js') }}"></script>
    <script src="{{ dynamicAsset(path: 'public/assets/back-end/js/file-upload/multiple-document-upload.js') }}"></script>
@endpush
