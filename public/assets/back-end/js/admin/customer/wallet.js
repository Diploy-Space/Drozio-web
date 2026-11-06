'use strict';

$(document).ready(function () {
    const bonusForms = $('.bonus-setup-form');

    function updateBonusAmountUnit(form) {
        const isPercentage = form.find('.bonus-type-select').val() === 'percentage';
        form.find('.max-bonus-amount-area').toggleClass('d-none', !isPercentage);
        form.find('.bonus-amount-unit').text(isPercentage ? '%' : form.data('currency-symbol'));
    }

    bonusForms.each(function () {
        updateBonusAmountUnit($(this));
    });

    bonusForms.on('change', '.bonus-type-select', function () {
        updateBonusAmountUnit($(this).closest('.bonus-setup-form'));
    });

    bonusForms.on('reset', function () {
        const form = $(this);
        setTimeout(function () {
            updateBonusAmountUnit(form);
        }, 0);
    });

    bonusForms.on('change', '.bonus-start-date, .bonus-end-date', function () {
        const form = $(this).closest('.bonus-setup-form');
        const startDate = form.find('.bonus-start-date').val();
        const endDate = form.find('.bonus-end-date').val();

        if (startDate === '' || endDate === '' || startDate <= endDate) {
            return;
        }

        $(this).val('');
        toastMagic.error(form.data('date-range-message'));
    });
});
