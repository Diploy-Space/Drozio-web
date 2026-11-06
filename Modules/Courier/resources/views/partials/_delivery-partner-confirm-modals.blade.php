@php($bs = (int) ($courierBs ?? 5))
@php($dismissAttribute = $bs === 5 ? 'data-bs-dismiss' : 'data-dismiss')
@php($titleClass = $bs === 5 ? 'fw-bold' : 'font-weight-bold')

<div class="modal fade" id="courierDispatchConfirmModal" tabindex="-1" aria-labelledby="courierDispatchConfirmLabel" role="dialog">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title {{ $titleClass }}" id="courierDispatchConfirmLabel">{{ translate('Send this order to the delivery partner') }}?</h5>
                @if ($bs === 5)
                    <button type="button" class="btn-close border-0 btn-circle bg-section2 shadow-none"
                            data-bs-dismiss="modal" aria-label="Close"></button>
                @else
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                @endif
            </div>
            <div class="modal-body">
                <p class="mb-3">
                    {{ translate('The order information will be sent to') }}
                    <strong class="text-capitalize courier-confirm-partner"></strong>.
                    {{ translate('Check the delivery address and contact details before continuing.') }}
                </p>
                <div class="alert alert-warning fs-12 mb-0">
                    {{ translate('Incorrect information may cause delivery issues. Correct it before confirming.') }}
                </div>
            </div>
            <div class="modal-footer border-0 d-flex gap-3">
                <button type="button" class="btn btn-secondary flex-fill" {{ $dismissAttribute }}="modal">{{ translate('Review Again') }}</button>
                <button type="button" class="btn btn-primary flex-fill" id="courier-dispatch-confirm">{{ translate('Confirm and Send') }}</button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="courierReviseConfirmModal" tabindex="-1" aria-labelledby="courierReviseConfirmLabel" role="dialog">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title {{ $titleClass }}" id="courierReviseConfirmLabel">{{ translate('Update Delivery Information') }}?</h5>
                @if ($bs === 5)
                    <button type="button" class="btn-close border-0 btn-circle bg-section2 shadow-none"
                            data-bs-dismiss="modal" aria-label="Close"></button>
                @else
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                @endif
            </div>
            <div class="modal-body">
                <p class="mb-3">
                    {{ translate('This order is already assigned to') }}
                    <strong class="text-capitalize courier-confirm-partner"></strong>.
                    {{ translate('This may not match the information already sent to the delivery partner.') }}
                </p>
                <div class="alert alert-warning fs-12 mb-0">
                    {{ translate('Update this with the delivery partner yourself if the shipment has shipped.') }}
                </div>
            </div>
            <div class="modal-footer border-0 d-flex gap-3">
                <button type="button" class="btn btn-secondary flex-fill" {{ $dismissAttribute }}="modal">{{ translate('Cancel') }}</button>
                <button type="button" class="btn btn-primary flex-fill" id="courier-revise-confirm">{{ translate('Confirm and Update') }}</button>
            </div>
        </div>
    </div>
</div>
