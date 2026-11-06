"use strict";

jQuery(document).ready(function ($) {
    function scrollRowIntoView(row) {
        if (row && row.scrollIntoView) {
            row.scrollIntoView({behavior: "smooth", block: "start"});
        }
    }

    function paymentFieldHtml() {
        const t = $("#get-add-input-field-text");
        return `
            <div class="border rounded-10 bg-white p-12 p-sm-20 field-row">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h6 class="mb-0 fw-bold text-capitalize">${t.data("field-label")} <span class="field-no"></span></h6>
                    <button type="button" class="btn btn-outline-danger icon-btn remove-input-fields-group" title="${t.data("delete-text")}"><i class="fi fi-rr-trash"></i></button>
                </div>
                <div class="row g-3">
                    <div class="col-sm-6">
                        <label class="form-label mb-2">${t.data("input-field-name")} <span class="text-danger">*</span></label>
                        <input type="text" name="input_name[]" class="form-control" placeholder="${t.data("input-field-name-placeholder")}" value="" required>
                    </div>
                    <div class="col-sm-6">
                        <label class="form-label mb-2">${t.data("input-data")} <span class="text-danger">*</span></label>
                        <input type="text" name="input_data[]" class="form-control" placeholder="${t.data("input-data-placeholder")}" value="" required>
                    </div>
                </div>
            </div>`;
    }

    function customerFieldHtml(index) {
        const t = $("#get-add-customer-input-field-text");
        return `
            <div class="border rounded-10 bg-white p-12 p-sm-20 field-row">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h6 class="mb-0 fw-bold text-capitalize">${t.data("field-label")} <span class="field-no"></span></h6>
                    <button type="button" class="btn btn-outline-danger icon-btn remove-input-fields-group" title="${t.data("delete-text")}"><i class="fi fi-rr-trash"></i></button>
                </div>
                <div class="row g-3 align-items-end">
                    <div class="col-md-4">
                        <label class="form-label mb-2">${t.data("input-field-name")} <span class="text-danger">*</span></label>
                        <input type="text" name="customer_input[]" class="form-control" placeholder="${t.data("input-field-name-placeholder")}" value="" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label mb-2">${t.data("input-placeholder")} <span class="text-danger">*</span></label>
                        <input type="text" name="customer_placeholder[]" class="form-control" placeholder="${t.data("input-placeholder-placeholder")}" value="" required>
                    </div>
                    <div class="col-md-4">
                        <label class="d-flex align-items-center gap-2 border rounded px-3 min-h-40 mb-0 user-select-none cursor-pointer">
                            <input type="checkbox" class="form-check-input checkbox--input mt-0" value="1" name="is_required[${index}]">
                            <span class="text-muted">${t.data("require-text")}</span>
                        </label>
                    </div>
                </div>
            </div>`;
    }

    function blockIfMissing($section, controls, t) {
        $section.find(".is-invalid").removeClass("is-invalid");
        let firstMissing = null;
        let message = "";
        $section.children(".field-row").each(function (rowIndex) {
            const $row = $(this);
            controls.forEach(function (control) {
                const $el = $row.find('[name="' + control.name + '"]');
                if ($.trim($el.val() || "") === "") {
                    $el.addClass("is-invalid");
                    if (!firstMissing) {
                        firstMissing = $el;
                        message = t.data("field-label") + " #" + (rowIndex + 1) + ": " + control.label + " " + t.data("required-suffix");
                    }
                }
            });
        });
        if (firstMissing) {
            scrollRowIntoView(firstMissing.closest(".field-row")[0]);
            firstMissing.trigger("focus");
            if (window.toastMagic && typeof toastMagic.error === "function") {
                toastMagic.error(message);
            }
            return true;
        }
        return false;
    }

    function nextRequiredIndex($section) {
        let max = -1;
        $section.find('input[type=checkbox][name^="is_required["]').each(function () {
            const match = /is_required\[(\d+)\]/.exec(this.name);
            if (match) {
                max = Math.max(max, parseInt(match[1], 10));
            }
        });
        return max + 1;
    }

    const $offlineMethodForm = $("#payment-method-offline");
    const initialFieldRows = $offlineMethodForm.find(".offline-field-section").map(function () {
        return {section: $(this), rows: $(this).children(".field-row").toArray()};
    }).get();

    $offlineMethodForm.on("reset", function () {
        initialFieldRows.forEach(function (initialSection) {
            initialSection.section.children(".field-row").detach();
            initialSection.section.append(initialSection.rows);
        });
        $offlineMethodForm.find(".is-invalid").removeClass("is-invalid");
    });

    $(document).on("click", ".remove-input-fields-group", function () {
        $(this).closest(".field-row").remove();
    });

    $(document).on("input change", "#payment-method-offline .is-invalid", function () {
        if ($.trim($(this).val() || "") !== "") {
            $(this).removeClass("is-invalid");
        }
    });

    $(document).on("click", ".add-input-fields-group", function (event) {
        event.preventDefault();
        const t = $("#get-add-input-field-text");
        const $section = $(this).siblings(".input-fields-section");
        const controls = [
            {name: "input_name[]", label: t.data("input-field-name")},
            {name: "input_data[]", label: t.data("input-data")},
        ];
        if (blockIfMissing($section, controls, t)) {
            return;
        }
        const $new = $(paymentFieldHtml());
        $section.append($new);
        scrollRowIntoView($new[0]);
        $new.find('input[name="input_name[]"]').trigger("focus");
    });

    $(document).on("click", ".add-customer-input-fields-group", function (event) {
        event.preventDefault();
        const t = $("#get-add-customer-input-field-text");
        const $section = $(this).siblings(".customer-input-fields-section");
        const controls = [
            {name: "customer_input[]", label: t.data("input-field-name")},
            {name: "customer_placeholder[]", label: t.data("input-placeholder")},
        ];
        if (blockIfMissing($section, controls, t)) {
            return;
        }
        const $new = $(customerFieldHtml(nextRequiredIndex($section)));
        $section.append($new);
        scrollRowIntoView($new[0]);
        $new.find('input[name="customer_input[]"]').trigger("focus");
    });

    $(".method-status-form").on("submit", function (event) {
        event.preventDefault();
        $.ajaxSetup({
            headers: {
                "X-CSRF-TOKEN": $('meta[name="_token"]').attr("content")
            }
        });
        $.ajax({
            url: $(this).attr("action"),
            method: $(this).attr("method"),
            data: $(this).serialize(),
            success: function (data) {
                if (parseInt(data.success_status) === 1) {
                    toastMagic.success(data.message);
                } else if (parseInt(data.success_status) === 0) {
                    toastMagic.error(data.message);
                }
                setTimeout(function () {
                    location.reload();
                }, 1000);
            }
        });
    });

    $("#payment-method-offline").on("submit", function (event) {
        event.preventDefault();
        $.ajaxSetup({
            headers: {
                "X-CSRF-TOKEN": $('meta[name="_token"]').attr("content")
            }
        });
        $.ajax({
            url: $(this).attr("action"),
            method: $(this).attr("method"),
            data: $(this).serialize(),
            success: function (data) {
                if (parseInt(data.status) === 1) {
                    toastMagic.success(data.message);
                    setTimeout(function () {
                        location.href = data.redirect_url;
                    }, 1000);
                } else {
                    toastMagic.error(data.message);
                }
            },
            error: function (xhr) {
                xhrResponseManager(xhr);
            },
        });
    });
});
