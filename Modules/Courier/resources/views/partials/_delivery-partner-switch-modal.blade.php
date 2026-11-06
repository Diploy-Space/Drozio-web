@php($bs = (int) ($courierBs ?? 5))

@if ($bs === 5)
    <div class="modal fade" id="courierSwitchModal" tabindex="-1" aria-labelledby="courierSwitchModalLabel" aria-hidden="true"
         data-current-partner="{{ $courierShipment['delivery_partner'] }}">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header border-0 pb-0">
                    <h5 class="modal-title fw-bold" id="courierSwitchModalLabel">{{ translate('Switch Delivery Partner') }}?</h5>
                    <button type="button" class="btn-close border-0 btn-circle bg-section2 shadow-none"
                            data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p class="mb-3">
                        {{ translate('This order is already assigned to') }}
                        <strong class="text-capitalize courier-switch-current"></strong>.
                        {{ translate('Switching to') }}
                        <strong class="text-capitalize courier-switch-next"></strong>
                        {{ translate('Both delivery partners may try to collect the same order.') }}
                    </p>
                    <div class="alert alert-warning fs-12 mb-0">
                        {{ translate('The previous partner is not canceled. Cancel to avoid a duplicate pickup.') }}
                    </div>
                </div>
                <div class="modal-footer border-0 d-flex gap-3">
                    <button type="button" class="btn btn-secondary flex-fill" data-bs-dismiss="modal">{{ translate('Cancel') }}</button>
                    <button type="button" class="btn btn-primary flex-fill" id="courier-switch-confirm">{{ translate('Yes Switch Partner') }}</button>
                </div>
            </div>
        </div>
    </div>
@else
    <div class="modal fade" id="courierSwitchModal" tabindex="-1" role="dialog" aria-labelledby="courierSwitchModalLabel"
         data-current-partner="{{ $courierShipment['delivery_partner'] }}">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header border-0 pb-0">
                    <h5 class="modal-title font-weight-bold" id="courierSwitchModalLabel">{{ translate('Switch Delivery Partner') }}?</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <p class="mb-3">
                        {{ translate('This order is already assigned to') }}
                        <strong class="text-capitalize courier-switch-current"></strong>.
                        {{ translate('Switching to') }}
                        <strong class="text-capitalize courier-switch-next"></strong>
                        {{ translate('Both delivery partners may try to collect the same order.') }}
                    </p>
                    <div class="alert alert-warning fs-12 mb-0">
                        {{ translate('The previous partner is not canceled. Cancel to avoid a duplicate pickup.') }}
                    </div>
                </div>
                <div class="modal-footer border-0 d-flex gap-3">
                    <button type="button" class="btn btn-secondary flex-fill" data-dismiss="modal">{{ translate('Cancel') }}</button>
                    <button type="button" class="btn btn-primary flex-fill" id="courier-switch-confirm">{{ translate('Yes Switch Partner') }}</button>
                </div>
            </div>
        </div>
    </div>
@endif
