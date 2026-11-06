@php
    // Free-delivery setup card, reused for both business contexts.
    //   $context : 'retail' | 'wholesale'
    // Retail keeps the original `free_delivery_*` business_settings keys; wholesale
    // uses namespaced `wholesale_free_delivery_*` keys so the two configs are
    // independent. Element ids are context-prefixed to avoid duplicate ids when
    // both cards render on the same page.
    $context = $context ?? 'retail';
    $prefix = $context === 'wholesale' ? 'wholesale_' : '';
    $statusKey = $prefix . 'free_delivery_status';
    $responsibilityKey = $prefix . 'free_delivery_responsibility';
    $overAmountKey = $prefix . 'free_delivery_over_amount_seller';

    $freeDelivery = getWebConfig($statusKey);
    $freeDeliveryResponsibility = getWebConfig($responsibilityKey);
    $freeDeliveryOverAmountSeller = getWebConfig($overAmountKey);
@endphp
<div class="card js-free-delivery-setup">
    <div class="card-body">
        <div class="mb-3 mb-sm-20">
            <h3>{{ translate('Setup Free Delivery') }}</h3>
            <p class="mb-0 fs-12">
                {{ translate('Configure free delivery offer for orders that meet a specific order value') }}
            </p>
        </div>
        <div class="p-12 p-sm-20 bg-section rounded">
            <div class="row g-4">
                <div class="col-12">
                    <label
                        class="d-flex justify-content-between align-items-center gap-3 border rounded p-3 user-select-none bg-white"
                        for="{{ $context }}-free-delivery-status">
                        <span>
                            <h5 class="fw-medium text-dark fs-14 mb-1">
                                {{ translate('Turn On/Off Free Delivery') }}
                            </h5>
                            <p class="mb-0 fs-12">
                                {{ translate('Free delivery applies when customers order over a certain amount.') }}
                            </p>
                        </span>

                        <label class="switcher" for="{{ $context }}-free-delivery-status">
                            <input
                                class="switcher_input custom-modal-plugin"
                                type="checkbox" value="1" name="{{ $statusKey }}"
                                id="{{ $context }}-free-delivery-status"
                                {{ $freeDelivery == 1 ? 'checked' : '' }}
                                data-modal-type="input-change"
                                data-on-image="{{ dynamicAsset(path: 'public/assets/new/back-end/img/modal/free-delivery-on.png') }}"
                                data-off-image="{{ dynamicAsset(path: 'public/assets/new/back-end/img/modal/free-delivery-off.png') }}"
                                data-on-title="{{ translate('Want to Turn ON :item', ['item' => translate('Free Delivery')]) }}"
                                data-off-title="{{ translate('Want to Turn OFF :item', ['item' => translate('Free Delivery')]) }}"
                                data-on-message="<p>{{ translate('If enabled the free delivery feature will be shown in the system') }}</p>"
                                data-off-message="<p>{{ translate('If disabled the free delivery feature will be hidden from the system') }}</p>">
                            <span class="switcher_control"></span>
                        </label>
                    </label>
                </div>
                <div class="col-lg-6">
                    <div class="form-group">
                        <label class="form-label" for="{{ $context }}-free-delivery-responsible-admin">
                            {{ translate('Free Delivery Responsibility') }}
                        </label>
                        <div
                            class="min-h-40 d-flex align-items-sm-center flex-column flex-sm-row gap-sm-5 border rounded mb-2 px-3 py-1 bg-white free-delivery-responsibility"
                            data-default="{{ $freeDeliveryResponsibility }}"
                        >
                            <div class="form-check d-flex gap-2 my-2">
                                <input class="form-check-input radio--input free-delivery-responsibility-radio" type="radio" value="admin"
                                       name="{{ $responsibilityKey }}"
                                       id="{{ $context }}-free-delivery-responsible-admin"
                                    {{ $freeDeliveryResponsibility == 'admin' ? 'checked' : '' }}>
                                <label class="form-check-label" for="{{ $context }}-free-delivery-responsible-admin">
                                    {{ translate('Admin Responsibility') }}
                                </label>
                            </div>
                            <div class="form-check d-flex gap-2 my-2">
                                <input class="form-check-input radio--input free-delivery-responsibility-radio" type="radio" value="seller"
                                       name="{{ $responsibilityKey }}"
                                       id="{{ $context }}-free-delivery-responsible-vendor"
                                    {{ $freeDeliveryResponsibility == 'seller' ? 'checked' : '' }}>
                                <label class="form-check-label" for="{{ $context }}-free-delivery-responsible-vendor">
                                    {{ translate('Vendors Responsibility') }}
                                </label>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-lg-6 free-delivery-over-amount-admin-area"
                     style="{{ $freeDeliveryResponsibility == 'seller' ? 'display:none' : '' }}">
                    <div class="form-group">
                        <label class="form-label" for="{{ $context }}_free_delivery_over_amount">
                            {{ translate('Free Delivery Over') }}
                            ({{ getCurrencySymbol(currencyCode: getCurrencyCode()) }})
                            <span class="tooltip-icon" data-bs-toggle="tooltip" data-bs-placement="right"
                                  data-bs-title="{{ translate('Free delivery amount for vendors who have not set their own.') }}">
                                <i class="fi fi-sr-info"></i>
                            </span>
                        </label>
                        <input type="number" class="form-control" min="0"
                               name="{{ $overAmountKey }}" id="{{ $context }}_free_delivery_over_amount"
                               placeholder="{{ translate('Ex') . ': ' . '10' }}"
                               value="{{ usdToDefaultCurrency($freeDeliveryOverAmountSeller) ?? 0 }}">
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
