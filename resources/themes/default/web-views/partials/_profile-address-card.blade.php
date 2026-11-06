<div class="col-md-6">
    <div class="card __shadow h-100">
        <div class="card-header d-flex justify-content-between align-items-center gap-2 bg-aliceblue border-0">
            <h6 class="mb-0 fs-14 font-semibold w-0 flex-grow-1">
                {{ translate($address['address_type']) }}
                <span class="fs-12 fw-normal text-muted">({{ $address['is_billing'] == 1 ? translate('Billing Address') : translate('Shipping Address') }})</span>
            </h6>
            <div class="d-flex gap-2 align-items-center">
                <a class="text-primary p-0 lh-1" href="{{ route('address-edit', $address->id) }}"
                   title="{{ translate('Edit address') }}">
                    <img class="svg" width="15" alt=""
                         src="{{ theme_asset(path: 'public/assets/front-end/img/location-edit-dynamic.svg') }}">
                </a>
                <button class="no-gutter remove-address-by-modal p-0 text-danger lh-1"
                        data-link="{{ route('address-delete', ['id' => $address->id]) }}">
                    <i class="fa fa-lg fa-trash"></i>
                </button>
            </div>
        </div>

        <div class="card-body">
            <dl class="profile-info-list">
                <dt>{{ translate('Name') }}</dt>
                <dd>{{ $address['contact_person_name'] }}</dd>
                <dt>{{ translate('Phone') }}</dt>
                <dd><span dir="ltr">{{ $address['phone'] }}</span></dd>
                <dt>{{ translate('City') }}</dt>
                <dd>{{ $address['city'] }}</dd>
                <dt>{{ translate('Zip Code') }}</dt>
                <dd>{{ $address['zip'] }}</dd>
                <dt>{{ translate('Address') }}</dt>
                <dd>{{ $address['address'] }}</dd>
                <dt>{{ translate('Country') }}</dt>
                <dd>{{ $address['country'] }}</dd>
            </dl>
        </div>
    </div>
</div>
