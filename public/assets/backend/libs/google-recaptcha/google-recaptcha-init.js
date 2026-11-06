"use strict";

const RECAPTCHA_TOKEN_MAX_AGE = 90000;
const RECAPTCHA_READY_TIMEOUT = 8000;
const RECAPTCHA_READY_POLL = 100;

let recaptchaSiteKey = $('#get-google-recaptcha-key').data('value');
let defaultCaptchaRequired = $('#get-google-recaptcha-key').data('default-captcha-required')?.toString() === '1';
let lastSubmittedRecaptchaForm = null;
let captchaSessionOwners = {};

function setDefaultCaptchaRequired(defaultSectionElement, isRequired) {
    let defaultDynamicElement = $(defaultSectionElement);
    if (defaultDynamicElement?.length > 0) {
        let $captchaInput = defaultDynamicElement.find('[name="default_captcha_value"]');
        isRequired ? $captchaInput?.attr('required', true) : $captchaInput?.removeAttr('required');
    }
}

function whenRecaptchaReady() {
    return new Promise(function (resolve) {
        if (typeof grecaptcha !== 'undefined' && typeof grecaptcha.execute === 'function') {
            resolve(true);
            return;
        }

        let waited = 0;
        let poll = setInterval(function () {
            if (typeof grecaptcha !== 'undefined' && typeof grecaptcha.execute === 'function') {
                clearInterval(poll);
                resolve(true);
                return;
            }

            waited += RECAPTCHA_READY_POLL;
            if (waited >= RECAPTCHA_READY_TIMEOUT) {
                clearInterval(poll);
                resolve(false);
            }
        }, RECAPTCHA_READY_POLL);
    });
}

function hideDefaultCaptchaSection($element, defaultSectionElement) {
    let $form = $element.closest('form');
    let defaultDynamicElement = $(defaultSectionElement);

    if (defaultDynamicElement?.length === 0 || !defaultDynamicElement.data('fallback-shown')) {
        return;
    }

    $form.find('[name="set_default_captcha"]')?.val(0);
    $form.find('.dynamic-default-and-recaptcha-section')?.removeClass('active');
    setDefaultCaptchaRequired(defaultSectionElement, false);
    defaultDynamicElement.addClass('d-none').removeData('fallback-shown');
    $('body').removeClass('default-captcha-active');
}

function claimDefaultCaptchaSession($container) {
    captchaSessionOwners[$container.data('session')] = $container[0];
}

function ownsDefaultCaptchaSession($container) {
    return captchaSessionOwners[$container.data('session')] === $container[0];
}

function refreshDefaultCaptchaImage($container) {
    let baseUrl = $container.data('base-url');
    let session = $container.data('session');

    $container.find('img').attr('src', baseUrl + '?sessionKey=' + session + '&token=' + Math.random());
    claimDefaultCaptchaSession($container);
    getSessionRecaptchaCode(session, $container.find('input'));
}

function activateDefaultCaptchaFallback() {
    let $forms = lastSubmittedRecaptchaForm?.length
        ? lastSubmittedRecaptchaForm
        : $('.render-grecaptcha-response').closest('form');

    $forms.each(function () {
        let $element = $(this).find('.render-grecaptcha-response').first();

        if ($element.length === 0) {
            return;
        }

        $element.val('').removeData('recaptcha-token-at');
        showDefaultCaptchaSection($element, $element.data('default-captcha'));
    });
}

function showDefaultCaptchaSection($element, defaultSectionElement) {
    let $form = $element.closest('form');

    if ($element.next('[name="set_default_captcha"]').length === 0) {
        $element.after('<input type="hidden" name="set_default_captcha" class="set_default_captcha_value" value="1">');
    } else {
        $form.find('[name="set_default_captcha"]')?.val(1);
    }

    $form.find('.dynamic-default-and-recaptcha-section')?.addClass('active');

    if($form.find('.default-captcha-container')?.length > 0){
        refreshDefaultCaptchaImage($form.find('.default-captcha-container'));
    }

    let defaultDynamicElement = $(defaultSectionElement);
    if (defaultDynamicElement?.length > 0) {
        setDefaultCaptchaRequired(defaultSectionElement, true);
        defaultDynamicElement.removeClass('d-none').data('fallback-shown', true);
        $('body').addClass('default-captcha-active');
    }
    $form.find('[type="submit"]').removeAttr('disabled');
}

