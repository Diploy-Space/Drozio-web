<div class="form-group mb-0">
    <label class="form-label mb-2" for="{{ $minimumOrderAmountModule['amount_field'] }}">
        {{ translate('Minimum order amount for :business', ['business' => $minimumOrderAmountModule['label']]) }}
        ({{ getCurrencySymbol(currencyCode: getCurrencyCode()) }})
        <span class="tooltip-icon" data-bs-toggle="tooltip" data-bs-placement="top"
            aria-label="{{ translate('Set a certain amount below that customer can not place any order') }}"
            data-bs-title="{{ translate('Set a certain amount below that customer can not place any order') }}">
            <i class="fi fi-sr-info"></i>
        </span>
    </label>
    <input type="number" min="0" step="any" class="form-control" name="{{ $minimumOrderAmountModule['amount_field'] }}"
        id="{{ $minimumOrderAmountModule['amount_field'] }}" placeholder="{{ translate('Ex') }}: 100"
        value="{{ usdToDefaultCurrency(amount: $minimumOrderAmountModule['amount']) }}">
</div>
