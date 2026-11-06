@php(session(['last_order'=> false]))
<div class="modal fade py-5" id="print-invoice" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">{{ translate('Print Invoice') }}</h5>
                <button id="invoice_close" type="button" class="btn-close border-0 btn-circle bg-section2 shadow-none" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body row">

                <div class="col-md-12">
                    <div class="text-center">
                        <input id="print_invoice" type="button" class="btn btn--primary non-printable action-print-pos-invoice"
                               data-print="#printableArea"
                               value="{{ translate('Proceed') }}, {{ translate('If thermal printer is ready') }}"/>
                        <a href="{{url()->previous()}}" class="btn btn-danger non-printable mt-2 mt-lg-0">
                            {{ translate('Back') }}
                        </a>
                    </div>
                    <hr class="non-printable">
                </div>

                <div class="row d-flex justify-content-center mx-auto mx-lg-0" id="printableArea">
                    @include('vendor-views.pos.order.invoice')
                </div>

            </div>
        </div>
    </div>
</div>