function generateRecaptcha($element, $input, action, defaultSectionElement) {
    if ($element.data('recaptcha-pending')) {
        return $element.data('recaptcha-pending');
    }

    let $form = $element.closest('form');

    let pending = new Promise(function (resolve) {
        if (!recaptchaSiteKey) {
            showDefaultCaptchaSection($element, defaultSectionElement);
            resolve(false);
            return;
        }

        whenRecaptchaReady().then(function (isReady) {
            if (!isReady) {
                showDefaultCaptchaSection($element, defaultSectionElement);
                resolve(false);
                return;
            }

            try {
                grecaptcha.ready(function () {
                    grecaptcha.execute(recaptchaSiteKey, { action: action })
                        .then(function (token) {
                            $element.val(token);
                            $element.data('recaptcha-token-at', Date.now());
                            $(defaultSectionElement)?.find('[name="default_captcha_value"]')?.val('');
                            hideDefaultCaptchaSection($element, defaultSectionElement);
                            $form.find('[type="submit"]').removeAttr('disabled');
                            resolve(true);
                        })
                        .catch(function () {
                            $element.val('');
                            showDefaultCaptchaSection($element, defaultSectionElement);
                            resolve(false);
                        });
                });
            } catch (err) {
                showDefaultCaptchaSection($element, defaultSectionElement);
                resolve(false);
            }
        });
    });

    $element.data('recaptcha-pending', pending);
    pending.then(function () {
        $element.removeData('recaptcha-pending');
    });

    return pending;
}

function isRecaptchaTokenFresh($element) {
    let issuedAt = $element.data('recaptcha-token-at');

    return !!$element.val() && !!issuedAt && (Date.now() - issuedAt) < RECAPTCHA_TOKEN_MAX_AGE;
}

function findActiveRecaptchaElement($form) {
    let $element = $form.find('.render-grecaptcha-response').first();

    if ($element.length === 0 || $form.find('[name="set_default_captcha"]').val() === '1') {
        return null;
    }

    return $element;
}

function prepareRecaptchaToken($form) {
    let $element = findActiveRecaptchaElement($form);

    if ($element === null) {
        return Promise.resolve(false);
    }

    if ($element.data('recaptcha-pending')) {
        return $element.data('recaptcha-pending');
    }

    if (isRecaptchaTokenFresh($element)) {
        return Promise.resolve(true);
    }

    return generateRecaptcha($element, $element.next('.set_default_captcha_value'), $element.data('action'), $element.data('default-captcha'));
}

function releaseRecaptchaToken($form) {
    let $element = findActiveRecaptchaElement($form);

    if ($element === null) {
        return;
    }

    $element.val('').removeData('recaptcha-token-at');
    generateRecaptcha($element, $element.next('.set_default_captcha_value'), $element.data('action'), $element.data('default-captcha'));
}

$('.render-grecaptcha-response').each(function () {
    let $element = $(this);
    let action = $element.data('action');
    let defaultSectionElement = $element.data('default-captcha');
    let $input = $element.next('.set_default_captcha_value');

    if ($input.length === 0) {
        $element.after('<input type="hidden" name="set_default_captcha" class="set_default_captcha_value" value="">');
        $input = $element.next('.set_default_captcha_value');
    }

    let formElement = $element.closest('form');
    if (!formElement.length) {
        console.warn('No form found for element:', $element);
        return;
    }

    let refreshTimer = null;
    let interactedSinceRefresh = false;

    let refreshToken = function () {
        if ($input.val() === '1') {
            return;
        }
        interactedSinceRefresh = false;
        generateRecaptcha($element, $input, action, defaultSectionElement);
    };

    let scheduleRefresh = function () {
        clearTimeout(refreshTimer);
        refreshTimer = setTimeout(function () {
            if (!interactedSinceRefresh) {
                return;
            }
            refreshToken();
            scheduleRefresh();
        }, RECAPTCHA_TOKEN_MAX_AGE);
    };

    formElement.on('mousedown keydown focusin input change', function () {
        interactedSinceRefresh = true;
        if (!isRecaptchaTokenFresh($element)) {
            refreshToken();
        }
        scheduleRefresh();
    });

    formElement.on('submit', function () {
        lastSubmittedRecaptchaForm = formElement;
        interactedSinceRefresh = true;
        setTimeout(refreshToken, 0);
        scheduleRefresh();
    });
});


