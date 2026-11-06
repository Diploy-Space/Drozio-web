'use strict';
$('.search-product').on('input', function () {
    let name = $(this).val().trim();
    if (name.length === 0) {
        $.get($('#get-search-product-route').data('action'), {searchValue: ''}, (response) => {
            $('.search-result-box').html(response.result);
        });
        return;
    }
    $.get($('#get-search-product-route').data('action'), {searchValue: name}, (response) => {
        $('.search-result-box').html(response.result);
    });
});

$(document).on('focus click keyup', '.search-all-type-product', function () {
    let name = $(this).val();
    let $wrapper = $(this).closest('.dropdown');
    let searchUrl = $wrapper.data('search-url') || $('#get-search-product-route').data('action');
    let params = {searchValue: name};

    if ($wrapper.data('all-products-option') !== undefined) {
        params.all_products_option = $wrapper.data('all-products-option');
    }

    $wrapper.find('.dropdown-menu').addClass('show');
    $.get(searchUrl, params, (response) => {
        $wrapper.find('.search-result-box').empty().html(response.result);
    })
})

$(document).on('click', '.select-product-search .select-product-item', function () {
    let productName = $(this).find('.product-name').text() || 'All Products';
    let productId = $(this).find('.product-id').text();
    let $wrapper = $(this).closest('.select-product-search');
    $wrapper.find('button.dropdown-toggle, button.dropdown--toggle').text(productName);
    $wrapper.find('.product_id').val(productId);
})
$(document).on('keydown', '.select-product-search input', function (e) {
    if (e.key === 'Enter') {
        e.preventDefault();
        return false;
    }
});
