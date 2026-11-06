'use strict';
$(".lang-link").click(function (e) {
    e.preventDefault();
    $('.lang-link').removeClass('active');
    $(".lang-form").addClass('d-none');
    $(this).addClass('active');
    let formId = this.id;
    let lang = formId.split("-")[0];
    $("#" + lang + "-form").removeClass('d-none');
});

let clearanceSaleDiscountElement = $('.clearance-sale-discount');

clearanceSaleDiscountElement.on('change', function () {
    if ($(this).val() === 'flat') {
        $('.clearance-sale-discount-flat').show();
    } else {
        $('.clearance-sale-discount-flat').hide();
    }
})

let offerActiveTimeElement = $('.offer-active-time');

offerActiveTimeElement.on('change', function () {
    if ($(this).val() === 'always') {
        $('.offer-active-time-section').hide();
    } else {
        $('.offer-active-time-section').show();
    }
})


document.addEventListener('DOMContentLoaded', function () {
    let tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
});

$(document).on('submit', '.deal-of-the-day-status-form', function (e) {
    e.preventDefault();

    const $form = $(this);
    const $input = $form.find('.switcher_input');

    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="_token"]').attr('content'),
        },
    });

    $.ajax({
        url: $form.attr('action'),
        method: 'POST',
        data: $form.serialize(),
        success: function (response) {
            toastMagic.success(response.message);

            if (!$input.is(':checked')) {
                return;
            }

            $('.deal-of-the-day-status-form').not($form).find('.switcher_input').prop('checked', false);
        },
        error: function (xhr) {
            $input.prop('checked', !$input.is(':checked'));
            toastMagic.error(xhr.responseJSON?.message);
        },
    });
});
