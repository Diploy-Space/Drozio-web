function updateSavedAuctionNavCount(count) {
    if (count === undefined || count === null) return;

    const total = Math.max(0, parseInt(count) || 0);

    $(".auction-saved-update-count").each(function () {
        $(this).text(total > 99 ? "99+" : total).toggleClass("is-zero", total === 0);
    });
}

function _auctionParseDate(dateString) {
    if (!dateString) return null;

    let date = new Date(dateString);
    if (!isNaN(date)) return date;

    date = new Date(dateString.replace(' ', 'T'));
    if (!isNaN(date)) return date;

    const parts = dateString.split(/[- :]/);
    return new Date(
        parts[0],
        parts[1] - 1,
        parts[2],
        parts[3] || 0,
        parts[4] || 0,
        parts[5] || 0
    );
}

const auctionReloadDelayAfterCountdownEnds = 2000;
let isAuctionReloadScheduled = false;

function reloadWhenAuctionCountdownEnds() {
    if (isAuctionReloadScheduled) return;
    isAuctionReloadScheduled = true;
    setTimeout(() => window.location.reload(), auctionReloadDelayAfterCountdownEnds);
}

window.initAuctionCountdowns = function (root) {
    const scope = root || document;
    const countdowns = scope.querySelectorAll('.countdown:not([data-countdown-init])');

    countdowns.forEach(function (countdown) {
        countdown.setAttribute('data-countdown-init', '1');

        const rawDate = countdown.dataset.end;
        const parsedDate = _auctionParseDate(rawDate);

        if (!parsedDate) return;

        const endTime = parsedDate.getTime();

        const container = countdown.closest(".m-thumbnail-wrap");
        const statusText = container?.querySelector(".status-text") ||
            countdown.closest('[class]')?.parentElement?.querySelector(".status-text");

        const defaultText = statusText?.textContent || "Closes In";
        const endText = statusText?.dataset?.timeendText || "Closed";

        const hoursEl = countdown.querySelector(".hours");
        const minutesEl = countdown.querySelector(".minutes");
        const secondsEl = countdown.querySelector(".seconds");

        if (!hoursEl || !minutesEl || !secondsEl) return;

        function applyUrgency(distance) {
            const hoursLeft = distance / (1000 * 60 * 60);

            countdown.classList.toggle("auction-countdown--ended", distance <= 0);
            countdown.classList.toggle("auction-countdown--urgent", distance > 0 && hoursLeft <= 1);
            countdown.classList.toggle("auction-countdown--soon", hoursLeft > 1 && hoursLeft <= 24);
            countdown.classList.toggle("auction-countdown--hide-hours", distance > 0 && hoursLeft < 1);
        }

        function updateTimer() {
            const now = Date.now();
            const distance = endTime - now;

            applyUrgency(distance);

            if (distance <= 0) {
                hoursEl.textContent = "00";
                minutesEl.textContent = "00";
                secondsEl.textContent = "00";

                if (statusText) {
                    statusText.textContent = endText;
                }

                return true;
            }

            if (statusText) {
                statusText.textContent = defaultText;
            }

            const hours = Math.floor(distance / (1000 * 60 * 60));
            const minutes = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
            const seconds = Math.floor((distance % (1000 * 60)) / 1000);

            hoursEl.textContent = String(hours).padStart(2, '0');
            minutesEl.textContent = String(minutes).padStart(2, '0');
            secondsEl.textContent = String(seconds).padStart(2, '0');

            return false;
        }

        const finished = updateTimer();
        if (finished) return;

        const interval = setInterval(() => {
            const done = updateTimer();
            if (done) {
                clearInterval(interval);
                reloadWhenAuctionCountdownEnds();
            }
        }, 1000);
    });
};

document.addEventListener("DOMContentLoaded", function () {

    var backgroundImageInit = $("[data-bg-img]");
    backgroundImageInit
        .css("background-image", function () {
            return 'url("' + $(this).data("bg-img") + '")';
        })
        .removeAttr("data-bg-img")
        .addClass("bg-img");

    window.initAuctionCountdowns();
});

$(document).ready(function () {
    try {
        var participationToast = sessionStorage.getItem('auction_participation_toast');
        if (participationToast !== null) {
            sessionStorage.removeItem('auction_participation_toast');
            if (participationToast && typeof toastMagic !== 'undefined') {
                toastMagic.success(participationToast);
            }
        }
    } catch (e) {}
});

