<div class="modal fade" id="logoutModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="exampleModalLabel">{{ translate('Ready to Leave') . '?' }}</h5>
                <button class="close" type="button" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">×</span>
                </button>
            </div>
            <div class="modal-body">
                {{ translate('Select Logout below if you are ready to end your current session') . '.' }}</div>
            <div class="modal-footer">
                <form action="{{ route('admin.logout') }}" method="post">
                    @csrf
                    <button class="btn btn-danger" type="button"
                        data-dismiss="modal">{{ translate('Cancel') }}</button>
                    <button class="btn btn--primary" type="submit">{{ translate('Logout') }}</button>
                </form>
            </div>
        </div>
    </div>
</div>

<div class="modal" id="popup-modal">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-body py-4">
                <div class="row">
                    <div class="col-12">
                        <div class="d-flex flex-wrap justify-content-center gap-2">
                            <div class="text-center w-100">
                                <h4 class="__color-8a8a8a">
                                    <i class="tio-shopping-cart-outlined"></i>
                                    {{ translate('You have new order') . ', ' . translate('Check Please') . '.' }}
                                </h4>
                            </div>
                            <div class="d-flex justify-content-center gap-2">
                                <button class="btn btn-danger ignore-check-order">
                                    {{ translate('Ignore this now') }}
                                </button>
                                <button class="btn btn-primary check-order">
                                    {{ translate('Okay') . ', ' . translate('Let me check') }}
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>


@include('layouts.back-end.partials.advanced-search._modal', ['searchUrl' => route('admin.v2.advanced-search')])
