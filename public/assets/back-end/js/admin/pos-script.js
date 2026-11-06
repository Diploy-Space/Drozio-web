"use strict";

const posPath = new URL($("#route-admin-pos-index").data("url"), window.location.href).pathname.replace(/\/$/, "");
const elementViewAllHoldOrdersSearch = $(".view_all_hold_orders_search");
const getYesWord = $("#message-yes-word").data("text");
const getNoWord = $("#message-no-word").data("text");
const messageAreYouSure = $("#message-are-you-sure").data("text");
const productSearchDelay = 250;
const sessionExpiredReloadDelay = 2000;
const customerCreatedReloadDelay = 1200;
const cartQuantityMaxFallback = 1000;
const compactSearchMedia = window.matchMedia("(max-width: 1199.98px)");
const mobileLayoutMedia = window.matchMedia("(max-width: 991.98px)");

let isPlacingOrder = false;
let productSearchTimer = null;
let productSearchRequest = null;

$.ajaxSetup({
    headers: {
        "X-CSRF-TOKEN": $('meta[name="_token"]').attr("content"),
    },
});

function posMessage(key) {
    return $("#message-" + key).data("text");
}

function submitPostRedirect(url, data = {}) {
    let form = document.createElement("form");
    form.method = "POST";
    form.action = url;
    form.style.display = "none";
    let payload = Object.assign(
        { _token: $('meta[name="_token"]').attr("content") },
        data
    );
    Object.keys(payload).forEach(function (key) {
        let input = document.createElement("input");
        input.type = "hidden";
        input.name = key;
        input.value = payload[key];
        form.appendChild(input);
    });
    document.body.appendChild(form);
    form.submit();
}

function isPosRequest(settings) {
    const path = new URL(settings.url, window.location.href).pathname;
    return path === posPath || path.startsWith(posPath + "/");
}

function getFirstValidationMessage(response) {
    const errors = response?.errors;
    if (Array.isArray(errors)) {
        return errors[0]?.message ?? null;
    }
    if (errors && typeof errors === "object") {
        return Object.values(errors).flat()[0] ?? null;
    }
    return response?.message ?? null;
}

function getRequestErrorMessage(xhr) {
    const response = xhr.responseJSON;
    if (xhr.status === 422) {
        return getFirstValidationMessage(response) ?? posMessage("something-went-wrong");
    }
    if (response?.status === "error" && response?.message) {
        return response.message;
    }
    return posMessage("something-went-wrong");
}

$(document).ajaxError(function (event, xhr, settings) {
    if (xhr.statusText === "abort" || !isPosRequest(settings)) {
        return;
    }
    $("#loading").stop(true, true).fadeOut();
    if (xhr.status === 419) {
        toastMagic.error(posMessage("session-expired"));
        setTimeout(() => window.location.reload(), sessionExpiredReloadDelay);
        return;
    }
    toastMagic.error(getRequestErrorMessage(xhr));
});

function posCartHasLines() {
    return $("#cart-summary .pos-line").length > 0;
}

function isPosDialogOpen() {
    return Swal.isVisible() || $(".modal.show, .offcanvas.show").length > 0;
}

function toastEmptyCart(messageKey = "cart-is-empty") {
    toastMagic.warning(posMessage(messageKey));
}

function showPosModal(elementId) {
    const element = document.getElementById(elementId);
    if (element) {
        bootstrap.Modal.getOrCreateInstance(element).show();
    }
}

function showPosOffcanvas(elementId) {
    const element = document.getElementById(elementId);
    if (element) {
        bootstrap.Offcanvas.getOrCreateInstance(element).show();
    }
}

function requireCartLines(action, emptyMessageKey = "cart-is-empty") {
    return function () {
        if (isPosDialogOpen()) {
            return;
        }
        if (!posCartHasLines()) {
            toastEmptyCart(emptyMessageKey);
            return;
        }
        action();
    };
}

function focusProductSearch() {
    if (isPosDialogOpen()) {
        return;
    }
    $("#search").trigger("focus").trigger("select");
}

function confirmOpenDialog() {
    if (Swal.isVisible()) {
        Swal.clickConfirm();
    }
}

function cancelOpenDialog() {
    if (Swal.isVisible()) {
        Swal.close();
    }
}

function openAddCustomerOffcanvas() {
    if (!isPosDialogOpen()) {
        showPosOffcanvas("offcanvasAddNewCustomer");
    }
}

function submitAddCustomerForm() {
    const form = document.getElementById("add-new-customer-form");
    if (form && $("#offcanvasAddNewCustomer").hasClass("show")) {
        form.requestSubmit();
    }
}

function printPosInvoice() {
    const printButton = $("#print_invoice");
    if (printButton.length) {
        printSpecificSectionWithPrintArea(printButton.data("print"));
    }
}

function closePosInvoice() {
    const element = document.getElementById("print-invoice");
    if (element) {
        bootstrap.Offcanvas.getOrCreateInstance(element).hide();
    }
}

function toggleShortcutList() {
    const element = document.getElementById("short-cut-keys");
    if (!element) {
        return;
    }
    if ($(element).hasClass("show")) {
        bootstrap.Modal.getOrCreateInstance(element).hide();
    } else if (!isPosDialogOpen()) {
        bootstrap.Modal.getOrCreateInstance(element).show();
    }
}

const posShortcuts = {
    KeyQ: focusProductSearch,
    KeyO: requireCartLines(startPlaceOrder),
    KeyS: confirmOpenDialog,
    KeyZ: cancelOpenDialog,
    KeyC: requireCartLines(() => showPosModal("clearCartModal")),
    KeyX: requireCartLines(() => showPosModal("clearCartModal")),
    KeyR: requireCartLines(() => showPosModal("holdOrderModal"), "no-product-to-hold"),
    KeyA: openAddCustomerOffcanvas,
    KeyN: submitAddCustomerForm,
    KeyE: requireCartLines(() => showPosModal("add-discount")),
    KeyD: requireCartLines(() => showPosModal("add-coupon-discount")),
    KeyP: printPosInvoice,
    KeyB: closePosInvoice,
    KeyK: toggleShortcutList,
};

