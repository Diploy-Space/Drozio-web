"use strict";

function setAuthButtonLoading($button, isLoading) {
    if (isLoading) {
        $button.data('idle-label', $button.html());
        $button.attr('disabled', true)
            .html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>' + $button.data('idle-label'));
        return;
    }

    $button.removeAttr('disabled').html($button.data('idle-label'));
}

function refreshAuthCaptcha($form) {
    $form.find('.default-captcha-container').each(function () {
        $(this).find('input[name="default_captcha_value"]').val('');
        $(this).find('.captcha-image-container').trigger('click');
    });
}

function isAwaitingFirebaseToken($form) {
    return $form.find('.generate-firebase-auth-recaptcha').length > 0
        && !$form.find('input[name="firebase-auth-recaptcha-response"]').val();
}

function showAuthFormError(xhr) {
    let response = xhr.responseJSON;

    if (response && Array.isArray(response.errors) && response.errors.length) {
        response.errors.forEach(function (error, index) {
            setTimeout(function () {
                toastMagic.error(error.message);
            }, index * 500);
        });
        return;
    }

    toastMagic.error(response && response.message ? response.message : $('#message-auth-failed').data('text'));
}

function bindAuthFormAjax($form, $button, onSuccess) {
    $form.on('submit', function (event) {
        if (isAwaitingFirebaseToken($form)) {
            return;
        }

        event.preventDefault();

        if ($form.data('auth-form-submitting')) {
            return;
        }

        $form.data('auth-form-submitting', true);
        setAuthButtonLoading($button, true);

        let hasRecaptchaToken = typeof prepareRecaptchaToken === 'function' && typeof releaseRecaptchaToken === 'function';
        let recaptchaToken = hasRecaptchaToken ? prepareRecaptchaToken($form) : Promise.resolve(false);

        recaptchaToken.then(function () {
            let formData = $form.serialize();

            if (hasRecaptchaToken) {
                releaseRecaptchaToken($form);
            }

            submitAuthForm($form, $button, formData, onSuccess);
        });
    });
}

function submitAuthForm($form, $button, formData, onSuccess) {
    $.ajax({
        type: 'POST',
        url: $form.attr('action'),
        data: formData,
        complete: function () {
            $form.removeData('auth-form-submitting');
        },
        success: function (response) {
            if (response && response.error) {
                setAuthButtonLoading($button, false);
                refreshAuthCaptcha($form);
                toastMagic.error(response.error);
                return;
            }

            onSuccess(response);
        },
        error: function (xhr) {
            setAuthButtonLoading($button, false);
            refreshAuthCaptcha($form);
            showAuthFormError(xhr);
        },
    });
}
