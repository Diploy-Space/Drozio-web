$(document).ready(function () {
    // Each free-delivery card (Retail / Wholesale) is toggled independently, so
    // the responsibility -> "free delivery over amount" visibility is scoped to
    // the card rather than relying on shared element ids.
    function toggleAmountArea($card, responsibility) {
        const $area = $card.find('.free-delivery-over-amount-admin-area');
        if (responsibility === 'admin') {
            $area.show();
        } else {
            $area.hide();
        }
    }

    function currentResponsibility($card) {
        return $card.find('.free-delivery-responsibility-radio:checked').val()
            || $card.find('.free-delivery-responsibility').data('default');
    }

    $('.js-free-delivery-setup').each(function () {
        const $card = $(this);

        toggleAmountArea($card, currentResponsibility($card));

        $card.on('change', '.free-delivery-responsibility-radio', function () {
            toggleAmountArea($card, $(this).val());
        });
    });

    // On reset the radios revert to their default checked state; re-evaluate each
    // card against its stored default after the browser applies the reset.
    $('button[type="reset"]').click(function () {
        setTimeout(function () {
            $('.js-free-delivery-setup').each(function () {
                const $card = $(this);
                toggleAmountArea($card, $card.find('.free-delivery-responsibility').data('default'));
            });
        }, 10);
    });

    // Remember the active free-delivery tab (Retail / Wholesale). The hidden field
    // carries it on submit; the ?tab= query param keeps it across manual reloads.
    $('#myTab button[data-bs-toggle="tab"]').on('shown.bs.tab', function () {
        const tab = $(this).data('tab-key');
        if (!tab) return;
        $('#active-order-settings-tab').val(tab);
        const url = new URL(window.location.href);
        url.searchParams.set('tab', tab);
        window.history.replaceState({}, '', url);
    });
});