window.addEventListener("keydown", function (event) {
    if (!event.altKey || event.ctrlKey || event.metaKey || event.shiftKey) {
        return;
    }
    if (event.isComposing || event.keyCode === 229) {
        return;
    }
    const shortcut = posShortcuts[event.code];
    if (!shortcut) {
        return;
    }
    event.preventDefault();
    if (!event.repeat) {
        shortcut();
    }
}, true);

function searchProducts(name) {
    productSearchRequest = $.get({
        url: $("#route-admin-products-search-product").data("url"),
        dataType: "json",
        data: {
            name: name,
        },
        success: function (data) {
            $(".pos-search-card").removeClass("d-none").show();
            $("#pos-search-box").removeClass("d-none").show();
            $(".search-result-box").empty().html(data.result);
        },
        complete: function (xhr) {
            if (productSearchRequest !== xhr) {
                return;
            }
            productSearchRequest = null;
            $(".pos-search").removeClass("is-searching");
        },
    });
}

function abortProductSearch() {
    clearTimeout(productSearchTimer);
    const pendingRequest = productSearchRequest;
    productSearchRequest = null;
    if (pendingRequest) {
        pendingRequest.abort();
    }
}

$(document).on("input", ".search-bar-input", function () {
    abortProductSearch();
    let name = $(this).val();
    if (name.trim().length === 0) {
        $(".pos-search").removeClass("is-searching");
        $(".search-result-box").empty().hide();
        return;
    }
    $(".pos-search").addClass("is-searching");
    productSearchTimer = setTimeout(() => searchProducts(name), productSearchDelay);
});

function renderCustomerAmountForPay() {
    let button = $("#submit_order");
    let wrapper = $(".place-order-wrapper");
    if (!posCartHasLines()) {
        button.prop("disabled", true);
        return;
    }
    let walletBalance = parseFloat($(".customer-wallet-balance").val());
    let totalAmount = parseFloat($(".total-amount").val());
    let paymentType = $('input[name="type"]:checked').val();
    let isWalletShort = paymentType === "wallet" && walletBalance < totalAmount;
    let paidAmountInput = $(".pos-paid-amount-element");
    let isCashShort = paymentType === "cash" && (parseFloat(paidAmountInput.val()) || 0) < (parseFloat(paidAmountInput.attr("min")) || 0);

    button.prop("disabled", isPlacingOrder || isWalletShort || isCashShort);
    let tooltipInstance = bootstrap.Tooltip.getInstance(wrapper[0]);
    if (isWalletShort) {
        wrapper.attr("title", posMessage("insufficient-wallet-balance")).attr("data-bs-toggle", "tooltip");
        if (!tooltipInstance) {
            new bootstrap.Tooltip(wrapper[0]);
        }
    } else {
        if (tooltipInstance) {
            tooltipInstance.dispose();
        }
        wrapper.removeAttr("title").removeAttr("data-bs-toggle");
    }
}

function changePosCustomer(userId, onComplete = null) {
    return $.post({
        url: $("#route-admin-pos-change-customer").data("url"),
        data: {
            _token: $('meta[name="_token"]').attr("content"),
            user_id: userId,
        },
        beforeSend: function () {
            $("#loading").fadeIn();
        },
        success: function (data) {
            $(".admin-pos-customer-info").empty().html(data.view);
            renderCartSummary(data?.cart_view);
            updateHoldOrdersCounter(data.totalHoldOrders);
            (data?.holdNotices ?? []).forEach((notice) => toastMagic.info(notice));
            toastAdjustmentNotices(data);
        },
        complete: function () {
            $("#loading").fadeOut();
            if (onComplete) {
                onComplete();
            }
        },
    });
}

$(".action-customer-change").on("click", function () {
    changePosCustomer($(this).data("id"));
});

$(document).on("pos:customer-created", "#add-new-customer-form", function (event, response) {
    if (!response?.customerId) {
        return;
    }
    event.preventDefault();
    changePosCustomer(response.customerId, () => setTimeout(() => location.reload(), customerCreatedReloadDelay));
});

$(".action-view-all-hold-orders").on("click", () => viewAllHoldOrders());
elementViewAllHoldOrdersSearch.on("input", () => viewAllHoldOrders("keyup"));

function updateHoldOrdersCounter(totalHoldOrders) {
    $(".total_hold_orders").text(totalHoldOrders)
        .toggleClass("d-none", (parseInt(totalHoldOrders) || 0) < 1);
}

function viewAllHoldOrders(action = null) {
    $.post({
        url: $("#route-admin-pos-view-hold-orders").data("url"),
        data: {
            customer: elementViewAllHoldOrdersSearch.val(),
        },
        beforeSend: function () {
            $("#loading").fadeIn();
        },
        success: function (data) {
            $("#hold-orders-modal-content").empty().html(data.view);
            if (action !== "keyup") {
                showPosModal("hold-orders-modal");
            }
            updateHoldOrdersCounter(data.totalHoldOrders);
        },
        complete: function () {
            $("#loading").fadeOut();
        },
    });
}

$(document).on("click", ".action-cart-change", function () {
    submitPostRedirect($("#route-admin-pos-change-cart-editable").data("url"), {
        cart_id: $(this).data("cart"),
    });
});

function cancelHeldOrder(cartId) {
    $.post({
        url: $("#route-admin-pos-cancel-order").data("url"),
        data: {
            cart_id: cartId,
        },
        beforeSend: function () {
            $("#loading").fadeIn();
        },
        success: function (data) {
            $("#hold-orders-modal-content").empty().html(data.view);
            updateHoldOrdersCounter(data.totalHoldOrders);
            if (elementViewAllHoldOrdersSearch.val()) {
                viewAllHoldOrders("keyup");
            }
            toastMagic.success(data.message);
        },
        complete: function () {
            $("#loading").fadeOut();
        },
    });
}

