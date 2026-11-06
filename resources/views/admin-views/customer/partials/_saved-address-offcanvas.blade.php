<div class="offcanvas offcanvas-end" tabindex="-1" id="savedAddressOffcanvas"
     aria-labelledby="savedAddressOffcanvasLabel">
    <div class="offcanvas-header bg-body">
        <h3 class="mb-0">{{ translate('Saved Address') }}</h3>
        <button type="button" class="btn-close shadow-none" data-bs-dismiss="offcanvas" aria-label="Close"></button>
    </div>
    <div class="offcanvas-body">
        @if($customer->addresses->isEmpty())
            <div class="text-center">
                <h5 class="mb-2">{{ translate('No address found') }}</h5>
                <p class="fs-12">{{ translate('You have not added any address yet') }}</p>
            </div>
        @else
            @foreach($customer->addresses as $address)
                <div class="p-2 bg-section rounded mb-3 mb-sm-20">
                    <div class="fs-14 mb-2">
                        <span class="text-dark fw-medium">{{ ucwords($address['address_type']) }}</span>
                        <span>
                            ({{ $address['is_billing'] == 0 ? translate('Shipping Address') : translate('Billing Address') }})
                        </span>
                    </div>
                    <div class="bg-white rounded p-2 d-flex flex-column gap-2 fs-12">
                        <div class="d-flex gap-1 align-items-center">
                            <span class="min-w-80">{{ translate('Name') }}</span>
                            <span>:</span>
                            <span>{{ $address['contact_person_name'] }} ({{ $address['phone'] }})</span>
                        </div>

                        @if(!empty($address['email']))
                            <div class="d-flex gap-1 align-items-center">
                                <span class="min-w-80">{{ translate('Email') }}</span>
                                <span>:</span>
                                <span>{{ $address['email'] }}</span>
                            </div>
                        @endif

                        <div class="d-flex gap-1 align-items-center">
                            <span class="min-w-80">{{ translate('Address Type') }}</span>
                            <span>:</span>
                            <span>{{ ucwords($address['address_type']) }}</span>
                        </div>

                        @if(!empty($address['country']))
                            <div class="d-flex gap-1 align-items-center">
                                <span class="min-w-80">{{ translate('Country') }}</span>
                                <span>:</span>
                                <span>{{ $address['country'] }}</span>
                            </div>
                        @endif

                        @if(!empty($address['city']))
                            <div class="d-flex gap-1 align-items-center">
                                <span class="min-w-80">{{ translate('City') }}</span>
                                <span>:</span>
                                <span>{{ $address['city'] }}</span>
                            </div>
                        @endif

                        @if(!empty($address['zip']))
                            <div class="d-flex gap-1 align-items-center">
                                <span class="min-w-80">{{ translate('Zip Code') }}</span>
                                <span>:</span>
                                <span>{{ $address['zip'] }}</span>
                            </div>
                        @endif

                        <div class="d-flex gap-1 align-items-center">
                            <span class="min-w-80">{{ translate('Address') }}</span>
                            <span>:</span>
                            <span>{{ $address['address'] }}</span>
                        </div>
                    </div>
                </div>
            @endforeach
        @endif
    </div>
</div>
