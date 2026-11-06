"use strict";

$('.reset-form').on('click', function () {
    window.location.reload();
});

$('.no-reload-form').on('submit', function (e) {
    e.preventDefault();
    const $form = $(this);

    $.ajaxSetup({
        headers: {
            "X-CSRF-TOKEN": $('meta[name="_token"]').attr("content"),
        },
    });

    $.ajax({
        url: $form.attr('action'),
        method: 'POST',
        data: $form.serialize(),
        success: function (response) {
            toastMagic.success(response.message);

            if ($form.hasClass('reload-true')) {
                setTimeout(() => {
                    location.reload();
                }, 2000);
            }
        },
    });
});


$('.status-update-form').on('submit', function (e) {
    e.preventDefault();
    const $form = $(this);
    $.ajaxSetup({
        headers: {
            "X-CSRF-TOKEN": $('meta[name="_token"]').attr("content"),
        },
    });
    $.ajax({
        url: $form.attr('action'),
        method: 'POST',
        data: $form.serialize(),
        success: function (response) {
            if (response.status) {
                toastMagic.success(response.message);
            } else {
                toastMagic.warning(response.message);
            }
            if ($form.hasClass('reload-true')) {
                setTimeout(() => location.reload(), 2000);
            }
        },
        error: function (xhr) {
            if (xhr.status === 0) {
                toastMagic.error("Network error. Check your internet connection.");
                return;
            }

            xhrResponseManager(xhr);
        }

    });
});



$(document).ready(function () {

    // reset dropdown value
    $('button[type="reset"]').on('click', function () {
        const form = $(this).closest('form');
        setTimeout(function () {
            const themeRatio = form.find('#theme_ratio');
            if (themeRatio.length) {
                themeRatio.html('');
            }
        }, 10);
    });



    $('.js-daterangepicker').each(function () {
        const $input = $(this);
        const isPreviousDateAllowed = $input.hasClass('previous-date-true');
        const isPlaceholderMode = $input.hasClass('placeholder-mode-true');
        const $floatingParent = $input.closest('.offcanvas, .modal');

        $input.daterangepicker({
            parentEl: $floatingParent.length ? $floatingParent : 'body',
            autoUpdateInput: !isPlaceholderMode,
            locale: {
                format: 'DD MMM YYYY'
            },
            minDate: !isPreviousDateAllowed ? moment() : false
        });

        if (isPlaceholderMode) {
            $input.attr('placeholder', 'DD MMM YYYY - DD MMM YYYY');
            $input.on('apply.daterangepicker', function (ev, picker) {
                $(this).val(picker.startDate.format('DD MMM YYYY') + ' - ' + picker.endDate.format('DD MMM YYYY'));
            });
            $input.on('cancel.daterangepicker', function (ev, picker) {
                $(this).val('');
            });
        }
    });

    let checkIsOffcanvasSetupDataValue = $('#check-offcanvas-setup-guide').data('value')?.toString();
    let checkIsOffcanvasSetupGuideEnable = checkIsOffcanvasSetupDataValue === 'true' || checkIsOffcanvasSetupDataValue === '1';

    if (checkIsOffcanvasSetupGuideEnable) {
        setTimeout(() => {
            const url = new URL(window.location.href);
            url.searchParams.delete('offcanvasShow');
            window.history.replaceState({}, document.title, url.toString());
        }, 3000);
    }

    // Hide daterangepicker on page scroll. We MUST close via the picker's
    // own .hide() — calling jQuery `.hide()` on the popup directly only
    // sets display: none and never updates the library's internal
    // `isShowing` flag. After that, the next click on the input toggles
    // the flag from "showing" → "hidden" (a no-op since CSS already hid
    // it), and the user has to click a SECOND time to actually re-open
    // the calendar. Iterating over the known picker-input classes and
    // calling `instance.hide()` keeps the flag in sync.
    function hideOpenDateRangePickers() {
        var pickerInputs = $(
            '.js-daterangepicker, .js-daterangepicker-with-range, ' +
            '.js-daterangepicker-times-sec, ' +
            '.js-date-range-picker-only-times, .date-range-js'
        );
        pickerInputs.each(function () {
            var picker = $(this).data('daterangepicker');
            if (picker && picker.isShowing) {
                picker.hide();
            }
        });
    }

    var daterangepickerScrolling = false;
    document.addEventListener('scroll', function (e) {
        // Don't hide if the scroll is happening inside the daterangepicker itself
        if ($(e.target).closest('.daterangepicker').length) return;
        hideOpenDateRangePickers();
    }, true);

    // Also hide on mouse wheel as fallback
    document.addEventListener('wheel', function (e) {
        if ($(e.target).closest('.daterangepicker').length) return;
        if (daterangepickerScrolling) return;
        daterangepickerScrolling = true;
        setTimeout(function() {
            hideOpenDateRangePickers();
            daterangepickerScrolling = false;
        }, 50);
    }, { passive: true });

    // Also hide on touch scroll (mobile)
    document.addEventListener('touchmove', function (e) {
        if ($(e.target).closest('.daterangepicker').length) return;
        hideOpenDateRangePickers();
    }, { passive: true });
});
function reinitializeTooltips() {
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
}