$(document).on("click", ".action-cancel-customer-order", function () {
    const cartId = $(this).data("cart-id");
    Swal.fire({
        title: messageAreYouSure,
        icon: "warning",
        text: posMessage("want-to-cancel-held-order"),
        showCancelButton: true,
        showConfirmButton: true,
        confirmButtonColor: "#1455AC",
        cancelButtonColor: "#d33",
        cancelButtonText: getNoWord,
        confirmButtonText: getYesWord,
        reverseButtons: true,
    }).then(function (result) {
        if (result.value) {
            cancelHeldOrder(cartId);
        }
    });
});

$(document).on("click", ".action-select-product, .action-select-search-product", function () {
    quickView($(this).data("id"));
});

$(document).on("click", "#quick-view .action-get-variant-for-already-in-cart", function () {
    getVariantForAlreadyInCart($(this).data("action"));
});

$(document).on("click", "#quick-view .action-add-to-cart", function () {
    addToCart();
});

$(document).on("change", "#quick-view .action-color-change", function () {
    $("#add-to-cart-form .color-ball").removeClass("border-add");
    $("#label-" + this.id).addClass("border-add");
    slideQuickViewToColor($(this).val());
});

$(document).on("click", "#quick-view .btn-number", function (e) {
    e.preventDefault();
    let fieldName = $(this).attr("data-field");
    let type = $(this).attr("data-type");
    let input = $("#add-to-cart-form input[name='" + fieldName + "']");
    let currentVal = parseInt(input.val());

    if (!isNaN(currentVal)) {
        if (type == "minus") {
            if (currentVal > input.attr("min")) {
                input.val(currentVal - 1).change();
            }
            if (parseInt(input.val()) == input.attr("min")) {
                $(this).attr("disabled", true);
            }
        } else if (type == "plus") {
            if (currentVal < input.attr("max")) {
                input.val(currentVal + 1).change();
            }
            if (parseInt(input.val()) == input.attr("max")) {
                $(this).attr("disabled", true);
            }
        }
    } else {
        input.val(0);
    }
});

$(document).on("focusin", "#quick-view .input-number", function () {
    $(this).data("oldValue", $(this).val());
});

$(document).on("change", "#quick-view .input-number", function () {
    sanitizeAndValidateQuantityInput(this);
    let minValue = parseInt($(this).attr("min"));
    let maxValue = parseInt($(this).attr("max"));
    let valueCurrent = parseInt($(this).val());
    let name = $(this).attr("name");
    if (valueCurrent >= minValue) {
        $(
            ".btn-number[data-type='minus'][data-field='" + name + "']"
        ).removeAttr("disabled");
    } else {
        sanitizeAndValidateQuantityInput(this);
        $(this).val($(this).data("oldValue"));
    }
    if (valueCurrent <= maxValue) {
        $(
            ".btn-number[data-type='plus'][data-field='" + name + "']"
        ).removeAttr("disabled");
    } else {
        $(this).val($(this).data("oldValue"));
    }
});

$(document).on("change", "#quick-view .cart-qty-field", function () {
    sanitizeAndValidateQuantityInput(this);
});

$(document).on("keydown", "#quick-view .input-number", function (e) {
    if (
        $.inArray(e.keyCode, [46, 8, 9, 27, 13, 190]) !== -1 ||
        (e.keyCode == 65 && e.ctrlKey === true) ||
        (e.keyCode >= 35 && e.keyCode <= 39)
    ) {
        return;
    }
    if (
        (e.shiftKey || e.keyCode < 48 || e.keyCode > 57) &&
        (e.keyCode < 96 || e.keyCode > 105)
    ) {
        e.preventDefault();
    }

    sanitizeAndValidateQuantityInput(this);
});

$(document).on("change", "#quick-view .variant-change input", function () {
    $("#add-to-cart-form input[name=quantity]").val(1);
    $(".cart-qty-field").val(1);
    $(".in-cart-quantity-field").val("");
});

$(document).on("change", "#quick-view .variant-change input, #quick-view .cart-qty-field", function () {
    getVariantPrice();
});

$(document).on("change", "#add-to-cart-form .in-cart-quantity-field", function () {
    getVariantPrice("already_in_cart");
});

$(document).on("focusin", "#quick-view .cart-qty-field, #quick-view .in-cart-quantity-field", function () {
    $(this).closest(".product-quantity-group").addClass("border-primary");
});

$(document).on("focusout", "#quick-view .cart-qty-field, #quick-view .in-cart-quantity-field", function () {
    $(this).closest(".product-quantity-group").removeClass("border-primary");
});

$(document).on("click", ".close-alert-message", function () {
    $(".pos-alert-message").addClass("d-none");
});

function startPlaceOrder() {
    if (isPlacingOrder || $("#submit_order").prop("disabled") || !checkedPaidAmount()) {
        return;
    }
    Swal.fire({
        title: messageAreYouSure,
        icon: "warning",
        text: $("#submit_order").data("message"),
        showCancelButton: true,
        showConfirmButton: true,
        confirmButtonColor: "#1455AC",
        cancelButtonColor: "#d33",
        cancelButtonText: getNoWord,
        confirmButtonText: getYesWord,
        reverseButtons: true,
    }).then(function (result) {
        if (result.value) {
            placeOrder();
        }
    });
}

function setPlacingOrder(isPlacing) {
    isPlacingOrder = isPlacing;
    if (isPlacing) {
        $("#submit_order").prop("disabled", true);
    } else {
        renderCustomerAmountForPay();
    }
}

