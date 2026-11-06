<div class="modal fade" id="howItWorksModal" tabindex="-1" aria-labelledby="howItWorksModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header border-0 pb-0 d-flex justify-content-end">
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ translate('Close') }}"
                        style="padding: 1rem; margin: -1rem;">
                </button>
            </div>
            <div class="modal-body px-4 px-sm-5 pt-0 text-center">
                <div class="d-flex flex-column align-items-center gap-2">
                    <img width="80" class="mb-3" src="{{ dynamicAsset(path: 'public/assets/back-end/img/wallet-bonus.png') }}"
                         loading="lazy" alt="">
                    <h3 class="lh-base">{{ translate('Wallet bonus applies only to top-ups made through a payment gateway.').'!' }}</h3>
                    <p>{{ translate('Bonus added to the customer wallet on top of a gateway top-up.').'. '.translate('The bonus is deducted from the admin wallet as an expense.').'.' }}</p>
                </div>
            </div>
        </div>
    </div>
</div>