$(document).on('click', '.auction-participate-btn', function () {
    let $btn = $(this);
    let originalHtml = $btn.html();

    $btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span>' + originalHtml);

    $.ajaxSetup({
        headers: {
            "X-CSRF-TOKEN": $('meta[name="_token"]').attr("content"),
        },
    });

    $.ajax({
        url: $btn.data('url'),
        method: "POST",
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        },
        data: {
            id: $btn.data('auction-product-id'),
        },
        success: function (response) {

            if (response?.status?.toString() === '1') {
                $btn.prop('disabled', false).html(originalHtml);
                try {
                    sessionStorage.setItem(
                        'auction_participation_toast',
                        response?.participation_confirmed_message || response?.message || ''
                    );
                } catch (e) {}
                const detailsUrl = $btn.data('details-url');
                if (detailsUrl) {
                    window.location.href = detailsUrl;
                } else {
                    location.reload();
                }
            } else if (response?.status?.toString() === '0' && response?.payment_modal_show?.toString() === 'true') {
                const detailsUrl = $btn.data('details-url');
                if (detailsUrl) {
                    window.location.href = detailsUrl + '?open_participate=1';
                } else {
                    $('#modal-participate-entry-info').modal('show');
                }
                $btn.prop('disabled', false).html(originalHtml);
            } else if (response?.status?.toString() === '0') {
                toastMagic.error(response.message);
                $btn.prop('disabled', false).html(originalHtml);
            }

        },
        error: function (xhr) {
            $btn.prop('disabled', false).html(originalHtml);
        }
    });
});

document.getElementById('auction-entry-fee-form')?.addEventListener('submit', function (e) {
    e.preventDefault();

    const submitBtn = document.getElementById('auction-entry-process-btn')
        || this.querySelector('button[type="submit"]');
    if (!submitBtn || submitBtn.disabled) return;

    const originalHtml = submitBtn.innerHTML;
    submitBtn.disabled = true;
    submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span>' + originalHtml;

    const restoreBtn = function () {
        submitBtn.disabled = false;
        submitBtn.innerHTML = originalHtml;
    };

    const hideModal = function (id) {
        const el = document.getElementById(id);
        if (el) {
            bootstrap.Modal.getInstance(el)?.hide();
        }
    };

    const token = document.querySelector('meta[name="_token"]')?.getAttribute('content') || '';

    fetch(this.action, {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': token,
            'X-Requested-With': 'XMLHttpRequest',
            'Accept': 'application/json',
        },
        body: new FormData(this),
    })
        .then(function (res) { return res.json(); })
        .then(function (response) {
            if (response?.status?.toString() === '1' && response?.redirect_link) {
                window.location.href = response.redirect_link;
            } else if (response?.status?.toString() === '1') {
                hideModal('modal-participate-entry-payment');
                hideModal('modal-participate-entry-info');
                toastMagic.success(response.message);
                setTimeout(function () { location.reload(); }, 1500);
            } else {
                toastMagic.error(response.message);
                restoreBtn();
            }
        })
        .catch(function () {
            toastMagic.error('Something went wrong. Please try again.');
            restoreBtn();
        });
});

if (typeof window.queueLocaleSwitchToast !== "function") {
    window.queueLocaleSwitchToast = function (message) {
        try {
            sessionStorage.setItem("locale-switch-toast", message);
        } catch (error) {
            toastMagic.success(message);
        }
    };

    $(function () {
        let queuedMessage = null;

        try {
            queuedMessage = sessionStorage.getItem("locale-switch-toast");
            sessionStorage.removeItem("locale-switch-toast");
        } catch (error) {
            queuedMessage = null;
        }

        if (queuedMessage && typeof toastMagic !== "undefined") {
            toastMagic.success(queuedMessage);
        }
    });
}

if (typeof window.beginLocalePreferenceSwitch !== "function") {
    let isLocalePreferenceSwitching = false;

    window.beginLocalePreferenceSwitch = function ($trigger) {
        if (isLocalePreferenceSwitching) {
            return false;
        }

        isLocalePreferenceSwitching = true;
        $trigger.addClass("is-switching").closest(".hm-panel").addClass("is-switching");

        return true;
    };
}

if (!$(document).data("currency-switch-bound")) {
    $(document).data("currency-switch-bound", true);

    $(".get-currency-change-function").on("click", function () {
        if (!window.beginLocalePreferenceSwitch($(this))) {
            return;
        }

        currencyChangeFunction($(this).data("code"));
    });
}