function placeOrder() {
    if (isPlacingOrder) {
        return;
    }
    setPlacingOrder(true);
    $.post({
        url: $("#order-place").attr("action"),
        data: new FormData(document.getElementById("order-place")),
        contentType: false,
        processData: false,
        beforeSend: function () {
            $("#loading").fadeIn();
        },
        success: function (response) {
            if (response?.status === "error") {
                toastMagic.error(response.message ?? posMessage("something-went-wrong"));
                setPlacingOrder(false);
            } else if (Boolean(response.checkProductTypeForWalkingCustomer) === true) {
                setPlacingOrder(false);
                $(".alert--message-for-pos").addClass("active");
                $(".alert--message-for-pos .warning-message").empty().html(response.message);
                showPosOffcanvas("offcanvasAddNewCustomer");
                setTimeout(() => {
                    $('.alert--message-for-pos').removeClass('show').removeClass('active');
                }, 5000)
            } else {
                location.reload();
            }
        },
        error: function () {
            setPlacingOrder(false);
        },
        complete: function () {
            $("#loading").fadeOut();
        },
    });
}

$(document).on("click", ".action-form-submit", startPlaceOrder);

$(document).on("submit", "#order-place", function (event) {
    event.preventDefault();
});

$(document).on("change", ".option-buttons input", function () {
    renderCustomerAmountForPay();
    let type = $(this).val();
    if ($(this).is(":checked")) {
        $(".cash-change-section").hide();
        if (type === "cash") {
            $(".cash-change-amount").show();
        } else if (type === "card") {
            $(".cash-change-card").removeClass("d-none").show();
        } else if (type === "wallet") {
            let insufficientBalanceMessage = $(
                "#message-insufficient-balance"
            );
            let cashChangeWallet = $(".cash-change-wallet");
            if (
                parseFloat($(".customer-wallet-balance").val()) <
                parseFloat($(".total-amount").val())
            ) {
                insufficientBalanceMessage.text(
                    insufficientBalanceMessage.data("text")
                );
            }
            cashChangeWallet.show();
            cashChangeWallet.removeClass("d-none").show();
        }
    }
});

$(document).on("keypress", ".pos-paid-amount-element", function (event) {
    let charCode = event.which || event.keyCode;
    let inputValue = $(this).val();

    if ((charCode < 48 || charCode > 57) && charCode !== 46) {
        event.preventDefault();
    }

    if (charCode === 46 && inputValue.includes(".")) {
        event.preventDefault();
    }
});

$(document).on("input", ".pos-paid-amount-element", function () {
    let minimumAmount = parseFloat($(this).attr("min")) || 0;
    let GivenAmount = parseFloat($(this).val()) || 0;
    let currencyPosition = $(this).data("currency-position");
    let currencySymbol = $(this).data("currency-symbol");
    let decimalPoint = $('#get-decimal-point').data('decimal-point') ?? 2;

    $("#submit_order").prop("disabled", isPlacingOrder || GivenAmount < minimumAmount);

    let amount = Number(GivenAmount - minimumAmount).toFixed(decimalPoint);
    let result = "";

    if (currencyPosition?.toString() === "left") {
        result = currencySymbol + amount;
    } else {
        result = amount + currencySymbol;
    }

    $(".pos-change-amount-element").text(result);
});

function checkedPaidAmount() {
    let paidAmount = $(".pos-paid-amount-element");
    if ($(".paid-by-cash").prop("checked") && paidAmount.val() === "") {
        toastMagic.error(posMessage("enter-valid-amount"));
        return false;
    } else if (
        $(".paid-by-cash").prop("checked") &&
        parseFloat(paidAmount.val()) < parseFloat(paidAmount.attr("min"))
    ) {
        toastMagic.error(posMessage("less-than-total-amount"));
        return false;
    }
    return true;
}

$(".action-coupon-discount").on("click", function (event) {
    let couponCode = $("#coupon_code").val();
    if (couponCode.length === 0) {
        toastMagic.error($(this).data("error-message"));
        event.preventDefault();
    } else {
        $.post({
            url: $("#route-admin-pos-coupon-discount").data("url"),
            data: {
                coupon_code: couponCode,
            },
            beforeSend: function () {
                $("#loading").fadeIn();
            },
            success: function (data) {
                if (data.coupon === "success") {
                    toastMagic.success(
                        posMessage("coupon-added-successfully"), '',
                        {
                            CloseButton: true,
                            ProgressBar: true,
                        }
                    );
                } else if (data.coupon === "amount_low") {
                    toastMagic.warning(posMessage("this-discount-is-not-applied-for-this-amount"));
                } else if (data.cart === "empty" || data.coupon === "min_purchase") {
                    toastMagic.warning(data.message);
                } else {
                    toastMagic.warning(posMessage("coupon-is-invalid"));
                }
                $("#add-coupon-discount").modal("hide");
                renderCartSummary(data.view);
                $("#search").focus();
            },
            complete: function () {
                $(".modal-backdrop").addClass("d-none");
                $(".footer-offset").removeClass("modal-open");
                $("#loading").fadeOut();
            },
        });
    }
});

$(document).on("click", "#pos-extra-discount-remove-admin", function (event) {
    event.preventDefault();
    $.post({
        url: $("#route-admin-pos-remove-discount").data("url"),
        beforeSend: function () {
            $("#loading").fadeIn();
        },
        success: function (data) {
            $("#add-discount").modal("hide");
            $(".modal-backdrop").addClass("d-none");
            renderCartSummary(data.view);
        },
        complete: function () {
            $(".modal-backdrop").addClass("d-none");
            $(".footer-offset").removeClass("modal-open");
            $("#loading").fadeOut();
        }
    });
});

$(document).on("show.bs.modal", "#add-discount", function () {
    const discountButton = $("#cart-summary #extra_discount");
    const discountType = discountButton.data("extra-discount-type") || "amount";
    const discountValue = parseFloat(discountButton.data("extra-discount-value")) || 0;
    $("#type_ext_dis").val(discountType).trigger("change");
    $("#dis_amount").val(discountValue > 0 ? discountValue : "");
});

