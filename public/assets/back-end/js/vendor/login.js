"use strict";

$(document).on('ready', function () {
    $('.js-toggle-password').each(function () {
        new HSTogglePassword(this).init()
    });
    $('.js-validate').each(function () {
        $.HSCore.components.HSValidation.init($(this));
    });
});

bindAuthFormAjax($('#vendor-login-form'), $('#vendorLoginBtn'), function (response) {
    location.href = response.redirect_url;
});

$('.clear-alter-message').on('click',function (){
    $('.vendor-suspend').addClass('d-none')
})
$('.get-login-recaptcha-verify').on('click', function () {
    document.getElementById('default_recaptcha_id').src = $(this).data('link') + "/" + Math.random()+'?captcha_session_id=vendorRecaptchaSessionKey';
});

$('#copyLoginInfo').on('click', function () {
    let vendorEmail = $('#vendor-email').data('email');
    let vendorPassword = $('#vendor-password').data('password');
    $('#signingVendorEmail').val(vendorEmail);
    $('#signingVendorPassword').val(vendorPassword);
    toastMagic.success($('#message-copied_success').data('text'));
});

$('.onerror-logo').on('error', function () {
    let image = $('#onerror-logo').data('onerror-logo');
    $(this).attr('src', image);
});


function getSessionRecaptchaCode(sessionKey, inputSelector) {
    try {
        let routeGetSessionRecaptchaCode = $('#route-get-session-recaptcha-code');
        if (routeGetSessionRecaptchaCode.data('mode').toString() === 'dev') {
            let string = '.';
            let intervalId = setInterval(() => {
                if (string === '......') {
                    string = '.';
                }
                string = string + '.';
                $(inputSelector).val(string);
            }, 100);

            setTimeout(() => {
                clearInterval(intervalId);
                $.ajaxSetup({
                    headers: {
                        "X-CSRF-TOKEN": $('meta[name="_token"]').attr("content"),
                    },
                });
                $.ajax({
                    type: "POST",
                    url: $('#route-get-session-recaptcha-code').data('route'),
                    data: {
                        _token: $('meta[name="_token"]').attr("content"),
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

$('.get-session-recaptcha-auto-fill').each(function () {
    getSessionRecaptchaCode($(this).data('session'), $(this).data('input'))
});

$('.get-session-recaptcha-auto-fill').on('click', function () {
    getSessionRecaptchaCode($(this).data('session'), $(this).data('input'));
});
