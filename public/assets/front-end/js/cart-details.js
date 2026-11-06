"use strict";

/* Shopping cart page. Nothing here raises a toast: every outcome is stated inline, next
   to the control the customer would use to act on it. */

function updateCartQuantityList(minimumOrderQuantity, key, increment, event) {
    let input = $('#cart_quantity_web' + key);
    let quantity = parseInt(input.val()) + parseInt(increment);

    if (input.val() > input.data('current-stock') && event === 'minus') {
        removeProductFromCartList(key);
        return;
    }

    if (minimumOrderQuantity > quantity && event !== 'delete') {
        showCartQuantityHint(input);
        $('.cartQuantity' + key).val(minimumOrderQuantity);
        return;
    }

    clearCartQuantityHint(input);

    if (input.val() == input.data('min') && event === 'delete') {
        removeProductFromCartList(key);
        return;
    }

    /* Answer the click straight away — the row re-renders from the server response, but the
       stepper should not sit on the old number while that round trip happens. */
    $('.cartQuantity' + key).val(quantity);
    cartQuantityStepperState();

    $.post($('#route-cart-updateQuantity').data('url'), {
        _token: $('meta[name="_token"]').attr('content'),
        key,
        quantity
    }, function (response) {
        if (response.status == 0) {
            showCartQuantityHint(input, response.message);
            $('.cartQuantity' + key).val(response['qty']);
            cartQuantityStepperState();
            return;
        }
        refreshCartSummary(response);
    });
}

function removeProductFromCartList(key) {
    $.post($('#route-cart-remove').data('url'), {
            _token: $('meta[name="_token"]').attr('content'),
            key: key
        },
        function (response) {
            let segmentArray = window.location.pathname.split('/');
            let segment = segmentArray[segmentArray.length - 1];
            if (segment === 'checkout-payment' || segment === 'checkout-details') {
                location.reload();
                return;
            }
            refreshCartSummary(response.data);
        });
}

/* Every cart mutation returns the re-rendered summary; re-running the initialisers keeps
   the replaced DOM wired to the same handlers the server-rendered page had. */
function refreshCartSummary(html) {
    $('#cart-summary').empty().html(html);
    // Tooltips are appended to <body>, so replacing their trigger leaves them on screen.
    $('body > .tooltip').remove();
    $('[data-toggle="tooltip"]').tooltip();
    cartListQuantityUpdateInit();
    actionCheckoutFunctionInit();
    cartPageControlsInit();
    couponCode();
    updateNavCart();
}

/* The minimum-order rule and a rejected quantity are both about one row, so they are
   stated under that row's stepper rather than in a toast over the page. */
function showCartQuantityHint(input, message) {
    let hint = input.closest('.sc-item__qty').find('.sc-qty-hint');
    hint.text(message || hint.data('minimum-order-message')).addClass('is-shown');
}

function clearCartQuantityHint(input) {
    input.closest('.sc-item__qty').find('.sc-qty-hint').removeClass('is-shown').text('');
}

function cartPageControlsInit() {
    $('.sc-page .action-remove-cart-item').off('click.cartPage').on('click.cartPage', function () {
        removeProductFromCartList($(this).data('cart-id'));
    });

    /* One request, not one per shop: the per-shop handler in custom.js already collects every
       checked row on the page, so a single change event syncs the whole selection. */
    $('#sc-select-all').off('change.cartPage').on('change.cartPage', function () {
        let selectAll = this.checked;
        $('.sc-page .shop-item-check, .sc-page .shop-head-check').prop('checked', selectAll);
        $('.sc-page .shop-head-check-desktop').first().trigger('change');
    });

    $('.sc-page .shop-item-check, .sc-page .shop-head-check')
        .off('change.cartPage').on('change.cartPage', cartSelectAllState);

    cartSelectAllState();
    cartQuantityStepperState();
}

function cartSelectAllState() {
    let items = $('.sc-page .shop-item-check');
    let checked = items.filter(':checked').length;

    $('#sc-select-all')
        .prop('checked', items.length > 0 && checked === items.length)
        .prop('indeterminate', checked > 0 && checked < items.length);
}

/* The bounds are a property of the row, so the buttons carry them instead of letting the
   customer submit a quantity the server will only reject. */
function cartQuantityStepperState() {
    $('.sc-page .qty_input').each(function () {
        let input = $(this);
        let quantity = parseInt(input.val());
        let minimum = parseInt(input.data('minimum-order')) || 1;
        let stock = parseInt(input.data('current-stock'));

        if (input.prop('disabled')) {
            return;
        }
        input.siblings('.qty_minus').prop('disabled', !isNaN(quantity) && quantity <= minimum);
        input.siblings('.qty_plus').prop('disabled', !isNaN(quantity) && !isNaN(stock) && quantity >= stock);
    });
}

function setShippingId(id, cartGroupId) {
    if (!id) {
        return;
    }

    $.get({
        url: $('#route-customer-set-shipping-method').data('url'),
        dataType: 'json',
        data: {
            id: id,
            cart_group_id: cartGroupId
        },
        beforeSend: function () {
            $('#loading').show();
        },
        success: function () {
            location.reload();
        },
        complete: function () {
            $('#loading').hide();
        },
    });
}

/* A checkout attempt that cannot go through does not navigate away and does not raise a
   toast. Every reason is already on the page — beside the control that resolves it — so the
   attempt only escalates those hints and takes the customer to the first one. */
function handleBlockedCheckout(messages) {
    let notes = $('.sc-page .sc-checkout-note');

    if (messages && messages.length) {
        notes.find('.sc-checkout-note__list').empty().append(
            messages.map(function (message) {
                return $('<li></li>').text(message);
            })
        );
        notes.prop('hidden', false);
    }

    flagUnresolvedShippingChoices();
}

function flagUnresolvedShippingChoices() {
    let pending = $('.sc-page .sc-ship--pending');

    $('.sc-page .sc-shop, .sc-page .sc-panel').removeClass('is-flagged');
    pending.closest('.sc-shop, .sc-panel').addClass('is-flagged');

    let target = pending.first().closest('.sc-shop, .sc-panel').get(0)
        || $('.sc-page .sc-note--attention, .sc-page .sc-note--danger').first().closest('.sc-shop').get(0)
        || $('.sc-page .sc-checkout-note:visible').get(0);

    if (target) {
        target.scrollIntoView({behavior: 'smooth', block: 'center'});
    }
}

$(function () {
    cartPageControlsInit();

    if ($('#cart-summary').data('flag-blockers')) {
        flagUnresolvedShippingChoices();
    }
});
