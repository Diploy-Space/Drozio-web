@php(session(['last_order'=> false]))
<div class="offcanvas-sidebar pos-offcanvas" id="print-invoice" style="--bs-offcanvas-width: 500px;">
    <div class="offcanvas-overlay" data-dismiss="offcanvas"></div>

    <div class="offcanvas-content d-flex flex-column">
        <div class="offcanvas-header d-flex justify-content-between align-items-center">
            <h2 class="text-capitalize m-0" id="printInvoiceLabel">{{ translate('Print Invoice') }}</h2>
            <button type="button" class="btn btn-circle bg-white text-dark fs-10" style="--size: 1.5rem;" data-dismiss="offcanvas" aria-label="Close">
                <i class="fi fi-rr-cross d-flex"></i>
            </button>
        </div>
        <div class="offcanvas-body overflow-auto flex-grow-1">
            <div class="flex-grow-1 p-3 d-flex justify-content-center align-items-center" id="printableArea">
                @include('vendor-views.pos.order.invoice')
            </div>
            <div class="border-top pt-3 pb-3 bg-white">
            </div>
        </div>
        <div class="offcanvas-footer shadow-popup">
            <div class="d-flex justify-content-center flex-wrap flex-sm-nowrap gap-3 px-3 px-sm-4 py-3">
                <a href="{{ url()->previous() }}" class="btn btn-secondary flex-grow-1 non-printable">
                    {{ translate('Back') }}
                </a>
                <input id="print_invoice" type="button" class="btn btn--primary flex-grow-1 non-printable action-print-pos-invoice" data-print="#printableArea" value="{{ translate('Proceed') }}, {{ translate('If thermal printer is ready') }}" />
            </div>
        </div>
    </div>
</div>
