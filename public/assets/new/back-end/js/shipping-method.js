
'use strict';

$(document).ready(function () {
    let shippingTypeValue = $('input[name="shippingType"]:checked').val();
    shippingType(shippingTypeValue);
});

$("#shipping-type-form").on('submit', function (event) {
    event.preventDefault();
    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="_token"]').attr('content')
        }
    });
    $.ajax({
        url: $(this).attr('action'),
        method: 'POST',
        data: $(this).serialize(),
        success: function (response) {
            if (response?.status === 1) {
                toastMagic.success(response?.message);

                let shippingTypeValue = $('input[name="shippingType"]:checked').val();
                shippingType(shippingTypeValue);
            }
        }
    });
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

function deleteDataWithoutForm() {
    $(".delete-data").on("click", function () {
        let getText = $("#order-wise-shipping-method-delete-content");
        let dataFrom = $(this).data("from");
        Swal.fire({
            title: getText.data("sure"),
            text: getText.data("text"),
            icon: "warning",
            showCancelButton: true,
            confirmButtonColor: "#1455AC",
            cancelButtonColor: "#d33",
            cancelButtonText: getText.data("cancel"),
            confirmButtonText: getText.data("confirm"),
            reverseButtons: true,
        }).then((result) => {
            if (result.value) {
                $.ajaxSetup({
                    headers: {
                        "X-CSRF-TOKEN": $('meta[name="_token"]').attr(
                            "content"
                        ),
                    },
                });
                let id = $(this).data("id");
                $.ajax({
                    url: $(this).data("action"),
                    method: "POST",
                    data: {id: id},
                    success: function (response) {
                        if (dataFrom == "currency") {
                            if (response.status == 1) {
                                toastMagic.success(
                                    $("#get-delete-currency-message").data(
                                        "success"
                                    )
                                );
                            } else {
                                toastMagic.warning($("#get-delete-currency-message").data("warning"));
                            }
                        }

                        setTimeout(() => {
                            location.reload();
                        }, 2000);
                    },
                });
            }
        });
    });
}

deleteDataWithoutForm();

let categoryCostTimers = {};

function categoryCostRowPayload(row) {
    let cost = row.find('.category-cost-value').val();

    if (cost === '' || isNaN(parseFloat(cost)) || parseFloat(cost) < 0) {
        return null;
    }

    return {
        categoryId: row.data('category-id'),
        cost: cost,
        multiplyQuantity: row.find('.category-cost-multiply').is(':checked') ? 1 : 0
    };
}

function saveCategoryCosts(rows) {
    let payloads = rows.map(categoryCostRowPayload).filter(function (payload) {
        return payload !== null;
    });

    if (payloads.length === 0) {
        return;
    }

    $.ajax({
        url: $('#category-cost-form').data('action'),
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': $('meta[name="_token"]').attr('content')
        },
        data: {
            ids: payloads.map(function (payload) { return payload.categoryId; }),
            cost: payloads.map(function (payload) { return payload.cost; }),
            multiply_qty: payloads.map(function (payload) { return payload.multiplyQuantity; })
        },
        success: function (response) {
            toastMagic.success(response.message);
        },
        error: function (xhr) {
            let errors = xhr.responseJSON?.errors;
            let message = Array.isArray(errors) ? errors[0]?.message : xhr.responseJSON?.message;
            if (message) {
                toastMagic.error(message);
            }
        }
    });
}

$(document).on('input', '#category-cost-form .category-cost-value', function () {
    let row = $(this).closest('.category-cost-row');
    let categoryId = row.data('category-id');
    clearTimeout(categoryCostTimers[categoryId]);
    categoryCostTimers[categoryId] = setTimeout(function () {
        saveCategoryCosts([row]);
    }, 600);
});

$(document).on('change', '#category-cost-form .category-cost-multiply', function () {
    let row = $(this).closest('.category-cost-row');
    clearTimeout(categoryCostTimers[row.data('category-id')]);
    saveCategoryCosts([row]);
});

$(document).on('click', '#category-cost-bulk-apply', function () {
    const value = $('#category-cost-bulk-value').val();

    if (value === '' || Number(value) < 0) {
        $('#category-cost-bulk-value').trigger('focus');
        return;
    }

    let rows = $('#category-cost-form .category-cost-row').toArray().map(function (row) {
        return $(row);
    });
    rows.forEach(function (row) {
        clearTimeout(categoryCostTimers[row.data('category-id')]);
        row.find('.category-cost-value').val(value);
    });
    saveCategoryCosts(rows);
});