$(document).on("change", "#type_ext_dis", function () {
    let placeholder = $(this).find("option:selected").data("placeholder");
    if (placeholder) {
        $("#dis_amount").attr("placeholder", placeholder);
    }
});

$(".action-extra-discount").on("click", function (event) {
    let discount = $("#dis_amount").val();
    let type = $("#type_ext_dis").val();
    if (discount.length === 0) {
        toastMagic.error($(this).data("error-message"));
        event.preventDefault();
    } else if (discount > 0) {
        $.post({
            url: $("#route-admin-pos-update-discount").data("url"),
            data: {
                discount: discount,
                type: type,
            },
            beforeSend: function () {
                $("#loading").fadeIn();
            },
            success: function (data) {
                if (data.extraDiscount === "success") {
                    toastMagic.success(
                        posMessage("extra-discount-added-successfully"), '',
                        {
                            CloseButton: true,
                            ProgressBar: true,
                        }
                    );
                } else if ((data.cart === "empty")) {
                    toastMagic.warning(
                        data.message, '',
                        {
                            CloseButton: true,
                            ProgressBar: true,
                        }
                    );
                } else {
                    toastMagic.warning(posMessage("this-discount-is-not-applied-for-this-amount"));
                }
                $("#add-discount").modal("hide");
                $(".modal-backdrop").addClass("d-none");
                renderCartSummary(data.view);
                $("#search").focus();
            },
            complete: function () {
                $(".modal-backdrop").addClass("d-none");
                $(".footer-offset").removeClass("modal-open");
                $("#loading").fadeOut();
            },
        });
    } else {
        toastMagic.warning(posMessage("amount-can-not-be-negative-or-zero"));
    }
});

$(document).on("focusin", ".action-pos-update-quantity", function () {
    $(this).select();
});

$(document).on("change", ".action-pos-update-quantity", function (event) {
    if (!event.originalEvent) {
        return;
    }
    let quantity = $(this).val();
    getPOSUpdateQuantity(
        $(this).data("product-key"),
        quantity !== "" ? quantity : parseInt($(this).attr("min")),
        $(this).data("product-variant")
    );
});

$(document).on("click", ".qty-count", function () {
    let $btn = $(this);
    let $group = $btn.closest(".qty-input-group");
    let $input = $group.find(".product-qty");
    let oldValue = parseInt($input.val()) || 1;
    let action = $btn.data("action");
    let min = parseInt($input.attr("min")) || 1;
    let max = getCartQuantityMax($input);
    let newValue = oldValue;

    if (action === "minus") {
        if (oldValue <= min) {
            toastMagic.warning(posMessage("product-quantity-cannot-be-less-then-one"));
            return;
        } else {
            newValue = oldValue - 1;
        }
    } else if (action === "plus" && oldValue < max) {
        newValue = oldValue + 1;
    }

    $input.val(newValue).trigger({ type: "change", originalEvent: true });

    $group.find('[data-action="minus"]').prop("disabled", newValue <= min);
    $group.find('[data-action="plus"]').prop("disabled", newValue >= max);
});

function getCartQuantityMax(input) {
    const max = parseInt($(input).attr("max"));
    return Number.isNaN(max) ? cartQuantityMaxFallback : max;
}

function syncCartQuantityButtons() {
    $(".qty-input-group").each(function () {
        let $group = $(this);
        let $input = $group.find(".product-qty");
        let value = parseInt($input.val()) || 1;
        let min = parseInt($input.attr("min")) || 1;
        let max = getCartQuantityMax($input);

        $group.find('[data-action="minus"]').prop("disabled", value <= min);
        $group.find('[data-action="plus"]').prop("disabled", value >= max);
    });
}

document.addEventListener('input', function (event) {
    if (event.target.classList.contains('action-pos-update-quantity')) {
        sanitizeAndValidateQuantityInput(event.target);
    }
});

function getPOSUpdateQuantity(key, qty, variant = null) {
    $.post({
        url: $("#route-admin-pos-update-quantity").data("url"),
        data: {
            _token: $('meta[name="_token"]').attr("content"),
            key: key,
            quantity: qty,
            variant: variant,
        },
        success: function (data) {
            updateQuantityResponseProcess(data);
        },
    });
}

function syncCartSummaryState() {
    syncCartQuantityButtons();
    syncMobileCartBar();
    $(".option-buttons input:checked").trigger("change");
}

function renderCartSummary(view) {
    const cartScrollTop = $("#cart-summary .pos-cart-scroll").scrollTop() || 0;
    $("#cart-summary").empty().html(view);
    $("#cart-summary .pos-cart-scroll").scrollTop(cartScrollTop);
    syncCartSummaryState();
}

let isBillingPanelInView = false;

function syncMobileCartBar() {
    $(".pos-mobile-cart-bar").toggleClass("is-hidden", isBillingPanelInView);
}

function initMobileCartBar() {
    const billingPanel = document.querySelector(".pos-col-billing");
    if (!billingPanel) {
        return;
    }
    new IntersectionObserver((entries) => {
        isBillingPanelInView = entries.some((entry) => entry.isIntersecting) && mobileLayoutMedia.matches;
        syncMobileCartBar();
    }, { threshold: 0.15 }).observe(billingPanel);
}

$(document).on("click", ".action-scroll-to-billing", function () {
    document.querySelector(".pos-col-billing")?.scrollIntoView({ behavior: "smooth", block: "start" });
});

function syncSearchPlaceholder() {
    const searchInput = $("#search");
    const placeholder = searchInput.data(compactSearchMedia.matches ? "short-placeholder" : "full-placeholder");
    if (placeholder) {
        searchInput.attr("placeholder", placeholder);
    }
}

compactSearchMedia.addEventListener("change", syncSearchPlaceholder);