if (!$(document).data("language-switch-bound")) {
    $(document).data("language-switch-bound", true);

    $(".change-language").on("click", function () {
        if (!window.beginLocalePreferenceSwitch($(this))) {
            return;
        }

        $.ajaxSetup({
            headers: { "X-CSRF-TOKEN": $('meta[name="_token"]').attr("content") },
        });
        $.ajax({
            type: "POST",
            url: $(this).data("action"),
            data: { language_code: $(this).data("language-code") },
            success: function (data) {
                queueLocaleSwitchToast(data.message);
                location.reload();
            },
        });
    });
}

function currencyChangeFunction(currency_code) {
    $.ajaxSetup({
        headers: { "X-CSRF-TOKEN": $('meta[name="_token"]').attr("content") },
    });
    $.ajax({
        type: "POST",
        url: $("#route-currency-change").data("url"),
        data: { currency_code: currency_code },
        success: function (data) {
            queueLocaleSwitchToast(data.message);
            location.reload();
        },
    });
}

$(".action-input-no-index-event").on("click", function () {
    $(".input-no-index-sub-element").prop("checked", true);
});

(function () {
    const applyBtn = document.getElementById('apply-wallet-btn');
    const removeBtn = document.getElementById('remove-wallet-btn');
    const balanceSection = document.getElementById('wallet-balance-section');
    const appliedSection = document.getElementById('wallet-applied-section');
    const paidByWalletSection = document.getElementById('paid-by-wallet-section');
    const walletRadio = document.getElementById('payment_method_wallet');

    function getOtherPaymentRadios() {
        return document.querySelectorAll('#modal-participate-entry-payment input[name="payment_method"]:not([type="hidden"])');
    }

    function lockOtherMethods() {
        getOtherPaymentRadios().forEach(function (r) {
            r.checked = false;
            r.disabled = true;
        });
        const offlineCard = document.querySelector('.auction-pay-offline-card');
        if (offlineCard) offlineCard.classList.add('d-none');
        const methodField = document.getElementById('auction_payment_method_field');
        if (methodField) methodField.innerHTML = '';
        const processBtn = document.getElementById('auction-entry-process-btn');
        if (processBtn) processBtn.disabled = false;
    }

    function unlockOtherMethods() {
        getOtherPaymentRadios().forEach(function (r) {
            r.disabled = false;
        });
    }

    window.removeWalletIfApplied = function () {
        if (appliedSection && !appliedSection.classList.contains('d-none')) {
            if (removeBtn) removeBtn.click();
        }
    };

    if (applyBtn) {
        applyBtn.addEventListener('click', function () {
            balanceSection.classList.add('d-none');
            appliedSection.classList.remove('d-none');
            paidByWalletSection.classList.remove('d-none');
            if (walletRadio) walletRadio.checked = true;
            lockOtherMethods();
        });
    }

    if (removeBtn) {
        removeBtn.addEventListener('click', function () {
            appliedSection.classList.add('d-none');
            paidByWalletSection.classList.add('d-none');
            balanceSection.classList.remove('d-none');
            if (walletRadio) walletRadio.checked = false;
            unlockOtherMethods();
        });
    }
})();

