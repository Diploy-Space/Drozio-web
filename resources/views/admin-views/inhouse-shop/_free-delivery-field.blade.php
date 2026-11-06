<div class="form-group mb-0">
    <div class="d-flex align-items-center justify-content-between gap-3">
        <label class="form-label mb-2" for="{{ $freeDeliveryModule['amount_field'] }}">
            {{ translate(':business Free Delivery Over Amount', ['business' => $freeDeliveryModule['label']]) }}
            ({{ getCurrencySymbol(currencyCode: getCurrencyCode()) }})
            <span class="tooltip-icon" data-bs-toggle="tooltip" data-bs-placement="top"
                aria-label="{{ translate('Set the order amount that earns the customer free delivery from your shop.') }}"
                data-bs-title="{{ translate('Set the order amount that earns the customer free delivery from your shop.') }}">
                <i class="fi fi-sr-info"></i>
            </span>
        </label>
        <label class="switcher mb-2" for="{{ $freeDeliveryModule['status_field'] }}">
            <input type="checkbox" class="switcher_input" value="1"
                name="{{ $freeDeliveryModule['status_field'] }}" id="{{ $freeDeliveryModule['status_field'] }}"
                {{ $freeDeliveryModule['is_active'] ? 'checked' : '' }}>
            <span class="switcher_control"></span>
        </label>
    </div>
    <input type="number" min="0" step="any" class="form-control"
        name="{{ $freeDeliveryModule['amount_field'] }}" id="{{ $freeDeliveryModule['amount_field'] }}"
        placeholder="{{ translate('Ex') }}: 300"
        value="{{ usdToDefaultCurrency(amount: $freeDeliveryModule['amount']) }}">
</div>
