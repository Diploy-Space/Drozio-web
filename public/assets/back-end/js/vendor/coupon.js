"use strict";

function getFieldVisibility() {
    let table = $('[data-field-visibility]').first().attr('data-field-visibility');

    return table ? JSON.parse(table) : {};
}

function toggleCouponFieldGroup(group, isVisible) {
    $('[data-coupon-field="' + group + '"]')
        .toggle(isVisible)
        .find('input, select, textarea')
        .prop('disabled', !isVisible);
}

function applyFieldVisibility() {
    let groups = getFieldVisibility()[$('#coupon_type').val()] || {targeting: true, discount: true};
    let isPercentage = $('#discount_type').val() === 'percentage';

    toggleCouponFieldGroup('targeting', groups.targeting);
    toggleCouponFieldGroup('discount', groups.discount);
    toggleCouponFieldGroup('max-discount', groups.discount && isPercentage);
}

function applyDiscountTypeDisplay() {
    let isPercentage = $('#discount_type').val() === 'percentage';

    $('#discount').attr({
        'max': isPercentage ? '100' : '1000000',
        'placeholder': isPercentage ? 'Ex: 10%' : 'Ex: 500'
    });
    $('#discount_percent').toggle(isPercentage);
    $('#discount_currency').toggle(!isPercentage);
}

function applyCouponDateLimits() {
    const startDate = $('#start_date');
    const expireDate = $('#expire_date');

    if (startDate.val()) {
        expireDate.attr('min', startDate.val());
        return;
    }

    const today = (new Date()).toISOString().split('T')[0];
    startDate.attr('min', today);
    expireDate.attr('min', today);
}

$(document).ready(function () {
    if ($('#code').val() === '') {
        generateCode();
    }

    applyCouponDateLimits();

    applyDiscountTypeDisplay();
    applyFieldVisibility();
});

$("#start_date").on("change", function () {
    $('#expire_date').attr('min', $(this).val());
});

$("#expire_date").on("change", function () {
    $('#start_date').attr('max', $(this).val());
});

$('#coupon_type').on('change', function () {
    applyFieldVisibility();
});

$('#discount_type').on('change', function () {
    applyDiscountTypeDisplay();
    applyFieldVisibility();
});

$('.get-quick-view').on('click', function () {
    let id = $(this).data('id');
    let url = $('#get-detail-url').data('url');
    $.ajax({
        type: 'GET',
        url: url,
        data: {
            id: id
        },
        beforeSend: function () {
            $('#loading').fadeIn();
        },
        success: function (data) {
            $('#loading').fadeOut();
            $('#quick-view').modal('show');
            $('#quick-view-modal').empty().html(data.view);
        }
    });
});

$("#generateCode").on("click", function () {
    generateCode();
});

function generateCode() {
    let code = Math.random().toString(36).substring(2, 12)?.toUpperCase();
    $('#code').val(code)
}

$('.coupon_status_form').on('submit', function (event) {
    event.preventDefault();

    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="_token"]').attr('content')
        }
    });
    $.ajax({
        url: $(this).attr('action'),
        method: 'GET',
        data: $(this).serialize(),
        success: function (data) {
            toastMagic.success(data.message);
        }
    });
});

$('[type="reset"]').on('click', function () {
    setTimeout(function () {
        applyDiscountTypeDisplay();
        applyFieldVisibility();
    }, 1000);
});