document.querySelectorAll('.no-negative-symbol').forEach(input => {
    input.addEventListener('keydown', function(e) {
        if (e.key === '-' || e.key === 'e') {
            e.preventDefault();
        }
    });

    input.addEventListener('input', function() {
        const cursorPos = this.selectionStart;
        const originalLength = this.value.length;

        this.value = this.value.replace(/-/g, '');
        const newLength = this.value.length;
        this.selectionStart = this.selectionEnd = cursorPos - (originalLength - newLength);
    });
});




let loadMoreBrandOnFilterPage = 2;
$('.load-more-product-brands').on('click', function () {
    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="_token"]').attr('content')
        }
    });
    $.ajax({
        type: 'POST',
        url: $(this).data('route'),
        data: {
            page: loadMoreBrandOnFilterPage,
            filter_brand_old_ids: $('[name="filter_brand_old_ids"]').val(),
        },
        beforeSend: function () {
            $('.load-more-product-brands-spinner').removeClass('d-none');
            $('.load-more-product-brands').addClass('d-none');
        },
        success: function (response) {
            loadMoreBrandOnFilterPage++;
            $("#load-more-filter-brands-view").append(response.html);
            if (response.hiddenCount <= 0) {
                $('.load-more-product-brands-container').hide();
            }

            setTimeout(() => {
                $('.load-more-product-brands-spinner').addClass('d-none');
                $('.load-more-product-brands').removeClass('d-none');
            }, 1000)
        },
        complete: function () {
            setTimeout(() => {
                $('.load-more-product-brands-spinner').addClass('d-none');
                $('.load-more-product-brands').removeClass('d-none');
            }, 1000)
        },
    });
});


let loadMoreStoreOnFilterPage = 2;
$('.load-more-product-stores').on('click', function () {
    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="_token"]').attr('content')
        }
    });
    $.ajax({
        type: 'POST',
        url: $(this).data('route'),
        data: {
            page: loadMoreStoreOnFilterPage,
            filter_shop_ids: $('[name="filter_shop_ids"]').val(),
        },
        beforeSend: function () {
            $('.load-more-product-stores-spinner').removeClass('d-none');
            $('.load-more-product-stores').addClass('d-none');
        },
        success: function (response) {
            loadMoreStoreOnFilterPage++;
            $("#load-more-filter-stores-view").append(response.html);
            if (response.hiddenCount <= 0) {
                $('.load-more-product-stores-container').hide();
            }

            setTimeout(() => {
                $('.load-more-product-stores-spinner').addClass('d-none');
                $('.load-more-product-stores').removeClass('d-none');
            }, 1000)
        },
        complete: function () {
            setTimeout(() => {
                $('.load-more-product-stores-spinner').addClass('d-none');
                $('.load-more-product-stores').removeClass('d-none');
            }, 1000)
        },
    });
});
