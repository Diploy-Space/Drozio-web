'use strict';

$(document).ready(function () {

    const vacationForm = $('#vendor-vacation-offcanvas-form');
    const vacationDateRange = $('#vacation_date_range');
    const customTimeRangeStart = $('#custom_time_range_start');
    const customTimeRangeEnd = $('#custom_time_range_end');
    const dateErrorSection = $('#dateError');
    const moduleErrorSection = $('#vacationModuleError');
    const VACATION_DISPLAY_FORMAT = 'DD MMM YYYY hh:mm A';
    const VACATION_STORE_FORMAT = 'YYYY-MM-DD HH:mm:ss';

    function storedVacationRange() {
        const start = moment(customTimeRangeStart.val());
        const end = moment(customTimeRangeEnd.val());

        return start.isValid() && end.isValid() ? {start: start, end: end} : null;
    }

    function renderVacationRange() {
        const range = storedVacationRange();
        vacationDateRange.val(range
            ? range.start.format(VACATION_DISPLAY_FORMAT) + ' - ' + range.end.format(VACATION_DISPLAY_FORMAT)
            : '');
    }

    function setVacationRange(start, end) {
        customTimeRangeStart.val(start.format(VACATION_STORE_FORMAT));
        customTimeRangeEnd.val(end.format(VACATION_STORE_FORMAT));
        renderVacationRange();
        dateErrorSection.addClass('d-none');
    }

    function clearVacationRange() {
        customTimeRangeStart.val('');
        customTimeRangeEnd.val('');
        vacationDateRange.val('');
    }

    const checkedDurationType = () => $('input[name="vacation_duration_type"]:checked').val();
    let currentDurationType = checkedDurationType();
    let customVacationRange = currentDurationType === 'custom' ? storedVacationRange() : null;

    function restoreCustomVacationRange() {
        if (!customVacationRange) {
            clearVacationRange();
            return;
        }

        setVacationRange(customVacationRange.start, customVacationRange.end);
        const picker = vacationDateRange.data('daterangepicker');
        if (picker) {
            picker.setStartDate(customVacationRange.start);
            picker.setEndDate(customVacationRange.end);
        }
    }

    function updateVacationDatesBasedOnDuration(selectedOption, action = null) {
        dateErrorSection.addClass('d-none');

        if (action !== 'page_load' && currentDurationType === 'custom' && selectedOption !== 'custom') {
            customVacationRange = storedVacationRange();
        }
        currentDurationType = selectedOption;

        if (selectedOption === 'until_change') {
            clearVacationRange();
            vacationDateRange.prop('disabled', true);
            $('.custom-time-range-container').addClass('opacity');
            $('.vacation-time-required').addClass('d-none');
            return;
        }

        $('.custom-time-range-container').removeClass('opacity');
        $('.vacation-time-required').removeClass('d-none');

        if (selectedOption === 'one_day') {
            vacationDateRange.prop('disabled', true);
            if (action !== 'page_load') {
                setVacationRange(moment(), moment().add(1, 'days'));
            }
            return;
        }

        if (action !== 'page_load') {
            restoreCustomVacationRange();
        }
        vacationDateRange.prop('disabled', false);
    }

    if (vacationDateRange.length) {
        const savedRange = storedVacationRange();

        const floatingParent = vacationDateRange.closest('.offcanvas, .modal');

        vacationDateRange.daterangepicker({
            parentEl: floatingParent.length ? floatingParent : 'body',
            autoUpdateInput: false,
            drops: 'auto',
            opens: 'left',
            timePicker: true,
            minDate: moment().startOf('day'),
            startDate: savedRange ? savedRange.start : moment(),
            endDate: savedRange ? savedRange.end : moment().add(1, 'days'),
            locale: {
                format: VACATION_DISPLAY_FORMAT,
            },
        });

        vacationDateRange.on('apply.daterangepicker', function (event, picker) {
            customVacationRange = {start: picker.startDate.clone().startOf('minute'), end: picker.endDate.clone().startOf('minute')};
            setVacationRange(picker.startDate.clone().startOf('minute'), picker.endDate.clone().startOf('minute'));
        });

        renderVacationRange();
        updateVacationDatesBasedOnDuration(checkedDurationType(), 'page_load');
    }

    $('input[name="vacation_duration_type"]').on('change', function () {
        updateVacationDatesBasedOnDuration($(this).val());
    });

    $('input[name="modules[]"]').on('change', function () {
        moduleErrorSection.addClass('d-none');
    });

    vacationForm.find('input[name="vacation_status"]').on('change', function () {
        if (!$(this).is(':checked')) {
            moduleErrorSection.addClass('d-none');
            dateErrorSection.addClass('d-none');
        }
    });

    function renderCharacterCount(textarea) {
        const maxLength = parseInt(textarea.attr('maxlength'), 10);
        textarea.closest('.form-group').find('.js-character-counter-text')
            .text(textarea.val().length + '/' + maxLength);
    }

    $('.js-character-counter').each(function () {
        renderCharacterCount($(this));
    }).on('input', function () {
        renderCharacterCount($(this));
    });

    vacationForm.on('reset', function () {
        setTimeout(function () {
            currentDurationType = checkedDurationType();
            customVacationRange = currentDurationType === 'custom' ? storedVacationRange() : null;
            renderVacationRange();
            vacationForm.find('.js-character-counter').each(function () {
                renderCharacterCount($(this));
            });
            moduleErrorSection.addClass('d-none');
            updateVacationDatesBasedOnDuration(checkedDurationType(), 'page_load');
        }, 0);
    });

    vacationForm.on('submit', function (event) {
        if (!vacationForm.find('input[name="vacation_status"]').is(':checked')) {
            moduleErrorSection.addClass('d-none');
            dateErrorSection.addClass('d-none');
            return;
        }

        const durationType = $('input[name="vacation_duration_type"]:checked').val();
        const isTurningOn = vacationForm.find('input[name="vacation_status"]').is(':checked');
        const hasModule = !isTurningOn || $('input[name="modules[]"]:checked').length > 0;
        const hasRange = !isTurningOn || durationType !== 'custom' || storedVacationRange() !== null;

        moduleErrorSection.toggleClass('d-none', hasModule);
        dateErrorSection.toggleClass('d-none', hasRange);

        if (!hasModule || !hasRange) {
            event.preventDefault();
        }
    });

    function syncFreeDeliveryAmounts() {
        $('.js-free-delivery-amount').each(function () {
            const amountInput = $(this);
            amountInput.prop('required', $(amountInput.data('status-toggle')).is(':checked'));
        });
    }

    syncFreeDeliveryAmounts();
    $(document).on('change', '.toggle-switch-message', syncFreeDeliveryAmounts);
    $('#toggle-modal-ok-button').on('click', function () {
        setTimeout(syncFreeDeliveryAmounts, 0);
    });

    const TIN_EXPIRE_YEAR_SPAN = 10;

    $('.js-tin-expire-date').each(function () {
        const expireDateInput = $(this);

        expireDateInput.daterangepicker({
            singleDatePicker: true,
            showDropdowns: true,
            minYear: moment().subtract(TIN_EXPIRE_YEAR_SPAN, 'years').year(),
            maxYear: moment().add(TIN_EXPIRE_YEAR_SPAN, 'years').year(),
            autoUpdateInput: false,
            drops: 'auto',
            locale: {
                cancelLabel: expireDateInput.data('clear-label'),
            },
        });

        expireDateInput.on('apply.daterangepicker', function (event, picker) {
            expireDateInput.val(picker.startDate.format('MM/DD/YYYY'));
        });

        expireDateInput.on('cancel.daterangepicker', function () {
            expireDateInput.val('');
        });
    });

    const originalValues = {};
    const fileModified = {};

    function captureOriginalValues() {
        $('form input, form select, form textarea').each(function () {
            const element = $(this);
            const name = element.attr('name');
            const type = element.attr('type');

            if (name && type !== 'file') {
                if (type === 'checkbox' || type === 'radio') {
                    originalValues[name] = element.is(':checked');
                } else {
                    originalValues[name] = element.val();
                }
            }
        });

        $('input[type="file"]').each(function () {
            const name = $(this).attr('name');
            if (name) {
                fileModified[name] = false;
            }
        });
    }

    $('input[type="file"]').on('change', function () {
        const name = $(this).attr('name');
        if (name) {
            fileModified[name] = true;
        }
    });

    function smartReset() {
        $('form input, form select, form textarea').each(function () {
            const element = $(this);
            const name = element.attr('name');
            const type = element.attr('type');

            if (name && type !== 'file' && originalValues.hasOwnProperty(name)) {
                if (type === 'checkbox' || type === 'radio') {
                    element.prop('checked', originalValues[name]);
                } else {
                    element.val(originalValues[name]);
                }
            }
        });

        $('input[type="file"]').each(function () {
            const element = $(this);
            const name = element.attr('name');

            if (name && fileModified[name]) {
                element.val('');
                fileModified[name] = false;
            }
        });
    }

    captureOriginalValues();

    $('#reset_btn').on('click', function (e) {
        e.preventDefault();
        smartReset();
    });

    const availabilityForm = document.getElementById('store-availability-form');
    if (availabilityForm) {
        const syncAvailabilityRow = function (input) {
            const description = input.closest('[data-availability-row]')?.querySelector('[data-availability-description]');
            if (description) {
                description.textContent = input.checked ? description.dataset.onText : description.dataset.offText;
            }
        };
        const syncAvailabilityRows = function () {
            availabilityForm.querySelectorAll('[data-availability-input]').forEach(syncAvailabilityRow);
        };

        availabilityForm.addEventListener('change', function (event) {
            if (event.target.matches('[data-availability-input]')) {
                syncAvailabilityRow(event.target);
            }
        });
        availabilityForm.addEventListener('reset', function () {
            setTimeout(syncAvailabilityRows, 0);
        });
    }
});

