@include("layouts.admin.partials.offcanvas._view-guideline-button")

<div class="offcanvas offcanvas-end" tabindex="-1" id="offcanvasSetupGuide" aria-labelledby="offcanvasSetupGuideLabel"
     data-status="{{ request('offcanvasShow') && request('offcanvasShow') == 'offcanvasSetupGuide' ? 'show' : '' }}">

    <div class="offcanvas-header bg-body">
        <h3 class="mb-0">{{ translate('Shipping Method') }}</h3>
        <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
    </div>

    <div class="offcanvas-body">

        <div class="p-12 p-sm-20 bg-section rounded mb-3 mb-sm-20">
            <div class="d-flex gap-3 align-items-center justify-content-between overflow-hidden">
                <button class="btn-collapse d-flex gap-3 align-items-center bg-transparent border-0 p-0" type="button"
                        data-bs-toggle="collapse" data-bs-target="#collapseShippingMethod_01" aria-expanded="true">
                    <div class="btn-collapse-icon border bg-light icon-btn rounded-circle text-dark collapsed">
                        <i class="fi fi-sr-angle-right"></i>
                    </div>
                    <span class="fw-bold text-start">{{ translate('Shipping Responsibility') }}</span>
                </button>

            </div>

            <div class="collapse mt-3 show" id="collapseShippingMethod_01">
                <div class="card card-body">
                    <p class="fs-12">
                        {{ translate('The platform handles packaging and delivery for these orders.') }}
                    </p>
                    <p class="fs-12">
                        {{ translate('The vendor ships directly to the customer for these orders.') }}
                    </p>
                </div>
            </div>
        </div>
        @if ($isCourierAddonPublished)
            <div class="p-12 p-sm-20 bg-section rounded mb-3 mb-sm-20">
                <div class="d-flex gap-3 align-items-center justify-content-between overflow-hidden">
                    <button class="btn-collapse d-flex gap-3 align-items-center bg-transparent border-0 p-0 collapsed" type="button"
                            data-bs-toggle="collapse" data-bs-target="#collapseShippingMethod_04" aria-expanded="true">
                        <div class="btn-collapse-icon border bg-light icon-btn rounded-circle text-dark collapsed">
                            <i class="fi fi-sr-angle-right"></i>
                        </div>
                        <span class="fw-bold text-start">{{ translate('Third Party Delivery Service') }}</span>
                    </button>

                </div>

                <div class="collapse mt-3" id="collapseShippingMethod_04">
                    <div class="card card-body">
                        <p class="fs-12">
                            {{ translate('Connects your store to couriers set up under delivery partner integration.') }}
                        </p>
                        <ul class="d-flex flex-column gap-12 fs-12">
                            <li>
                                <strong>{{ translate('When ON') }}:</strong>
                                {{ translate('Book an order with a courier instead of your own team.') }}
                                {{ translate('Adds a way to ship. Your existing methods keep working.') }}
                            </li>
                            <li>
                                <strong>{{ translate('When OFF') }}:</strong>
                                {{ translate('Delivery partners are hidden and every order uses the default shipping method.') }}
                                {{ translate('Saved partner credentials are kept.') }}
                            </li>
                            <li>
                                <strong>{{ translate('Who selects the partner') }}:</strong>
                                {{ translate('Follows the shipping responsibility setting: admin books in-house, vendor books vendor-wise.') }}
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        @endif
        @if ($isThirdPartyDeliveryEnabled)
            <div class="p-12 p-sm-20 bg-section rounded mb-3 mb-sm-20">
                <div class="d-flex gap-3 align-items-center justify-content-between overflow-hidden">
                    <button class="btn-collapse d-flex gap-3 align-items-center bg-transparent border-0 p-0 collapsed" type="button"
                            data-bs-toggle="collapse" data-bs-target="#collapseShippingMethod_05" aria-expanded="true">
                        <div class="btn-collapse-icon border bg-light icon-btn rounded-circle text-dark collapsed">
                            <i class="fi fi-sr-angle-right"></i>
                        </div>
                        <span class="fw-bold text-start">{{ translate('Vendor Delivery Partner Setup') }}</span>
                    </button>

                </div>

                <div class="collapse mt-3" id="collapseShippingMethod_05">
                    <div class="card card-body">
                        <p class="fs-12">
                            {{ translate('Decides whether vendors manage their own courier accounts.') }}
                            {{ translate('This option is available only while third party delivery mode is on.') }}
                        </p>
                        <ul class="d-flex flex-column gap-12 fs-12">
                            <li>
                                <strong>{{ translate('When ON') }}:</strong>
                                {{ translate('Each vendor gets an integration page and their own carrier accounts.') }}
                            </li>
                            <li>
                                <strong>{{ translate('When OFF') }}:</strong>
                                {{ translate('Hides the page from vendors. Credentials they already saved are kept.') }}
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        @endif
        <div class="p-12 p-sm-20 bg-section rounded mb-3 mb-sm-20">
            <div class="d-flex gap-3 align-items-center justify-content-between overflow-hidden">
                <button class="btn-collapse d-flex gap-3 align-items-center bg-transparent border-0 p-0 collapsed" type="button"
                        data-bs-toggle="collapse" data-bs-target="#collapseShippingMethod_02" aria-expanded="true">
                    <div class="btn-collapse-icon border bg-light icon-btn rounded-circle text-dark collapsed">
                        <i class="fi fi-sr-angle-right"></i>
                    </div>
                    <span class="fw-bold text-start">{{ translate('Add order wise shipping method') }}</span>
                </button>

            </div>

            <div class="collapse mt-3" id="collapseShippingMethod_02">
                <div class="card card-body">
                    <p class="fs-12">
                        {{ translate('Set a title, delivery time and cost for each shipping option.') }}
                         {{ translate('This lets you offer various delivery speeds and prices to customers.') }}
                    </p>
                </div>
            </div>
        </div>
        <div class="p-12 p-sm-20 bg-section rounded mb-3 mb-sm-20">
            <div class="d-flex gap-3 align-items-center justify-content-between overflow-hidden">
                <button class="btn-collapse d-flex gap-3 align-items-center bg-transparent border-0 p-0 collapsed" type="button"
                        data-bs-toggle="collapse" data-bs-target="#collapseShippingMethod_03" aria-expanded="true">
                    <div class="btn-collapse-icon border bg-light icon-btn rounded-circle text-dark collapsed">
                        <i class="fi fi-sr-angle-right"></i>
                    </div>
                    <span class="fw-bold text-start">{{ translate('List of order wise shipping method') }}</span>
                </button>

            </div>

            <div class="collapse mt-3" id="collapseShippingMethod_03">
                <div class="card card-body">
                    <p class="fs-12">
                        {{ translate('List of order-wise shipping methods') }}
                    </p>
                    <ul class="d-flex flex-column gap-12 fs-12">
                        <li>
                            <strong>{{ translate('Title') }}:</strong>
                            {{ translate('Name of the shipping option.') }}
                        </li>
                        <li>
                            <strong>{{ translate('Shipping Duration') }}:</strong>
                            {{ translate('Estimated delivery time.') }}
                        </li>
                        <li>
                            <strong>{{ translate('Cost') }}:</strong>
                            {{ translate('Shipping Fee') }}
                        </li>
                        <li>
                            <strong>{{ translate('Status') }}:</strong>
                            {{ translate('Enabled or disabled.') }}
                        </li>
                        <li>
                            <strong>{{ translate('Actions') }}:</strong>
                            {{ translate('Edit or delete.') }}
                        </li>
                    </ul>
                </div>
            </div>
        </div>

    </div>
</div>
