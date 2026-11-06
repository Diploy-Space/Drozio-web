"use strict";

$('.default-language-delete-alert').on('click', function () {
    let messageContainer = $('#get-language-warning-message');
    if ($(this).data('code')?.toString() === 'en') {
        toastMagic.warning(messageContainer.data('title'), messageContainer.data('english'));
    } else {
        toastMagic.warning(messageContainer.data('title'), messageContainer.data('message'));
    }
})

$('.mark-as-default-language-alert').on('click', function () {
    let messageContainer = $('#get-mark-default-warning-message');
    toastMagic.warning(messageContainer.data('title'), messageContainer.data('message'));
})

$('.default-currency-delete-alert').on('click', function () {
    let messageContainer = $('#get-currency-warning-message');
    toastMagic.warning(messageContainer.data('title'), messageContainer.data('message'));
})

$('.mark-as-default-currency').on('click', function () {
    $.ajaxSetup({
        headers: {
            "X-CSRF-TOKEN": $('meta[name="_token"]').attr("content"),
        },
    });
    $.ajax({
        url: $('.mark-as-default-currency').data('ajax'),
        method: 'POST',
        data: {
            _token: $('meta[name="_token"]').attr("content"),
            code: $(this).data('code'),
        },
        success: function (response) {
            if (response?.mode?.toString() === 'multi_currency') {
                $('#defaultCurrencyChangeModal').find('.modal-body').html(response?.view);
                $('#defaultCurrencyChangeModal').modal('show');
            } else {
                toastMagic.success(response.message);
                setTimeout(() => {
                    location.reload();
                }, 2000)
            }
        },
        error: function (response) {
            if (response.status === 422) {
                let errors = response?.responseJSON?.errors;
                Object.keys(errors).forEach(function (key) {
                    toastMagic.error(errors[key][0]);
                });
            } else {
                toastMagic.error(response?.responseJSON?.message || 'Something went wrong!');
            }
        }
    });
});

$('.clean-database-form').on('submit', function (event) {
    const checkboxes = $(this).find('input[name="tables[]"]:checked');
    if (checkboxes.length === 0) {
        event.preventDefault();
        let cleanDatabaseFormMsg = $('.clean-database-form-msg');
        toastMagic.warning(cleanDatabaseFormMsg?.data('warning'), cleanDatabaseFormMsg?.data('warning-msg'), true);
    }
});


const SOFTWARE_UPDATE_RELOAD_DELAY = 5000;

function isSoftwareUpdateInterrupted(xhr) {
    return xhr?.status === 0 || xhr?.status >= 500;
}

function softwareUpdateErrorMessage(xhr, fallback) {
    const payload = xhr?.responseJSON;

    if (Array.isArray(payload?.errors) && payload.errors.length) {
        return payload.errors[0].message;
    }

    return payload?.message || fallback;
}

$('#software-update-form').on('submit', function (event) {
    event.preventDefault();
    const $form = $(this);
    const fallbackMessage = $form.data('request-failed');
    if ($('[name="update_file"]').val()) {
        $.ajaxSetup({
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            }
        });
        let redirectRoute = $(this).data('redirect-route');
        let isReloadPending = false;
        let formData = new FormData(document.getElementById('software-update-form'));
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
                $form.find('[type="reset"], [type="submit"]').prop('disabled', true);
            },
            success: function (response) {
                if (response?.status) {
                    toastMagic.success(response?.message);
                    location.href = response?.redirect || redirectRoute;
                } else {
                    $('.progress-bar-container').hide();
                    toastMagic.error(response?.message || fallbackMessage);
                }
            },
            complete: function () {
                if (! isReloadPending) {
                    $form.find('[type="reset"], [type="submit"]').prop('disabled', false);
                }
            },
            error: function (xhr) {
                if (isSoftwareUpdateInterrupted(xhr)) {
                    isReloadPending = true;
                    toastMagic.info($form.data('update-in-progress'));
                    setTimeout(function () {
                        location.reload();
                    }, SOFTWARE_UPDATE_RELOAD_DELAY);
                    return;
                }

                $('.progress-bar-container').hide();
                toastMagic.error(softwareUpdateErrorMessage(xhr, fallbackMessage));
            },
        });
    } else {
        toastMagic.error($form.data('input-warning'));
    }
});
