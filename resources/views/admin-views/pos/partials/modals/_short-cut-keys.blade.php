@php($shortcuts = [
    translate('Focus search') => 'Alt + Q',
    translate('Place Order') => 'Alt + O',
    translate('Confirm order placement') => 'Alt + S',
    translate('Cancel order placement') => 'Alt + Z',
    translate('Clear Cart') => 'Alt + C / Alt + X',
    translate('Hold order') => 'Alt + R',
    translate('Add New Customer') => 'Alt + A',
    translate('Submit new customer form') => 'Alt + N',
    translate('Extra discount') => 'Alt + E',
    translate('Coupon Discount') => 'Alt + D',
    translate('Print Invoice') => 'Alt + P',
    translate('Close invoice') => 'Alt + B',
    translate('Show or hide shortcut keys') => 'Alt + K',
])

<div class="modal fade" id="short-cut-keys" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title">{{ translate('Short cut keys') }}</h3>
                <button type="button" class="btn-close border-0 btn-circle bg-section2 shadow-none"
                        data-bs-dismiss="modal" aria-label="{{ translate('Close') }}">
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
