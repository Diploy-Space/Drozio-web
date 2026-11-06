'use strict';

function closeAddonModals() {
    $('#addon-list-wrapper .modal').each(function () {
        bootstrap.Modal.getInstance(this)?.dispose();
    });
    $('.modal-backdrop').remove();
    $('body').removeClass('modal-open').css({overflow: '', paddingRight: ''});
}

function initAddonTooltips() {
    $('#addon-list-wrapper [data-bs-toggle="tooltip"]').each(function () {
        bootstrap.Tooltip.getOrCreateInstance(this);
    });
}

function disposeAddonTooltips() {
    $('#addon-list-wrapper [data-bs-toggle="tooltip"]').each(function () {
        bootstrap.Tooltip.getInstance(this)?.dispose();
    });
}

function showAddonThumbnailPlaceholder(image) {
    const placeholder = image.dataset.onerror;

    if (placeholder && image.getAttribute('src') !== placeholder) {
        image.setAttribute('src', placeholder);
    }
}

function replaceBrokenAddonThumbnails() {
    document.querySelectorAll('#addon-list-wrapper img.onerror-image').forEach(function (image) {
        if (image.complete && image.naturalWidth === 0) {
            showAddonThumbnailPlaceholder(image);
        }
    });
}

document.addEventListener('error', function (event) {
    const image = event.target;

    if (image instanceof HTMLImageElement && image.matches('#addon-list-wrapper img.onerror-image')) {
        showAddonThumbnailPlaceholder(image);
    }
}, true);

function closeActivationModal() {
    const activationModal = document.getElementById('activatedThemeModal');

    if (activationModal) {
        bootstrap.Modal.getOrCreateInstance(activationModal).hide();
    }
    $('#activateData').empty();
}

function refreshAddonList(response) {
    if (response?.view !== undefined) {
        closeAddonModals();
        disposeAddonTooltips();
        $('#addon-list-wrapper').html(response.view);
        initAddonTooltips();
    }

    if (response?.counts) {
        $.each(response.counts, function (key, value) {
            $('[data-addon-status="' + key + '"]').text(value);
        });
    }
}

$(document).on('click', '.addon-publish-status', function (event) {
    event.preventDefault();
});

$('#addon-upload-form').on('submit', function (event) {
    event.preventDefault();

    const fileInput = $(this).find('input[type="file"]')[0];

    if (!fileInput || fileInput.files.length === 0) {
        toastMagic.error($('#get-file-upload-field-required-message').data('error'));
    } else {
        $.ajaxSetup({
            headers: {
                "X-CSRF-TOKEN": $('meta[name="_token"]').attr("content"),
            },
        });
        let formData = new FormData(document.getElementById('addon-upload-form'));
        $.ajax({
            type: 'POST',
            url: $(this).attr('action'),
            data: formData,
            processData: false,
            contentType: false,
            xhr: function () {
                let xhr = new window.XMLHttpRequest();
                $('.progress-bar-container').show();
                xhr.upload.addEventListener("progress", function (e) {
                    if (e.lengthComputable) {
                        let percentage = Math.round((e.loaded * 100) / e.total);
                        $(".upload-progress-label").text(percentage + "%");
                        $('.upload-progress-bar').css('width', percentage + '%');
                    }
                }, false);
                return xhr;
            },
            beforeSend: function () {
                $(this).find('[type="reset"]').attr('disabled');
                $(this).find('[type="submit"]').attr('disabled');
            },
            success: function (response) {
                if (response.status === 'error') {
                    $('.progress-bar-container').hide();
                    toastMagic.error(response.message);
                } else if (response.status === 'success') {
                    toastMagic.success(response.message);
                    $('#uploadAddonOffcanvas').length && bootstrap.Offcanvas.getInstance(document.getElementById('uploadAddonOffcanvas'))?.hide();
                    refreshAddonList(response);
                    setTimeout(function () {
                        location.reload();
                    }, 3000);
                }
            },
            complete: function () {
                $(this).find('[type="reset"]').removeAttr('disabled');
                $(this).find('[type="submit"]').removeAttr('disabled');
            },
            error: function (xhr) {
                xhrResponseManager(xhr);
            },
        });
    }
});

