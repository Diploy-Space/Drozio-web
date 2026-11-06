@extends('layouts.admin.app')

@section('title', translate('Vendors'))

@section('content')
    <div class="content container-fluid">
        <div class="mb-3 mb-sm-20 d-flex flex-wrap align-items-center justify-content-between gap-3">
            <h2 class="h1 mb-0 text-capitalize d-flex flex-column flex-sm-row align-items-sm-center gap-sm-2">
                {{ translate('Business Setup') }}
            </h2>

            @include('admin-views.business-settings._business-module-switcher')
        </div>

        @include('admin-views.business-settings.business-setup-inline-menu')

        <form action="{{ route('admin.settings.retail-settings.vendor-settings.update-vendor-settings') }}" method="post" id="update-retail-vendor-settings-form">
            @csrf

            <div class="card">
                <div class="card-body">
                    <div class="mb-3 mb-sm-20">
                        <h3 class="mb-1">{{ translate('General Setup') }}</h3>
                        <p class="mb-0 fs-12">
                            {{ translate('Complete the basic settings for vendors') }}
                        </p>
                    </div>
                    <div class="p-12 p-sm-20 bg-section rounded">
                        <div class="row g-4">
                            <div class="col-xl-6 col-md-6">
                                <div
                                    class="d-flex justify-content-between align-items-center gap-3 border rounded p-12 p-sm-3 user-select-none h-100 bg-white">
                                    <span class="d-flex flex-column">
                                        <span class="fw-medium text-dark fs-14 mb-1 text-capitalize">
                                            {{ translate('Active Pos For Vendor') }}
                                        </span>
                                        <span class="mb-0 fs-12">
                                            {{ translate('If enabled pos will be available on the vendor panel') }}
                                        </span>
                                    </span>
                                    @php($sellerPos = getWebConfig('seller_pos'))
                                    <label class="switcher" for="vendor-pos">
                                        <input
                                            class="switcher_input "
                                            type="checkbox" value="1" name="seller_pos"
                                            id="vendor-pos"
                                            {{ $sellerPos == 1 ? 'checked' : '' }}
                                            data-modal-type="input-change"
                                            data-on-image="{{ dynamicAsset(path: 'public/assets/new/back-end/img/modal/pos-seller-on.png') }}"
                                            data-off-image="{{ dynamicAsset(path: 'public/assets/new/back-end/img/modal/pos-seller-off.png') }}"
                                            data-on-title="{{ translate('Want to Turn ON POS for Vendor') }}"
                                            data-off-title="{{ translate('Want to Turn OFF POS for Vendor') }}"
                                            data-on-message="<p>{{ translate('Shows POS in the vendor panel and app.') }}</p>"
                                            data-off-message="<p>{{ translate('Hides POS from the vendor panel and app.') }}</p>">

                                        <span class="switcher_control"></span>
                                    </label>
                                </div>
                            </div>
                            <div class="col-xl-6 col-md-6">
                                <label
                                    class="d-flex justify-content-between align-items-center gap-3 border rounded p-12 p-sm-3 user-select-none h-100 bg-white">
                                    <span class="d-flex flex-column">
                                        <span class="fw-medium text-dark fs-14 mb-1 text-capitalize">
                                            {{ translate('Minimum Order Amount') }}
                                        </span>
                                        <span class="mb-0 fs-12">
                                            {{ translate('If enabled vendors can set minimum order amount for their orders') }}
                                        </span>
                                    </span>

                                    @php($minimumOrderAmountBySeller = getWebConfig('minimum_order_amount_by_seller'))
                                    <label class="switcher" for="minimum-order-amount-by-vendor">
                                        <input
                                            class="switcher_input "
                                            type="checkbox" value="1" name="minimum_order_amount_by_seller"
                                            id="minimum-order-amount-by-vendor"
                                            {{ $minimumOrderAmountBySeller == 1 ? 'checked' : '' }}
                                            data-modal-type="input-change"
                                            data-on-image="{{ dynamicAsset(path: 'public/assets/new/back-end/img/modal/minimum-order-amount-feature-on.png') }}"
                                            data-off-image="{{ dynamicAsset(path: 'public/assets/new/back-end/img/modal/minimum-order-amount-feature-off.png') }}"
                                            data-on-title="{{ translate('Want to Turn ON the Set Minimum Order Amount option') . '?' }}"
                                            data-off-title="{{ translate('Want to Turn OFF the Set Minimum Order Amount option') . '?' }}"
                                            data-on-message="<p>{{ translate('Vendors can set a minimum order amount for their stores.') . '.' }}</p>"
                                            data-off-message="<p>{{ translate('Vendors cannot set a minimum order amount; the admin sets it.') . '.' }}</p>"
                                            data-on-button-text="{{ translate('Turn On') }}"
                                            data-off-button-text="{{ translate('Turn Off') }}">
                                        <span class="switcher_control"></span>
                                    </label>
                                </label>
                            </div>
                            <div class="col-xl-6 col-md-6">
                                <label
                                    class="d-flex justify-content-between align-items-center gap-3 border rounded p-12 p-sm-3 user-select-none h-100 bg-white">
                                    <span class="d-flex flex-column">
                                        <span class="fw-medium text-dark fs-14 mb-1 text-capitalize">
                                            {{ translate('Vendor can reply on review') }}
                                        </span>
                                        <span class="mb-0 fs-12">
                                            {{ translate('Enable this option to allow vendors to reply to customer reviews') }}
                                        </span>
                                    </span>

                                    @php($vendorReviewReplyStatus = getWebConfig('vendor_review_reply_status') ?? 0)
                                    <label class="switcher" for="vendor-review-reply-status">
                                        <input
                                            class="switcher_input "
                                            type="checkbox" value="1" name="vendor_review_reply_status"
                                            id="vendor-review-reply-status"
                                            {{ $vendorReviewReplyStatus == 1 ? 'checked' : '' }}
                                            data-modal-type="input-change"
                                            data-on-image="{{ dynamicAsset(path: 'public/assets/new/back-end/img/modal/vendor-review-reply-on.png') }}"
                                            data-off-image="{{ dynamicAsset(path: 'public/assets/new/back-end/img/modal/vendor-review-reply-off.png') }}"
                                            data-on-title="{{ translate('By Turning On Vendor Review Reply Option') . '?' }}"
                                            data-off-title="{{ translate('By Turning Off Vendor Review Reply Option') . '?' }}"
                                            data-on-message="<p>{{ translate('Lets this seller reply to reviews.') . '.' }}</p>"
                                            data-off-message="<p>{{ translate('Stops this seller replying to reviews.') . '.' }}</p>"
                                            data-on-button-text="{{ translate('Turn On') }}"
                                            data-off-button-text="{{ translate('Turn Off') }}">
                                        <span class="switcher_control"></span>
                                    </label>
                                </label>
                            </div>
                            <div class="col-xl-6 col-md-6">
                                <label
                                    class="d-flex justify-content-between align-items-center gap-3 border rounded p-12 p-sm-3 user-select-none h-100 bg-white">
                                    <span class="d-flex flex-column">
                                        <span class="fw-medium text-dark fs-14 mb-1 text-capitalize">
                                            {{ translate('Vendor can edit order') }}
                                        </span>
                                        <span class="mb-0 fs-12">
                                            {{ translate('Enable this option to allow vendors to edit orders') }}
                                        </span>
                                    </span>

                                    @php($vendorCanEditOrderStatus = getWebConfig('vendor_can_edit_order') ?? 0)
                                    <label class="switcher" for="vendor-can-edit-order">
                                        <input
                                            class="switcher_input "
                                            type="checkbox" value="1" name="vendor_can_edit_order"
                                            id="vendor-can-edit-order"
                                            {{ $vendorCanEditOrderStatus == 1 ? 'checked' : '' }}
                                            data-modal-type="input-change"
                                            data-on-image="{{ dynamicAsset(path: 'public/assets/new/back-end/img/modal/vendor-review-reply-on.png') }}"
                                            data-off-image="{{ dynamicAsset(path: 'public/assets/new/back-end/img/modal/vendor-review-reply-off.png') }}"
                                            data-on-title="{{ translate('By Turning On Vendor Edit Order Option') . '?' }}"
                                            data-off-title="{{ translate('By Turning Off Vendor Edit Order Option') . '?' }}"
                                            data-on-message="<p>{{ translate('Lets this vendor edit their orders.') . '.' }}</p>"
                                            data-off-message="<p>{{ translate('Stops this vendor editing their orders.') . '.' }}</p>"
                                            data-on-button-text="{{ translate('Turn On') }}"
                                            data-off-button-text="{{ translate('Turn Off') }}">
                                        <span class="switcher_control"></span>
                                    </label>
                                </label>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            @if ($isMultiVendorMode)
                <div class="card mt-3">
                    <div class="card-header">
                        <div>
                            <h3 class="fs-14 mb-1 fw-medium">{{ translate('Retail Commission') }}</h3>
                            <p class="mb-0 fs-12">
                                {{ translate('Set the commission the platform earns on retail orders.') }}
                            </p>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <div class="p-12 p-sm-20 bg-section rounded h-100">
                                    <h4 class="d-flex align-items-center gap-1 fw-medium mb-2 text-capitalize" for="sales_commission">
                                        {{ translate('Retail default commission') }} {{ '(%)' }}
                                        <span class="tooltip-icon" data-bs-toggle="tooltip" data-bs-placement="top"
                                              aria-label="{{ translate('Commission applied to retail orders from vendors with no own commission.') }}"
                                              data-bs-title="{{ translate('Commission applied to retail orders from vendors with no own commission.') }}">
                                            <i class="fi fi-sr-info"></i>
                                        </span>
                                    </h4>
                                    <input type="number" step="0.01" min="0" max="100" id="sales_commission"
                                           name="sales_commission" class="form-control"
                                           placeholder="{{ translate('Ex') }}: {{ '10' }}"
                                           value="{{ $retailCommissionSetup['commissionValue'] }}">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="p-12 p-sm-20 bg-section rounded h-100">
                                    <div class="d-flex justify-content-between align-items-center gap-2 mb-1">
                                        <h4 class="fs-14 mb-0 fw-medium text-capitalize">{{ translate('Set vendor wise retail commission') }}</h4>
                                        <label class="switcher">
                                            <input type="checkbox" id="retail_vendor_wise_commission_status"
                                                   name="retail_vendor_wise_commission_status" class="switcher_input"
                                                   value="1" {{ $retailCommissionSetup['vendorWiseStatus'] ? 'checked' : '' }}>
                                            <span class="switcher_control"></span>
                                        </label>
                                    </div>
                                    <p class="mb-0 fs-12">
                                        {{ translate('Set a different commission for each vendor. Go to') }}:
                                        <a href="{{ route('admin.people.vendors.vendor-list') }}" target="_blank"
                                           class="text-decoration-underline fw-semibold">{{ translate('Vendor List') }}</a>
                                        &rarr; {{ translate('Vendor Details') }} &rarr; {{ translate('Settings') }}.
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            @endif

            @if(function_exists('getCheckAddonPublishedStatus') && getCheckAddonPublishedStatus(moduleName: 'Builder'))
                <div class="card mt-3">
                    <div class="card-body">
                        <div class="p-12 p-sm-20 bg-section rounded">
                            <div class="d-flex justify-content-between align-items-center gap-3">
                                <div>
                                    <h2 class="text-capitalize">{{ translate('Vendor website builder') }}</h2>
                                    <p class="mb-0">
                                        {{ translate('Lets vendors set up and manage their own website.') }}
                                    </p>
                                </div>
                                <div>
                                    @php($adminWebsiteBuilderStatus = getWebConfig('admin_website_builder_status') ?? 0)
                                    <label class="switcher" for="admin-website-builder-status">
                                        <input class="switcher_input" type="checkbox" value="1"
                                               name="admin_website_builder_status" id="admin-website-builder-status"
                                               {{ $adminWebsiteBuilderStatus == 1 ? 'checked' : '' }}
                                               data-modal-type="input-change"
                                               data-on-title="{{ translate('Want to enable vendor website setup') . '?' }}"
                                               data-off-title="{{ translate('Want to disable vendor website setup') . '?' }}"
                                               data-on-message="<p>{{ translate('Vendors can create, edit and manage their own websites.') }}</p>"
                                               data-off-message="<p>{{ translate('Vendors cannot create or manage their own websites.') }}</p>"
                                               data-on-button-text="{{ translate('Turn On') }}"
                                               data-off-button-text="{{ translate('Turn Off') }}">
                                        <span class="switcher_control"></span>
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            @endif

            <div class="d-flex justify-content-end trans3 mt-4 action-btn-wrapper-container">
                <div class="d-flex justify-content-sm-end justify-content-center gap-3 flex-grow-1 flex-grow-sm-0 bg-white action-btn-wrapper trans3">
                    <button type="reset" class="btn btn-secondary px-3 px-sm-4 w-120">{{ translate('Reset') }}</button>
                    <button type="{{ getDemoModeFormButton(type: 'button') }}" class="btn btn-primary px-3 px-sm-4 {{ getDemoModeFormButton(type: 'class') }}">
                        <i class="fi fi-sr-disk"></i>
                        {{ translate('Save Information') }}
                    </button>
                </div>
            </div>
        </form>
    </div>
@endsection
