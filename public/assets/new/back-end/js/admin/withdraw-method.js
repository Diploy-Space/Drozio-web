'use strict';

jQuery(document).ready(function ($) {
    const MAX_FIELDS = 15;

    function nextIndex($section) {
        let max = -1;
        $section.children('[data-key]').each(function () {
            max = Math.max(max, parseInt($(this).attr('data-key'), 10) || 0);
        });
        return max + 1;
    }

    function fieldRowHtml(ocId, index) {
        const t = $('#get-add-filed-text');
        return `
            <div class="border rounded-10 bg-white p-12 p-sm-20 field-row" data-key="${index}">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h6 class="mb-0 fw-bold text-capitalize">${t.data('field-label')} <span class="field-no"></span></h6>
                    <button type="button" class="btn btn-outline-danger icon-btn remove-field" title="${t.data('remove')}">
                        <i class="fi fi-rr-trash"></i>
                    </button>
                </div>
                <div class="row g-3">
                    <div class="col-sm-6">
                        <label class="form-label mb-2">${t.data('input-filed')} <span class="text-danger">*</span></label>
                        <select class="form-control" name="field_type[]" required>
                            <option value="" selected disabled>--${t.data('select-field-type')}--</option>
                            <option value="string">${t.data('string')}</option>
                            <option value="number">${t.data('number')}</option>
                            <option value="date">${t.data('date')}</option>
                            <option value="password">${t.data('password')}</option>
                            <option value="email">${t.data('email')}</option>
                            <option value="phone">${t.data('phone')}</option>
                        </select>
                    </div>
                    <div class="col-sm-6">
                        <label class="form-label mb-2">${t.data('field-name')} <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="field_name[]" placeholder="${t.data('field-name')}" value="" required>
                    </div>
                    <div class="col-sm-6">
                        <label class="form-label mb-2">${t.data('placeholder-text')} <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="placeholder_text[]" placeholder="${t.data('placeholder-text')}" value="" required>
                    </div>
                    <div class="col-sm-6 d-flex align-items-end">
                        <label class="d-flex align-items-center gap-2 border rounded px-3 min-h-40 w-100 mb-0 user-select-none cursor-pointer">
                            <input class="form-check-input checkbox--input mt-0" type="checkbox" value="1" name="is_required[${index}]" checked>
                            <span class="text-muted">${t.data('required')}</span>
                        </label>
                    </div>
                </div>
            </div>`;
    }

    function scrollRowIntoView(row) {
        if (row && row.scrollIntoView) {
            row.scrollIntoView({behavior: 'smooth', block: 'start'});
        }
    }

    // Flag every empty control across all rows; return the first one and a message naming it.
    function findMissingControl($section) {
        const t = $('#get-add-filed-text');
        $section.find('.is-invalid').removeClass('is-invalid');

        let firstMissing = null;
        let message = '';
        $section.children('.field-row').each(function (rowIndex) {
            const $row = $(this);
            const controls = [
                {el: $row.find('select[name="field_type[]"]'), label: t.data('input-filed')},
                {el: $row.find('input[name="field_name[]"]'), label: t.data('field-name')},
                {el: $row.find('input[name="placeholder_text[]"]'), label: t.data('placeholder-text')},
            ];
            controls.forEach(function (control) {
                if ($.trim(control.el.val() || '') === '') {
                    control.el.addClass('is-invalid');
                    if (!firstMissing) {
                        firstMissing = control.el;
                        message = t.data('field-label') + ' #' + (rowIndex + 1) + ': ' + control.label + ' ' + t.data('required-suffix');
                    }
                }
            });
        });
        return {firstMissing, message};
    }

    $(document).on('click', '.add-more-field', function (event) {
        event.preventDefault();
        const $form = $(this).closest('form');
        const $section = $form.find('.custom-field-section');
        const t = $('#get-add-filed-text');

        // Every existing field must be complete before another can be added.
        const {firstMissing, message} = findMissingControl($section);
        if (firstMissing) {
            scrollRowIntoView(firstMissing.closest('.field-row')[0]);
            firstMissing.trigger('focus');
            if (window.toastMagic && typeof toastMagic.error === 'function') {
                toastMagic.error(message);
            }
            return;
        }

        if ($section.children('.field-row').length >= MAX_FIELDS) {
            Swal.fire({title: t.data('reached-maximum'), confirmButtonText: t.data('confirm')});
            return;
        }

        const $new = $(fieldRowHtml($form.attr('data-oc') || 'add', nextIndex($section)));
        $section.append($new);
        scrollRowIntoView($new[0]);
        $new.find('select[name="field_type[]"]').trigger('focus');
    });

    // Clear the invalid highlight as soon as the user fills a flagged control.
    $(document).on('input change', '.withdraw-method-form .is-invalid', function () {
        if ($.trim($(this).val() || '') !== '') {
            $(this).removeClass('is-invalid');
        }
    });

    $(document).on('click', '.remove-field', function () {
        $(this).closest('.field-row').remove();
    });

    // Native reset clears input values; drop the extra rows so only the first remains.
    $(document).on('reset', 'form.withdraw-method-form', function () {
        const $section = $(this).find('.custom-field-section');
        setTimeout(function () {
            $section.find('.field-row').not(':first').remove();
        }, 0);
    });
});