$('.default-captcha-container').each(function () {
    let defaultCaptchaContainer = $(this);
    let placeholderText = defaultCaptchaContainer?.data('placeholder') ?? 'Enter captcha value';
    let baseUrl = defaultCaptchaContainer?.data('base-url');
    let session = defaultCaptchaContainer?.data('session');

    let isVisibleCaptcha = !defaultCaptchaContainer.hasClass('d-none');
    let html = `<input type="text" name="default_captcha_value" value=""${isVisibleCaptcha ? ' required' : ''} placeholder="${placeholderText}">`;

    let htmlIcon = `<?xml version="1.0" encoding="utf-8"?>
    <svg width="20px" height="20px" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
    <path d="M5.05028 14.9497C4.65975 14.5592 4.65975 13.9261 5.05028 13.5355C5.4408 13.145 6.07397 13.145 6.46449 13.5355C7.39677 14.4678 8.655 15 10 15C12.7614 15 15 12.7614 15 10C15 9.44772 15.4477 9 16 9C16.5523 9 17 9.44772 17 10C17 13.866 13.866 17 10 17C8.11912 17 6.35391 16.2534 5.05028 14.9497Z" fill="#000000"/>
    <path d="M13.5585 12.832C13.099 13.1384 12.4781 13.0141 12.1718 12.5546C11.8655 12.0951 11.9897 11.4742 12.4492 11.1679L15.4496 9.16787C15.9091 8.86154 16.53 8.98575 16.8363 9.4453C17.1426 9.90484 17.0184 10.5257 16.5589 10.832L13.5585 12.832Z" fill="#000000"/>
    <path d="M18.8321 12.4452C19.1384 12.9048 19.0143 13.5256 18.5547 13.832C18.0952 14.1383 17.4743 14.0142 17.168 13.5546L15.168 10.5546C14.8616 10.0951 14.9858 9.47424 15.4453 9.16789C15.9049 8.86153 16.5257 8.98571 16.8321 9.44524L18.8321 12.4452Z" fill="#000000"/>
    <path d="M14.8571 4.85116C15.2477 5.24168 15.2477 5.87485 14.8571 6.26537C14.4666 6.65589 13.8334 6.65589 13.4429 6.26537C12.5106 5.33309 11.2524 4.8009 9.90738 4.8009C7.14596 4.8009 4.90738 7.03948 4.90738 9.8009C4.90738 10.3532 4.45967 10.8009 3.90738 10.8009C3.3551 10.8009 2.90738 10.3532 2.90738 9.8009C2.90738 5.93491 6.04139 2.8009 9.90738 2.8009C11.7883 2.8009 13.5535 3.54752 14.8571 4.85116Z" fill="#000000"/>
    <path d="M6.34889 6.96887C6.80844 6.66255 7.4293 6.78676 7.73563 7.2463C8.04195 7.70585 7.91775 8.32671 7.4582 8.63304L4.45782 10.633C3.99828 10.9394 3.37741 10.8152 3.07109 10.3556C2.76476 9.89606 2.88897 9.2752 3.34852 8.96887L6.34889 6.96887Z" fill="#000000"/>
<path d="M1.07533 7.35567C0.768977 6.89614 0.893151 6.27527 1.35268 5.96892C1.81221 5.66256 2.43308 5.78674 2.73943 6.24627L4.73943 9.24627C5.04578 9.7058 4.92161 10.3267 4.46208 10.633C4.00255 10.9394 3.38168 10.8152 3.07533 10.3557L1.07533 7.35567Z" fill="#000000"/>
</svg>`;

    html += `<div class="captcha-image-container"><img alt="captcha" src="${baseUrl + '?sessionKey=' + session + '&token=' + Math.random()}">
            <span class="refresh-icon">${htmlIcon}</span></div>`;
    defaultCaptchaContainer.append(html);
    claimDefaultCaptchaSession(defaultCaptchaContainer);

    defaultCaptchaContainer.find('input[name="default_captcha_value"]').on('focus', function () {
        if (!ownsDefaultCaptchaSession(defaultCaptchaContainer)) {
            refreshDefaultCaptchaImage(defaultCaptchaContainer);
        }
    });

    defaultCaptchaContainer.find('.captcha-image-container').on('click', function () {
        if (defaultCaptchaContainer?.find(".refresh-icon")?.hasClass('rotate-active')) {
            defaultCaptchaContainer.find(".refresh-icon").removeClass('rotate-active');
        } else {
            defaultCaptchaContainer.find(".refresh-icon").addClass('rotate-active');
        }
        refreshDefaultCaptchaImage(defaultCaptchaContainer);
    });
});

$(document).ajaxSuccess(function (event, jqXHR, settings, data) {
    if (data?.default_captcha_required) {
        activateDefaultCaptchaFallback();
    }
});

if (defaultCaptchaRequired) {
    activateDefaultCaptchaFallback();
}

function getSessionRecaptchaCode(sessionKey, inputSelector) {
    try {
        let routeGetSessionRecaptchaCode = $(
            "#route-get-session-recaptcha-code"
        );
        let csrfToken = $('meta[name="_token"]').attr("content");
        if (routeGetSessionRecaptchaCode.data("mode").toString() === "dev") {
            let string = ".";
            let intervalId = setInterval(() => {
                if (string === "......") {
                    string = ".";
                }
                string = string + ".";
                $(inputSelector).val(string);
            }, 100);

            setTimeout(() => {
                clearInterval(intervalId);
                $.ajaxSetup({
                    headers: {
                        "X-CSRF-TOKEN": csrfToken,
                    },
                });
                $.ajax({
                    type: "POST",
                    url: $("#route-get-session-recaptcha-code").data("route"),
                    data: {
                        _token: csrfToken,
                        sessionKey: sessionKey,
                    },
                    success: function (response) {
                        $(inputSelector).val(response?.code);
                    },
                });
            }, 1000);
        }
    } catch (e) {
        console.log(e);
    }
}

$(document).ready(function () {
    $('.default-captcha-container').each(function () {
        let routeGetSessionRecaptchaCode = $(
            "#route-get-session-recaptcha-code"
        );
        if (routeGetSessionRecaptchaCode?.data("mode")?.toString() === "dev") {
            try {
                let container = $(this);
                let session = container.data('session');
                let input = container.find('input[name="default_captcha_value"]');

                if (session && input.length) {
                    getSessionRecaptchaCode(session, input);
                }
            } catch (e) {
            }
        }
    });
});