$(document).on("submit", ".addon-publish-form", function (event) {
    event.preventDefault();

    $.ajaxSetup({
        headers: {
            "X-CSRF-TOKEN": $('meta[name="csrf-token"]').attr("content"),
        },
    });
    $.ajax({
        type: 'POST',
        url: $(this).attr("action"),
        data: $(this).serialize(),
        beforeSend: function () {
            $("#loading").fadeIn();
        },
        success: function (response) {
            if (response.flag === 'inactive') {
                $('#activateData').empty().html(response.view);
                $("#activatedThemeModal").addClass('bg-soft-dark').modal("show");
                if (typeof reinitializeTooltips === 'function') {
                    reinitializeTooltips();
                }
            } else {
                if (response.errors) {
                    for (let i = 0; i < response.errors.length; i++) {
                        setTimeout(() => {
                            toastMagic.error(response.errors[i].message);
                        }, i * 500);
                    }
                } else {
                    toastMagic.success(response?.message);
                    refreshAddonList(response);
                }
            }
        },
        complete: function () {
            $("#loading").fadeOut();
        },
    });
});

$(document).on('submit', '.addon-complete-installation-form', function (event) {
    event.preventDefault();

    const $button = $(this).find('button[type="submit"]');

    $.ajax({
        type: 'POST',
        url: $(this).attr('action'),
        data: $(this).serialize(),
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
        },
        beforeSend: function () {
            $button.prop('disabled', true);
            $('#loading').fadeIn();
        },
        success: function (response) {
            if (response?.status === 'success') {
                toastMagic.success(response.message);
                refreshAddonList(response);
            } else {
                toastMagic.error(response?.message);
            }
        },
        error: function (xhr) {
            xhrResponseManager(xhr);
        },
        complete: function () {
            $button.prop('disabled', false);
            $('#loading').fadeOut();
        },
    });
});

$(document).on('submit', '#addon-list-activation-form', function (event) {
    event.preventDefault();

    const $form = $(this);
    const $button = $form.find('.addon-list-activation-submit');
    const $spinner = $button.find('.submit-spinner');
    const $label = $button.find('.submit-label');
    const originalLabel = $label.text();

    $.ajaxSetup({
        headers: {
            "X-CSRF-TOKEN": $('meta[name="csrf-token"]').attr("content"),
        },
    });
    $.ajax({
        type: 'POST',
        url: $form.attr('action'),
        data: new FormData(this),
        processData: false,
        contentType: false,
        beforeSend: function () {
            $button.prop('disabled', true);
            $spinner.removeClass('d--none');
            $label.text($label.data('loading-text') || 'Activating...');
        },
        success: function (response) {
            if (response.status === 'success') {
                toastMagic.success(response.message);
                closeActivationModal();
                refreshAddonList(response);
            } else if (response.errors) {
                for (let i = 0; i < response.errors.length; i++) {
                    setTimeout(() => {
                        toastMagic.error(response.errors[i].message);
                    }, i * 500);
                }
                resetButton();
            } else {
                toastMagic.error(response.message || 'Activation failed');
                resetButton();
            }
        },
        error: function (xhr) {
            const errors = xhr?.responseJSON?.errors;
            if (errors && typeof errors === 'object') {
                const messages = Array.isArray(errors)
                    ? errors.map((error) => error.message)
                    : Object.values(errors).flat();
                messages.forEach((msg, i) => {
                    setTimeout(() => toastMagic.error(msg), i * 500);
                });
            } else {
                toastMagic.error(xhr?.responseJSON?.message || 'Activation failed');
            }
            resetButton();
        },
    });

    function resetButton() {
        $button.prop('disabled', false);
        $spinner.addClass('d--none');
        $label.text(originalLabel);
    }
});

$(document).on("submit", ".addon-delete-form", function (event) {
    event.preventDefault();
    $.ajaxSetup({
        headers: {
            "X-CSRF-TOKEN": $('meta[name="csrf-token"]').attr("content"),
        },
    });
    $.ajax({
        type: 'POST',
        url: $(this).attr("action"),
        data: $(this).serialize(),
        beforeSend: function () {
            $("#loading").fadeIn();
        },
        success: function (data) {
            if (data.status === 'success') {
                toastMagic.success(data.message);
                refreshAddonList(data);
            } else if (data.status === 'error') {
                toastMagic.error(data.message);
            }
        },
        complete: function () {
            $("#loading").fadeOut();
        },
    });
});

$(function () {
    initAddonTooltips();
    replaceBrokenAddonThumbnails();
});
