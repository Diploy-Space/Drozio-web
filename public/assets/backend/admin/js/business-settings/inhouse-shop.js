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

    vacationForm.find('input[name="status"]').on('change', function () {
        if (!$(this).is(':checked')) {
            moduleErrorSection.addClass('d-none');
            dateErrorSection.addClass('d-none');
        }
    });

    vacationForm.on('reset', function () {
        setTimeout(function () {
            currentDurationType = checkedDurationType();
            customVacationRange = currentDurationType === 'custom' ? storedVacationRange() : null;
            renderVacationRange();
            moduleErrorSection.addClass('d-none');
            updateVacationDatesBasedOnDuration(checkedDurationType(), 'page_load');
        }, 0);
    });

    vacationForm.on('submit', function (event) {
        if (!vacationForm.find('input[name="status"]').is(':checked')) {
            moduleErrorSection.addClass('d-none');
            dateErrorSection.addClass('d-none');
            return;
        }

        const durationType = $('input[name="vacation_duration_type"]:checked').val();
        const isTurningOn = vacationForm.find('input[name="status"]').is(':checked');
        const hasModule = !isTurningOn || $('input[name="modules[]"]:checked').length > 0;
        const hasRange = !isTurningOn || durationType !== 'custom' || storedVacationRange() !== null;

        moduleErrorSection.toggleClass('d-none', hasModule);
        dateErrorSection.toggleClass('d-none', hasRange);

        if (!hasModule || !hasRange) {
            event.preventDefault();
        }
    });

    $('#reset_btn').on('click', function (e) {
        e.preventDefault();
        const form = this.closest('form');

        Array.from(form.elements).forEach(input => {
            if (input.type !== 'file') {
                input.value = '';
            }
        });

        const fileInput = form.querySelector('input[name="tin_certificate"]');
        const pdfSingle = form.querySelector('.pdf-single');
        const defaultDocThumb = pdfSingle?.getAttribute('data-default-src');

        if (fileInput && fileInput.value) {
            fileInput.value = '';

            const previewImg = pdfSingle.querySelector('.pdf-thumbnail');
            if (previewImg) {
                previewImg.src = defaultDocThumb;
            }

            pdfSingle.style.display = 'block';

            const uploadWrapper = form.querySelector('#doc-upload-wrapper');
            if (uploadWrapper) {
                uploadWrapper.style.display = 'none';
            }
        }
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

