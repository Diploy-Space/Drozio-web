@php($shortcuts = [
    translate('To focus search input') => 'Alt + Q',
    translate('To click order') => 'Alt + O',
    translate('To click payment submit') => 'Alt + S',
    translate('To close payment submit') => 'Alt + Z',
    translate('Cancel all cart items') => 'Alt + C',
    translate('To click clear cart') => 'Alt + X',
    translate('To click new order') => 'Alt + R',
    translate('To click add new customer') => 'Alt + A',
    translate('To submit add new customer form') => 'Alt + N',
    translate('To click extra discount') => 'Alt + E',
    translate('To click coupon discount') => 'Alt + D',
    translate('To print invoice') => 'Alt + P',
    translate('To cancel invoice') => 'Alt + B',
    translate('To click short cut keys') => 'Alt + K',
])

<div class="modal fade pos-modal" id="short-cut-keys" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title">{{ translate('Short cut keys') }}</h3>
                <button type="button" class="btn-close border-0 btn-circle bg-section2 shadow-none"
                        data-dismiss="modal" aria-label="{{ translate('Close') }}">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="pos-shortcut-list">
                    @foreach($shortcuts as $action => $keys)
                        <div>
                            <span>{{ $action }}</span>
                            <kbd>{{ $keys }}</kbd>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
</div>
