'use strict';
$(document).ready(function () {
    let shippingTypeValue = $('#get-shipping-type-value').data('value');
    shippingType(shippingTypeValue);
});
function shippingType(shippingTypeValue){
    if (shippingTypeValue === 'category_wise') {
        $('#product_wise_note').hide();
        $('#order_wise_shipping').hide();
        $('#update_category_shipping_cost').show();

    } else if (shippingTypeValue === 'order_wise') {
        $('#product_wise_note').hide();
        $('#update_category_shipping_cost').hide();
        $('#order_wise_shipping').show();
    } else {
        $('#update_category_shipping_cost').hide();
        $('#order_wise_shipping').hide();
        $('#product_wise_note').show();
    }
}
$('.shipping-type').on('change', 'input[name="shippingType"]', function (){
    let shippingTypeValue = $(this).val();
    shippingType(shippingTypeValue);
    let shippingTypeData = $('#get-shipping-type-data');
    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="_token"]').attr('content')
        }
    });
    $.ajax({
        url: shippingTypeData.data('action'),
        method: 'POST',
        data: {
            shippingType: shippingTypeValue
        },
        success: function () {
            toastMagic.success(shippingTypeData.data('success'));
        },
        error: showShippingRequestError
    });
})

function showShippingRequestError(xhr) {
    let errors = xhr.responseJSON?.errors;
    let message = Array.isArray(errors) ? errors[0]?.message : xhr.responseJSON?.message;
    if (message) {
        toastMagic.error(message);
    }
}

let categoryShippingCostTimers = {};

function saveCategoryShippingCost(categoryId) {
    let table = $('#category-shipping-cost-table');
    let costInput = table.find('.category-shipping-cost-input[data-category-id="' + categoryId + '"]');
    let multiplyInput = table.find('.category-shipping-multiply-input[data-category-id="' + categoryId + '"]');
    let cost = costInput.val();

    if (cost === '' || isNaN(parseFloat(cost)) || parseFloat(cost) < 0) {
        return;
    }

    $.ajax({
        url: table.data('action'),
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': $('meta[name="_token"]').attr('content')
        },
        data: {
            ids: [categoryId],
            cost: [cost],
            multiply_qty: [multiplyInput.is(':checked') ? 1 : 0]
        },
        success: function (response) {
            toastMagic.success(response.message);
        },
        error: showShippingRequestError
    });
}

$(document).on('input', '.category-shipping-cost-input', function () {
    let categoryId = $(this).data('category-id');
    clearTimeout(categoryShippingCostTimers[categoryId]);
    categoryShippingCostTimers[categoryId] = setTimeout(function () {
        saveCategoryShippingCost(categoryId);
    }, 600);
});

$(document).on('change', '.category-shipping-multiply-input', function () {
    let categoryId = $(this).data('category-id');
    clearTimeout(categoryShippingCostTimers[categoryId]);
    saveCategoryShippingCost(categoryId);
});
