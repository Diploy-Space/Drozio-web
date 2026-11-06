<div class="modal fade" id="logoutModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel"
     aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="exampleModalLabel">{{translate('Ready to Leave').'?'}}</h5>
                <button class="close" type="button" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">×</span>
                </button>
            </div>
            <div class="modal-body">{{translate('Select Logout below if you are ready to end your current session').'.'}}</div>
            <div class="modal-footer">
                <button class="btn btn-secondary" type="button" data-dismiss="modal">{{translate('Cancel')}}</button>
                <a class="btn btn--primary" href="{{route('vendor.auth.logout')}}">{{translate('Logout')}}</a>
            </div>
        </div>
    </div>
</div>

<div class="modal" id="popup-modal">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-body">
                <div class="row">
                    <div class="col-12">
                        <div class="text-center">
                            <h2 class="__color-8a8a8a">
                                <i class="tio-shopping-cart-outlined"></i> {{translate('You have new order').','.translate('Check Please')}}.
                            </h2>
                            <hr>
                            <button class="btn btn-warning ignore-check-order">{{ translate('Ignore this now') }}</button>
                            <button class="btn btn--primary check-order">{{translate('Okay').','.translate('Let me check')}}</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="NotificationModal" tabindex="-1" aria-labelledby="shiftNotificationModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content shadow-lg" id="NotificationModalContent">
        </div>
    </div>
</div>
