@php($bs = (int) ($courierBs ?? 5))
@php($selectClass = $bs === 5 ? 'form-select' : 'form-control')
@php($panelBg = $bs === 5 ? 'bg-section2' : 'bg-light')

<style>
    .courier-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
        gap: 1rem;
        align-items: end;
    }
</style>

<input type="hidden" name="host_order_reference" value="{{ $courierOrder['id'] }}">
<input type="hidden" name="provider" id="courier_provider">
<input type="hidden" name="order_value" value="{{ $courierOrder['amount'] ?? $courierOrder['cod'] ?? 0 }}">

<div class="{{ $panelBg }} rounded-10 p-3 p-sm-20 d-flex flex-column gap-3" id="courier-fields-panel">
    <div class="alert alert-warning mb-0 fs-12 d-none" id="courier-lookup-notice" role="alert"></div>

    <div>
        @include('courier::partials._field-label', [
            'for'  => 'courier_store',
            'text' => translate('Pickup Store'),
            'help' => translate('Your own store or warehouse the rider collects this parcel from'),
        ])
        <select class="{{ $selectClass }}" name="store_id" id="courier_store"
                data-stores-url="{{ route($courierRoutes['stores']) }}"
                data-placeholder="{{ translate('Search or use default pickup store') }}">
            <option value="">{{ translate('Default pickup store') }}</option>
        </select>
    </div>

    <div>
        @include('courier::partials._field-label', [
            'for'         => 'courier_recipient_name',
            'text'        => translate('Receivers Name'),
            'requiredFor' => 'recipient_name',
        ])
        <input type="text" class="form-control" name="recipient_name" id="courier_recipient_name"
               value="{{ $courierOrder['name'] ?? '' }}" required>
    </div>

    <div class="courier-grid">
        <div>
            @include('courier::partials._field-label', [
                'for'         => 'courier_recipient_phone',
                'text'        => translate('Receivers Phone'),
                'requiredFor' => 'recipient_phone',
            ])
            <input type="text" class="form-control" name="recipient_phone" id="courier_recipient_phone"
                   value="{{ $courierOrder['phone'] ?? '' }}" inputmode="tel" data-numeric-field="phone" required>
        </div>
        <div>
            @include('courier::partials._field-label', [
                'for'         => 'courier_cod',
                'text'        => translate('Cash to Collect'),
                'requiredFor' => 'cod_amount',
                'help'        => !empty($courierOrder['cod_locked'])
                    ? translate('Order already paid nothing to collect')
                    : translate('Amount the rider collects. Enter 0 if already paid.'),
            ])
            <input type="number" step="0.01" min="0" class="form-control" name="cod_amount" id="courier_cod"
                   value="{{ $courierOrder['cod'] ?? 0 }}" inputmode="decimal" data-numeric-field="decimal" required
                   {{ !empty($courierOrder['cod_locked']) ? 'readonly' : '' }}>
        </div>
    </div>

    <div>
        @include('courier::partials._field-label', [
            'for'         => 'courier_recipient_address',
            'text'        => translate('Delivery Address'),
            'requiredFor' => 'recipient_address',
            'help'        => translate('House road and landmark the rider needs to find the customer'),
        ])
        <textarea class="form-control" name="recipient_address" id="courier_recipient_address" rows="2"
                  placeholder="{{ translate('Ex') }}: 1/B/3 Farmgate, Dhaka" required>{{ $courierOrder['address'] ?? '' }}</textarea>
    </div>

    <div class="courier-grid" id="courier-location-row">
        <div class="courier-loc-col" data-level="city">
            @include('courier::partials._field-label', [
                'for'         => 'courier_city',
                'text'        => translate('Delivery City'),
                'levelFor'    => 'city',
                'requiredFor' => 'city_id',
            ])
            <select class="{{ $selectClass }}" name="city_id" id="courier_city"
                    data-cities-url="{{ route($courierRoutes['cities']) }}"
                    data-zones-url="{{ route($courierRoutes['zones'], ['cityId' => '__ID__']) }}">
                <option value="">{{ translate('Select') }}</option>
            </select>
        </div>
        <div class="courier-loc-col" data-level="zone">
            @include('courier::partials._field-label', [
                'for'         => 'courier_zone',
                'text'        => translate('Delivery Zone'),
                'levelFor'    => 'zone',
                'requiredFor' => 'zone_id',
            ])
            <select class="{{ $selectClass }}" name="zone_id" id="courier_zone"
                    data-areas-url="{{ route($courierRoutes['areas'], ['zoneId' => '__ID__']) }}">
                <option value="">{{ translate('Select') }}</option>
            </select>
        </div>
        <div class="courier-loc-col" data-level="area">
            @include('courier::partials._field-label', [
                'for'         => 'courier_area',
                'text'        => translate('Delivery Area'),
                'levelFor'    => 'area',
                'requiredFor' => 'area_id',
            ])
            <select class="{{ $selectClass }}" name="area_id" id="courier_area">
                <option value="">{{ translate('Select') }}</option>
            </select>
        </div>
    </div>

    <div class="d-none" id="courier-international-address">
        <div class="courier-grid">
            <div>
                @include('courier::partials._field-label', [
                    'for'         => 'courier_country',
                    'text'        => translate('Delivery Country'),
                    'requiredFor' => 'country_code',
                ])
                <select class="{{ $selectClass }}" name="country_code" id="courier_country">
                    <option value="">{{ translate('Select') }}</option>
                    @foreach ($courierCountries ?? [] as $country)
                        <option value="{{ $country['id'] }}">{{ $country['name'] }} ({{ $country['id'] }})</option>
                    @endforeach
                </select>
            </div>
            <div>
                @include('courier::partials._field-label', [
                    'for'         => 'courier_postal_code',
                    'text'        => translate('Delivery Postal Code'),
                    'requiredFor' => 'postal_code',
                    'help'        => translate('The ZIP or PIN code of the delivery address'),
                ])
                <input type="text" class="form-control" name="postal_code" id="courier_postal_code">
            </div>
        </div>
        <div class="courier-grid mt-3">
            <div>
                @include('courier::partials._field-label', [
                    'for'         => 'courier_city_name',
                    'text'        => translate('Delivery City'),
                    'requiredFor' => 'city_name',
                ])
                <input type="text" class="form-control" name="city_name" id="courier_city_name">
            </div>
            <div>
                @include('courier::partials._field-label', [
                    'for'  => 'courier_state_province',
                    'text' => translate('State or Province'),
                ])
                <input type="text" class="form-control" name="state_province" id="courier_state_province">
            </div>
        </div>
    </div>

    <div class="d-none" id="courier-geo-address">
        <div class="courier-grid">
            <div>
                @include('courier::partials._field-label', [
                    'for'         => 'courier_latitude',
                    'text'        => translate('Delivery Point Latitude'),
                    'requiredFor' => 'latitude',
                    'help'        => translate('From the customer\'s saved location, or right-click the address in Google Maps.'),
                ])
                <input type="text" class="form-control" name="latitude" id="courier_latitude"
                       value="{{ $courierOrder['latitude'] ?? '' }}" placeholder="{{ translate('Ex') }}: 23.7461"
                       inputmode="decimal" data-numeric-field="signed-decimal">
            </div>
            <div>
                @include('courier::partials._field-label', [
                    'for'         => 'courier_longitude',
                    'text'        => translate('Delivery Point Longitude'),
                    'requiredFor' => 'longitude',
                    'help'        => translate('Second number from the same Google Maps click. Rider goes here.'),
                ])
                <input type="text" class="form-control" name="longitude" id="courier_longitude"
                       value="{{ $courierOrder['longitude'] ?? '' }}" placeholder="{{ translate('Ex') }}: 90.3742"
                       inputmode="decimal" data-numeric-field="signed-decimal">
            </div>
        </div>
    </div>

    <div class="d-none" id="courier-branch-address">
        <div class="courier-grid">
            <div>
                @include('courier::partials._field-label', [
                    'for'         => 'courier_source_branch',
                    'text'        => translate('Pickup Branch'),
                    'requiredFor' => 'source_branch',
                    'help'        => translate('The partner\'s branch that collects this parcel'),
                ])
                <select class="{{ $selectClass }}" name="source_branch" id="courier_source_branch"
                        data-branches-url="{{ route($courierRoutes['cities']) }}">
                    <option value="">{{ translate('Select') }}</option>
                </select>
                <input type="hidden" name="source_branch_id" id="courier_source_branch_id">
            </div>
            <div>
                @include('courier::partials._field-label', [
                    'for'         => 'courier_destination_branch',
                    'text'        => translate('Delivery Branch'),
                    'requiredFor' => 'destination_branch',
                    'help'        => translate('The partner\'s branch nearest to the customer'),
                ])
                <select class="{{ $selectClass }}" name="destination_branch" id="courier_destination_branch">
                    <option value="">{{ translate('Select') }}</option>
                </select>
                <input type="hidden" name="destination_branch_id" id="courier_destination_branch_id">
            </div>
        </div>
    </div>

    <div class="courier-grid">
        <div class="d-none" id="courier-delivery-type-col">
            @include('courier::partials._field-label', [
                'for'  => 'courier_delivery_type',
                'text' => translate('Delivery Service'),
                'help' => translate('The delivery speed or vehicle this partner uses for the parcel'),
            ])
            <select class="{{ $selectClass }}" name="delivery_type" id="courier_delivery_type"></select>
        </div>
        <div>
            @include('courier::partials._field-label', [
                'for'         => 'courier_weight',
                'text'        => translate('Parcel Weight'),
                'requiredFor' => 'weight',
            ])
            <div class="input-group">
                <input type="number" step="0.01" min="0" class="form-control" name="weight" id="courier_weight"
                       placeholder="{{ translate('Ex') }}: 0.5" inputmode="decimal" data-numeric-field="decimal" required>
                @if ($bs === 5)
                    <span class="input-group-text">Kg</span>
                @else
                    <div class="input-group-append"><span class="input-group-text">Kg</span></div>
                @endif
            </div>
        </div>
        <div>
            @include('courier::partials._field-label', [
                'for'  => 'courier_quantity',
                'text' => translate('Number of Items'),
            ])
            <input type="number" min="1" class="form-control" name="quantity" id="courier_quantity"
                   placeholder="{{ translate('Ex') }}: 1" inputmode="numeric" data-numeric-field="integer">
        </div>
    </div>

    <div>
        @include('courier::partials._field-label', [
            'for'  => 'courier_item_description',
            'text' => translate('Parcel Contents'),
            'help' => translate('Printed on the shipping label so the rider knows the contents.'),
        ])
        <input type="text" class="form-control" name="item_description" id="courier_item_description"
               placeholder="{{ translate('Ex') }}: Cotton t-shirts">
    </div>

    <div>
        @include('courier::partials._field-label', [
            'for'  => 'courier_note',
            'text' => translate('Rider Instructions'),
            'help' => translate('Anything the rider should know before delivering this parcel'),
        ])
        <textarea class="form-control" name="note" id="courier_note" rows="2"
                  placeholder="{{ translate('Ex') }}: {{ translate('Handle with care') }}"></textarea>
    </div>
</div>

<div class="bg-section rounded-10 p-3 d-flex justify-content-between align-items-center gap-2 flex-wrap mt-3">
    <div>
        <span class="fw-semibold d-block">{{ translate('Estimated Delivery Charge') }}</span>
        <span class="fs-12 text-muted" id="courier-estimate-result">{{ translate('Fill weight and location, then estimate') }}</span>
    </div>
    <button type="button" class="btn btn-outline-primary" id="courier-estimate-btn"
            data-estimate-url="{{ route($courierRoutes['estimate']) }}">
        {{ translate('Estimate') }}
    </button>
</div>
