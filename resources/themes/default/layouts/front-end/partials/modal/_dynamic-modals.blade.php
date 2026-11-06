<div class="modal fade out-of-stock-modal" id="out-of-stock-modal" tabindex="-1" aria-labelledby="out-of-stock-modal-title" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <button type="button" class="out-of-stock-modal__close" data-dismiss="modal" aria-label="{{ translate('Close') }}">
                <i class="czi-close"></i>
            </button>
            <div class="modal-body text-center">
                <div class="out-of-stock-modal__icon">
                    <img src="{{ theme_asset(path: 'public/assets/front-end/img/icons/out-of-stock.png') }}" alt="{{ translate('Out of Stock') }}">
                </div>
                <h5 class="out-of-stock-modal__title" id="out-of-stock-modal-title">{{ translate('Unable to add to cart') }}</h5>
                <p class="out-of-stock-modal__message" id="out-of-stock-modal-message">{{ translate('Out of Stock') }}</p>
                <button type="button" class="btn btn--primary btn-block out-of-stock-modal__action" data-dismiss="modal">
                    {{ translate('Okay') }}
                </button>
            </div>
        </div>
    </div>
</div>