function updateQuantityResponseProcess(data) {
    if (data.productType === "physical" && data.qty < 0) {
        toastMagic.warning(posMessage("product-quantity-is-not-enough"));
    }
    if (data.quantityUpdate == 1) {
        toastMagic.success(
            posMessage("product-quantity-updated"), '',
            {
                CloseButton: true,
                ProgressBar: true,
            }
        );
    }
    renderCartSummary(data.view);
    updateProductCounts(data);
    toastAdjustmentNotices(data);
}

function toastAdjustmentNotices(data) {
    (data?.adjustmentNotices ?? []).forEach(function (notice, index) {
        setTimeout(function () {
            toastMagic.warning(notice);
        }, index * 500);
    });
}

$(document).on("click", "input[type=number]", function () {
    this.select();
});

window.addEventListener("click", function (event) {
    let searchResultBoxes =
        document.getElementsByClassName("search-result-box");
    for (let i = 0; i < searchResultBoxes.length; i++) {
        let searchResultBox = searchResultBoxes[i];
        if (
            event.target !== searchResultBox &&
            !searchResultBox.contains(event.target)
        ) {
            searchResultBox.style.display = "none";
        }
    }
});

$(".action-print-pos-invoice").on("click", function () {
    const divName = $(this).data("print");
    printSpecificSectionWithPrintArea(divName);
});

function printSpecificSectionWithPrintArea(selector) {
    try {
        $(selector).printThis();
    } catch (e) {
        console.warn("Printing failed:", e);
    }
}

const renderRippleEffect = () => {
    function createRipple(event) {
        const button = event.currentTarget;
        const circle = document.createElement("span");
        const diameter = Math.max(button.clientWidth, button.clientHeight);
        const radius = diameter / 2;
        circle.style.width = circle.style.height = `${diameter}px`;
        circle.classList.add("ripple");
        const ripple = button.getElementsByClassName("ripple")[0];
        if (ripple) {
            ripple.remove();
        }
        button.appendChild(circle);
    }
    const buttons = document.querySelectorAll("#quick-view .btn-number");
    for (const button of buttons) {
        button.addEventListener("click", createRipple);
    }
};

function quickView(product_id) {
    $.ajax({
        url: $("#route-admin-pos-quick-view").data("url"),
        type: "GET",
        data: {
            product_id: product_id,
        },
        dataType: "json",
        beforeSend: function () {
            $("#loading").fadeIn();
        },
        success: function (data) {
            $("#quick-view-modal").empty().html(data.view);
            $('#quick-view-modal [data-bs-toggle="tooltip"]').each(function () {
                new bootstrap.Tooltip(this, { container: 'body' });
            });
            getVariantPrice();
            renderRippleEffect();
            $("#quick-view").modal("show");
        },
        complete: function () {
            $("#loading").fadeOut();
        },
    });
}

function toggleQuickViewDescriptionControl() {
    const $body = $("#quick-view .pos-qv__description-body");
    if ($body.length) {
        $("#quick-view .pos-qv__description-toggle").toggleClass("d-none", $body[0].scrollHeight <= $body[0].clientHeight);
    }
}

$(document).on("shown.bs.modal", "#quick-view", function () {
    initSliderWithZoom();
    slideQuickViewToColor($("#quick-view .action-color-change:checked").val(), 0);
    toggleQuickViewDescriptionControl();
});

function slideQuickViewToColor(colorCode, speed) {
    if (!quickViewMainSwiper || !colorCode) {
        return;
    }
    const colorKey = String(colorCode).replace("#", "").toUpperCase();
    const slideIndex = $(quickViewMainSwiper.slides).toArray().findIndex(function (slide) {
        return String($(slide).data("color") ?? "").toUpperCase() === colorKey;
    });
    if (slideIndex >= 0) {
        quickViewMainSwiper.slideTo(slideIndex, speed);
    }
}

$(document).on("hidden.bs.modal", "#quick-view", destroySliderWithZoom);

$(document).on("hidden.bs.modal", "#quick-view", function () {
    $('#quick-view-modal [data-bs-toggle="tooltip"]').each(function () {
        bootstrap.Tooltip.getInstance(this)?.dispose();
    });
});

$(document).on("click", "#quick-view .pos-qv__description-toggle", function () {
    const $body = $(this).siblings(".pos-qv__description-body");
    const isExpanded = $body.toggleClass("is-expanded").hasClass("is-expanded");
    $(this).text($(this).data(isExpanded ? "less-label" : "more-label"));
});

function getVariantForAlreadyInCart(event = null) {
    let current_val = parseFloat($(".in-cart-quantity-field").val());
    if (current_val > 0) {
        $(".in-cart-quantity-minus").removeAttr("disabled");
        if (event == "plus") {
            $(".in-cart-quantity-field").val(current_val + 1);
        } else {
            $(".in-cart-quantity-field").val(current_val - 1);
            if (current_val <= 2) {
                $(".in-cart-quantity-minus").attr("disabled", true);
            }
        }
    } else {
        $(".in-cart-quantity-minus").attr("disabled", true);
    }
    getVariantPrice("already_in_cart");
}

function checkAddToCartValidity() {
    var names = {};
    $("#add-to-cart-form input:radio").each(function () {
        names[$(this).attr("name")] = true;
    });
    var count = Object.keys(names).length;
    if (count === 0) {
        return true;
    }
    var checkedCount = 0;
    $.each(names, function (name) {
        if ($("input:radio[name='" + name + "']:checked").length > 0) {
            checkedCount++;
        }
    });
    return checkedCount === count;
}

function sanitizeAndValidateQuantityInput(inputElement) {
    inputElement.value = inputElement.value.replace(/[^0-9]/g, '').replace(/^0+/, '');
    const min = parseInt(inputElement.getAttribute("min")) || 1;
    const max = getCartQuantityMax(inputElement);
    const val = parseInt(inputElement.value);

    if (inputElement.value !== '' && (val < min || val > max)) {
        inputElement.value = min;
    }
}

