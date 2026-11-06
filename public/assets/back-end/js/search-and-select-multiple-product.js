'use strict';
$('.search-product').on('keyup',function (){
    let name = $(this).val();
    if (name.length > 0) {
        $.get($('#get-search-product-route').data('action'), {searchValue: name}, (response) => {
            $('.search-result-box').empty().html(response.result);
        })
    }
})
let searchTimeout;
$(document).on('input', '.search-all-type-product', function () {
    let name = $(this).val();
    let dealId = $(this).data('deal-id');
    let $input = $(this);
    let $resultBox = $input.closest('.dropdown').find('.search-result-box');

    clearTimeout(searchTimeout);

    if (name.length > 0) {
        searchTimeout = setTimeout(function() {
            let data = { searchValue: name };
            if (dealId) {
                data.deal_id = dealId;
            }
            $.ajax({
                url: $input.data('search-url') || $('#get-search-all-type-product-route').data('action'),
                type: 'GET',
                data: data,
                beforeSend: function () {
                    $resultBox.html(searchLoaderMarkup());
                },
                success: function (response) {
                    $resultBox.empty().html(response.result);
                },
                error: function () {
                    $resultBox.empty();
                }
            });
        }, 500);
    } else {
        $resultBox.empty();
    }
});

function searchLoaderMarkup() {
    return '<div class="d-flex justify-content-center align-items-center py-4 product-search-loader">'
         + '<div class="spinner-border spinner-border-sm text-primary me-2" role="status" aria-hidden="true"></div>'
         + '<span class="text-muted fs-12">' + (window.searchLoaderText || 'Searching...') + '</span>'
         + '</div>';
}

function showAlreadySelectedToast() {
    if (typeof toastMagic === 'undefined' || !toastMagic.warning) return;
    let msg = $('#product-already-selected-message').data('message') || 'Product is already selected';
    toastMagic.warning(msg);
}

$(document).on('shown.bs.dropdown', '.select-product-search, .select-clearance-product-search', function () {
    let $input = $(this).find('.search-all-type-product, .search-product-for-clearance-sale').first();
    if ($input.length) $input.trigger('focus');
});

function closeProductSearchDropdown($wrapper) {
    if (!$wrapper || !$wrapper.length) return;
    let toggle = $wrapper.find('[data-bs-toggle="dropdown"]')[0];
    if (toggle && typeof bootstrap !== 'undefined' && bootstrap.Dropdown) {
        let instance = bootstrap.Dropdown.getOrCreateInstance(toggle);
        if (instance) {
            instance.hide();
            return;
        }
    }
    $wrapper.find('.dropdown-menu').removeClass('show');
    if (toggle) toggle.setAttribute('aria-expanded', 'false');
}

$(document).ready(function () {
    let searchTimeout;
    $(document).on('focus click', '.search-product-for-clearance-sale', function () {
        $(this).closest('.select-clearance-product-search').find('.dropdown-menu').addClass('show');
    });

    $(document).on('input', '.search-product-for-clearance-sale', function () {
        let $input = $(this);
        let $wrapper = $input.closest('.select-clearance-product-search');
        let $resultBox = $wrapper.find('.search-result-box');
        let name = $input.val();
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(function() {
            $.ajax({
                url: $('#get-search-product-for-clearance-route').data('action'),
                type: 'GET',
                data: { searchValue: name },
                beforeSend: function () {
                    $resultBox.html(searchLoaderMarkup());
                },
                success: function (response) {
                    $wrapper.find('.dropdown-menu').addClass('show')
                    $resultBox.html(response.result);
                },
                error: function () {
                    $resultBox.empty();
                }
            });
        }, 500);
    });
    $(document).on('click', function (e) {
        let $target = $(e.target);
        if (!$target.closest('.select-clearance-product-search').length) {
            $('.select-clearance-product-search .dropdown-menu').removeClass('show');
        }
    });
    $(document).on('keypress', '.search-product-for-clearance-sale', function (e) {
        if (e.which === 13) {
            e.preventDefault();
        }
    });
});

let productIdsArray = [];
$(document).on('click', '.select-product-search .select-product-item', function () {
    let $item = $(this);
    let productId = $item.find('.product-id').text();
    if (!productId) return;
    if (productIdsArray.indexOf(productId) === -1) {
        productIdsArray.push(productId);
        getProductDetails(productIdsArray);
    } else {
        showAlreadySelectedToast();
    }
    closeProductSearchDropdown($item.closest('.dropdown'));
})
function removeSelectedProduct(){
    $('.remove-selected-product').on('click', function () {
        let removedId = String($(this).data('product-id'));
        let idx = productIdsArray.indexOf(removedId);
        if (idx !== -1) productIdsArray.splice(idx, 1);
        $(this).closest('.select-product-item').remove();
    });
}
$('.reset-selected-products').on('click',function (){
    productIdsArray = [];
    $('#selected-products').empty();
})

function getProductDetails(productIds){
    $.ajax({
        url: $('#get-multiple-product-details-route').data('action'),
        type: 'GET',
        data: { productIds: productIds },
        beforeSend: function () {
            $("#loading").fadeIn();
        },
        success: function(response) {
            $('#selected-products').empty().html(response.result);

            removeSelectedProduct();
        },
        complete: function () {
            $("#loading").fadeOut();
        },
    });

}

let clearanceProductIdsArray = [];
$(document).on('click', '.select-clearance-product-search .select-clearance-product-item', function () {
    let $item = $(this);
    let productId = $item.find('.product-id').text();
    if (!productId) return;
    if (clearanceProductIdsArray.indexOf(productId) === -1) {
        clearanceProductIdsArray.push(productId);
        getClearanceProductDetails(clearanceProductIdsArray);
    } else {
        showAlreadySelectedToast();
    }
    checkClearanceProductArray()
    closeProductSearchDropdown($item.closest('.dropdown'));
})

function removeSelectedClearanceProduct() {
    $('.remove-selected-clearance-product').on('click', function () {
        let removedId = String($(this).data('product-id'));
        let idx = clearanceProductIdsArray.indexOf(removedId);
        if (idx !== -1) clearanceProductIdsArray.splice(idx, 1);
        $(this).closest('.remove-selected-clearance-parent').remove();
        checkClearanceProductArray()
    });
}

function getClearanceProductDetails(productIds) {
    $.ajax({
        url: $('#get-multiple-clearance-product-details-route').data('action'),
        type: 'GET',
        data: {productIds: productIds},
        beforeSend: function () {
            $("#loading").fadeIn();
        },
        success: function (response) {
            $('#selected-products').empty().html(response.result);
            removeSelectedClearanceProduct();
        },
        complete: function () {
            $("#loading").fadeOut();
        },
    });

}

function checkClearanceProductArray() {
    const hasSelectedProducts = clearanceProductIdsArray?.length > 0;
    $('.search-and-add-product').toggle(!hasSelectedProducts);
    $('.clearance-selected-products').each(function () {
        if (hasSelectedProducts) {
            this.style.removeProperty('display');
        } else {
            this.style.setProperty('display', 'none', 'important');
        }
    });
}

$(document).ready(checkClearanceProductArray);

$(document).ready(function() {
    const $selectedProductsContainer = $('#selected-products');
    const $addProductsBtn = $('#add-products-btn');
    function toggleAddProductsButton() {
        $addProductsBtn.prop('disabled', $selectedProductsContainer.children().length === 0);
    }

    toggleAddProductsButton();

    const observer = new MutationObserver(toggleAddProductsButton);
    observer.observe($selectedProductsContainer[0], { childList: true });
});