(function () {
    const offlineRadio = document.getElementById('auction_pay_offline');
    const offlineCard = document.querySelector('.auction-pay-offline-card');
    const methodField = document.getElementById('auction_payment_method_field');
    const gatewayRadios = document.querySelectorAll('input[name="payment_method"]:not(#auction_pay_offline)');
    const processBtn = document.getElementById('auction-entry-process-btn');
    const entryPaymentModal = document.getElementById('modal-participate-entry-payment');
    const offlinePaymentModal = document.getElementById('modal-auction-offline-payment');
    const goBackBtn = document.getElementById('auction-offline-go-back');
    const entryFeeForm = document.getElementById('auction-entry-fee-form');
    const offlineForm = document.getElementById('auction-offline-payment-form');

    if (!entryFeeForm && !offlineForm && !entryPaymentModal && !offlinePaymentModal) return;

    const i18n = document.getElementById('auction-entry-i18n')?.dataset || {};
    const processingText = i18n.processing || 'Processing';
    const defaultErrorText = i18n.defaultError || 'Something went wrong. Please try again.';
    const spinnerHtml = '<span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span> ' + processingText + '...';

    gatewayRadios.forEach(function (radio) {
        radio.addEventListener('change', function () {
            if (typeof window.removeWalletIfApplied === 'function') window.removeWalletIfApplied();
            if (offlineCard) offlineCard.classList.add('d-none');
            if (methodField) methodField.innerHTML = '';
            if (processBtn) processBtn.disabled = false;
        });
    });

    if (offlineRadio && offlineCard) {
        offlineRadio.addEventListener('change', function () {
            if (this.checked) {
                if (typeof window.removeWalletIfApplied === 'function') window.removeWalletIfApplied();
                offlineCard.classList.remove('d-none');
                if (processBtn) processBtn.disabled = true;
            }
        });
    }

    document.querySelectorAll('.auction-offline-payment-btn').forEach(function (btn) {
        btn.addEventListener('click', function () {
            const methodId = this.dataset.methodId;
            const amount = this.dataset.amount;
            const offlinePaymentField = document.getElementById('auction_offline_payment_field');
            const routeUrl = document.getElementById('route-pay-offline-method-list')?.dataset?.url;

            if (goBackBtn) goBackBtn.classList.remove('d-none');

            if (offlinePaymentField) offlinePaymentField.innerHTML = '';
            if (!routeUrl || !methodId) return;

            $.ajax({
                url: routeUrl + '?method_id=' + methodId + '&edit_due_amount=' + encodeURIComponent(amount || 0),
                type: 'GET',
                success: function (response) {
                    if (offlinePaymentField) offlinePaymentField.innerHTML = response?.methodHtml;

                    const bsEntryModal = bootstrap.Modal.getInstance(entryPaymentModal);
                    if (bsEntryModal) {
                        entryPaymentModal.addEventListener('hidden.bs.modal', function openOffline() {
                            entryPaymentModal.removeEventListener('hidden.bs.modal', openOffline);
                            new bootstrap.Modal(offlinePaymentModal).show();
                        }, { once: true });
                        bsEntryModal.hide();
                    }
                }
            });
        });
    });

    if (goBackBtn) {
        goBackBtn.addEventListener('click', function (e) {
            e.preventDefault();
            const bsOfflineModal = bootstrap.Modal.getInstance(offlinePaymentModal);
            if (bsOfflineModal) {
                offlinePaymentModal.addEventListener('hidden.bs.modal', function reopenPayment() {
                    offlinePaymentModal.removeEventListener('hidden.bs.modal', reopenPayment);
                    new bootstrap.Modal(entryPaymentModal).show();
                }, { once: true });
                bsOfflineModal.hide();
            }
        });
    }

    if (entryFeeForm && processBtn) {
        let entryFeeSubmitting = false;
        entryFeeForm.addEventListener('submit', function (e) {
            const checkedMethod = entryFeeForm.querySelector('input[name="payment_method"]:checked');
            if (!checkedMethod) {
                e.preventDefault();
                const msg = (document.getElementById('auction-entry-i18n')?.dataset?.selectPaymentMethod)
                    || 'Please select a payment method.';
                if (typeof toastMagic !== 'undefined') toastMagic.error(msg);
                return;
            }
            const isWallet = checkedMethod.value === 'wallet';
            if (!isWallet) return;

            if (entryFeeSubmitting) {
                e.preventDefault();
                return;
            }
            entryFeeSubmitting = true;

            processBtn.disabled = true;
            processBtn.dataset.originalHtml = processBtn.innerHTML;
            processBtn.innerHTML = spinnerHtml;
        });
    }

    if (offlineForm) {
        offlineForm.addEventListener('submit', function (e) {
            e.preventDefault();

            const submitBtn = offlineForm.querySelector('button[type="submit"]');
            const originalHtml = submitBtn.innerHTML;
            submitBtn.disabled = true;
            submitBtn.innerHTML = spinnerHtml;

            $.ajax({
                url: offlineForm.action,
                type: 'POST',
                data: $(offlineForm).serialize(),
                success: function (response) {
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = originalHtml;
                    toastMagic.success(response.message);
                    const bsOfflineModal = bootstrap.Modal.getInstance(offlinePaymentModal);
                    if (bsOfflineModal) bsOfflineModal.hide();

                    location.reload();
                },
                error: function (xhr) {
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = originalHtml;
                    const msg = xhr.responseJSON?.message || defaultErrorText;
                    toastMagic.error(msg);
                }
            });
        });
    }
})();