function updateProductDetailsTopSection(response) {
    let formSelector = ".add-to-cart-details-form";
    $(formSelector)
        .find(".discounted-unit-price")
        .html(response?.discounted_unit_price);
    $(formSelector)
        .find(".product-details-chosen-price-amount")
        .text(response.price);

    $(formSelector)
        .find(".product-total-unit-price")
        .html(response?.discount_amount > 0 ? response?.total_unit_price : "");

    $(formSelector)
        .find(".discounted_badge")
        .text(response?.discount_amount > 0 ? response?.discount_text : "");
    toggleSetDiscountWrapper();
}

function setAddToCartLabel(labelKey) {
    const $button = $(".quick-view-modal-add-cart-button");
    $button.find(".quick-view-add-cart-label").text($button.data(labelKey));
}

function closePosQuickView() {
    const element = document.getElementById("quick-view");
    if (element) {
        bootstrap.Modal.getOrCreateInstance(element).hide();
    }
}

let variantPriceRequestSequence = 0;
let variantPriceRequest = null;

function getVariantPrice(type = null) {
    if (
        $("#add-to-cart-form input[name=quantity]").val() > 0 &&
        checkAddToCartValidity()
    ) {
        if (variantPriceRequest) {
            variantPriceRequest.abort();
        }
        const requestSequence = ++variantPriceRequestSequence;
        variantPriceRequest = $.ajax({
            type: "POST",
            url:
                $("#route-admin-pos-get-variant-price").data("url") +
                (type ? "?type=" + type : ""),
            data: $("#add-to-cart-form").serializeArray(),
            success: function (response) {
                if (requestSequence !== variantPriceRequestSequence) {
                    return;
                }
                if (response.quantity !== undefined) {
                    $('input[name="quantity"]').attr('max', response.quantity);
                    $('input[name="product-qty"]').attr('max', response.quantity);
                    $(".current-stock-qty").text(response.quantity);
                }
                updateProductDetailsTopSection(response);
                if (!response.isSelectionAvailable) {
                    $(".current-stock-qty").text(response.selectedStock);
                    setStockStatusChip(0);
                    setAddToCartLabel("out-of-stock-label");
                    $(".quick-view-modal-add-cart-button").prop("disabled", true);
                    $(".default-quantity-system").addClass("d-none");
                    $(".in-cart-quantity-system").addClass("d-none");
                    return;
                }
                $(".quick-view-modal-add-cart-button").prop("disabled", false);
                if (response.inCartStatus == 0) {
                    setAddToCartLabel("add-label");
                    $(".in-cart-quantity-system").addClass("d-none");
                    $(".default-quantity-system").removeClass("d-none");
                    stockStatus(
                        response.quantity,
                        "cart-qty-field-plus",
                        "cart-qty-field"
                    );
                } else {
                    $(".default-quantity-system").addClass("d-none");
                    $(".in-cart-quantity-system").removeClass("d-none");
                    setAddToCartLabel("update-label");
                    if (type == null) {
                        $(".in-cart-quantity-field").val(
                            response.inCartData.quantity
                        );
                        response.inCartData.quantity == 1
                            ? buttonDisableOrEnableFunction(
                                "in-cart-quantity-minus",
                                true
                            )
                            : "";
                    } else {
                        $(".in-cart-quantity-field").val(response.requestQuantity || 1);
                    }
                    stockStatus(
                        response.quantity,
                        "in-cart-quantity-plus",
                        "in-cart-quantity-field"
                    );
                }
                setProductData(
                    "add-to-cart-details-form",
                    response.price,
                    response.discount_text
                );
            },
            complete: function () {
                if (requestSequence === variantPriceRequestSequence) {
                    variantPriceRequest = null;
                }
            },
        });
    }
}

function updateProductCounts(data) {
    $(".product-add-count").addClass("d-none").text("");
    if (data.productCounts) {
        $.each(data.productCounts, function (productId, count) {
            const countElement = $("#product-added-to-cart-" + productId);
            if (countElement.length) {
                countElement.text(count).removeClass("d-none");
            }
        });
    }
}

function addToCart(form_id = "add-to-cart-form") {
    if (checkAddToCartValidity()) {
        $.post({
            url: $("#route-admin-pos-add-to-cart").data("url"),
            data: $("#" + form_id).serializeArray(),
            beforeSend: function () {
                $("#loading").fadeIn();
            },
            success: function (data) {
                if (data.data == 1) {
                    renderCartSummary(data.view);
                    toastMagic.success(
                        posMessage("cart-updated"), '',
                        {
                            CloseButton: true,
                            ProgressBar: true,
                        }
                    );
                    data.inCartData && data.inCartData == 1
                        ? $(".in-cart-quantity-field").val(data.requestQuantity)
                        : "";
                    updateProductCounts(data);
                    closePosQuickView();
                    toastAdjustmentNotices(data);
                    return false;
                } else if (data.data == 0) {
                    $(".product-stock-message")
                        .empty()
                        .html(
                            $("#get-product-stock-message").data("out-of-stock")
                        );
                    $(".pos-alert-message").removeClass("d-none");
                    updateProductCounts(data);
                    return false;
                } else if (data.data == 'custom-error') {
                    Swal.fire({
                        icon: "error",
                        title: data?.title ?? posMessage("cart-word"),
                        text: data?.text ?? posMessage("sorry-product-is-out-of-stock"),
                    });
                    updateProductCounts(data);
                    return false;
                } else {
                    $(".in-cart-quantity-field").val(data.quantity);
                    getVariantPrice();
                    setTimeout(function () {
                        $(".cart-qty-field").val(1);
                    }, 500);
                }
                closePosQuickView();

                toastMagic.success(
                    posMessage("item-has-been-added-in-your-cart"), '',
                    {
                        CloseButton: true,
                        ProgressBar: true,
                    }
                );
                renderCartSummary(data.view);
                updateProductCounts(data);
                abortProductSearch();
                $(".pos-search").removeClass("is-searching");
                $(".search-result-box").empty().hide();
                $("#search").val("");
                toastAdjustmentNotices(data);
            },
            complete: function () {
                $("#loading").fadeOut();
            },
        });
    } else {
        Swal.fire({
            type: "info",
            title: posMessage("cart-word"),
            text: posMessage("please-choose-all-the-options"),
        });
    }
}

$(document).on("click", ".remove-from-cart", function () {
    $.post(
        $("#route-admin-pos-remove-cart").data("url"),
        {
            _token: $('meta[name="_token"]').attr("content"),
            id: $(this).data("id"),
            variant: $(this).data("variant"),
        },
        function (data) {
            renderCartSummary(data.view);
            if (data.errors) {
                for (let index = 0; index < data.errors.length; index++) {
                    setTimeout(() => {
                        toastMagic.error(data.errors[index].message);
                    }, index * 500);
                }
            } else {
                toastMagic.info(posMessage("item-has-been-removed-from-cart"));
                toastAdjustmentNotices(data);
            }
            updateProductCounts(data);
        }
    );
});

function productStockMessage(type) {
    $(".product-stock-message")
        .empty()
        .html($("#get-product-stock-message").data(type));
    $(".pos-alert-message").removeClass("d-none");
}

function setStockStatusChip(quantity) {
    let isInStock = quantity > 0;

    $(".stock-status-in-quick-view")
        .toggleClass("text-success", isInStock)
        .toggleClass("text-danger", !isInStock)
        .text(
            isInStock
                ? posMessage("stock-id")
                : posMessage("stock-out")
        );
}

function stockStatus(quantity, buttonDisableOrEnableClassName, inputQuantityClassName) {
    let inputQuantity = $("." + inputQuantityClassName);

    setStockStatusChip(quantity);

    if (quantity <= 0) {
        productStockMessage("out-of-stock");
        buttonDisableOrEnableFunction(buttonDisableOrEnableClassName, true);
        inputQuantity.val(1);
        $(".btn-number[data-type='minus']").attr("disabled", true);
    } else if (inputQuantity.val() >= quantity) {
        productStockMessage("limited-stock");
        buttonDisableOrEnableFunction(buttonDisableOrEnableClassName, true);
        inputQuantity.val(quantity);
    } else {
        $(".pos-alert-message").addClass("d-none");
        buttonDisableOrEnableFunction(buttonDisableOrEnableClassName, false);
        $(".btn-number[data-type='minus']").attr("disabled", false);
    }
}

function toggleSetDiscountWrapper() {
    if ($('.set-discount-amount').text().trim().length > 0) {
        $('.set-discount-wrapper').show();
        $('.set-discount-wrapper').addClass('d-flex').removeClass('d-none');
    } else {
        $('.set-discount-wrapper').removeClass('d-flex').addClass('d-none');
        $('.set-discount-wrapper').hide();
    }
}

function setProductData(parentClass, price, discount) {
    $("." + parentClass + " " + ".set-discount-amount").html(discount);
    toggleSetDiscountWrapper();
}

$(".close-alert--message-for-pos").on("click", function () {
    $(".alert--message-for-pos").removeClass("active");
});

let quickViewMainSwiper = null;
let quickViewThumbSwiper = null;

function initSliderWithZoom() {
    $(".easyzoom").each(function () {
        $(this).easyZoom();
    });

    quickViewThumbSwiper = new Swiper(".quickviewSliderThumb2", {
        spaceBetween: 10,
        slidesPerView: "auto",
        watchSlidesProgress: true,
        watchOverflow: true,
        navigation: {
            nextEl: ".swiper-quickview-button-next",
            prevEl: ".swiper-quickview-button-prev",
        },
    });

    quickViewMainSwiper = new Swiper(".quickviewSlider2", {
        slidesPerView: 1,
        spaceBetween: 10,
        loop: false,
        thumbs: {
            swiper: quickViewThumbSwiper,
        },
    });
}

function destroySliderWithZoom() {
    if (quickViewMainSwiper) {
        quickViewMainSwiper.destroy(true, true);
        quickViewMainSwiper = null;
    }
    if (quickViewThumbSwiper) {
        quickViewThumbSwiper.destroy(true, true);
        quickViewThumbSwiper = null;
    }
}

function initPosInfiniteScroll() {
    const grid = document.getElementById("pos-product-scroll");
    const sentinel = grid?.querySelector(".pos-scroll-sentinel");
    if (!grid || !sentinel) {
        return;
    }

    let isLoading = false;

    const stopObserving = () => {
        observer.disconnect();
        sentinel.remove();
    };

    const loadNextPage = () => {
        if (isLoading || grid.dataset.hasMore !== "1") {
            return;
        }
        isLoading = true;
        grid.classList.add("is-loading");

        const params = new URLSearchParams(window.location.search);
        params.set("page", grid.dataset.nextPage);

        $.get({
            url: grid.dataset.url + "?" + params.toString(),
            dataType: "json",
            success: function (data) {
                $(sentinel).before($($.parseHTML(data.view)));
                grid.dataset.nextPage = String(parseInt(grid.dataset.nextPage) + 1);
                grid.dataset.hasMore = data.hasMorePages ? "1" : "0";
                updateProductCounts(data);
            },
            complete: function () {
                isLoading = false;
                grid.classList.remove("is-loading");
                if (grid.dataset.hasMore !== "1") {
                    stopObserving();
                }
            },
        });
    };

    const observer = new IntersectionObserver(
        (entries) => {
            if (entries.some((entry) => entry.isIntersecting)) {
                loadNextPage();
            }
        },
        { root: grid, rootMargin: "400px" }
    );

    if (grid.dataset.hasMore === "1") {
        observer.observe(sentinel);
    } else {
        sentinel.remove();
    }
}

toggleSetDiscountWrapper();
syncCartSummaryState();
syncSearchPlaceholder();
initMobileCartBar();
initPosInfiniteScroll();
