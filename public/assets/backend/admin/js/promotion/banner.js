"use strict";

let getYesWord = $('#message-yes-word').data('text');
let getNoWord = $('#message-no-word').data('text');
let messageAreYouSureDeleteThis = $('#message-are-you-sure-delete-this').data('text');
let messageYouWillNotAbleRevertThis = $('#message-you-will-not-be-able-to-revert-this').data('text');

function bannerThemeRatio(bannerType) {
    const $map = $('#theme-ratio-map');
    const ratios = $map.data('map') || {};

    return (ratios[$map.data('theme')] || {})[bannerType] ?? '';
}

function refreshBannerForm($form) {
    const $type = $form.find('.banner-type-select');
    const selected = $form.find('.action-display-data').val();

    $form.find('.theme-ratio').text(bannerThemeRatio($type.val()));

    $form.find('.resource-field').each(function () {
        const isSelected = $(this).data('resource') === selected;
        $(this).toggleClass('d--none', !isSelected);
        $(this).find('input, select, textarea').prop('disabled', !isSelected);
    });
}

$(document).on('change', '.banner-type-select, .action-display-data', function () {
    refreshBannerForm($(this).closest('form'));
});

document.addEventListener('submit', function (event) {
    const form = event.target.closest('.banner-form');
    if (!form) return;

    const $form = $(form);
    const messages = [];
    const bannerType = $form.find('.banner-type-select').val();
    const resourceType = $form.find('.action-display-data').val();

    if (!bannerType) messages.push($form.data('msg-banner-type'));
    if (!resourceType) messages.push($form.data('msg-resource-type'));

    if (resourceType === 'custom') {
        const url = ($form.find('.resource-field[data-resource="custom"] input[name="url"]').val() || '').trim();
        if (!/^https?:\/\/.+/.test(url)) messages.push($form.data('msg-url'));
    } else if (resourceType && !$form.find('.resource-field[data-resource="' + resourceType + '"] select').val()) {
        messages.push($form.data('msg-resource-' + resourceType));
    }

    const imageInput = $form.find('input[type="file"][name="image"][required]').get(0);
    if (imageInput && !imageInput.files.length) messages.push($(imageInput).data('required-msg'));

    if (!messages.length) return;

    event.preventDefault();
    event.stopImmediatePropagation();
    messages.forEach((message, index) => setTimeout(() => toastMagic.error(message), index * 400));
}, true);

$(function () {
    $('.banner-form').each(function () {
        refreshBannerForm($(this));
    });
});

$('.banner-delete-button').on('click', function () {
    let bannerId = $(this).attr("id");
    Swal.fire({
        title: messageAreYouSureDeleteThis,
        text: messageYouWillNotAbleRevertThis,
        showCancelButton: true,
        confirmButtonColor: '#1455AC',
        cancelButtonColor: '#d33',
        confirmButtonText: getYesWord,
        cancelButtonText: getNoWord,
        type: 'warning',
        reverseButtons: true
    }).then((result) => {
        if (result.value) {
            $.ajaxSetup({
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="_token"]').attr('content')
                }
            });
            $.ajax({
                url: $('#route-admin-banner-delete').data('url'),
                method: 'POST',
                data: {id: bannerId},
                success: function (response) {
                    toastMagic.success(response.message);
                    location.reload();
                }
            });
        }
    })
});

var backgroundImage = $("[data-bg-img]");
backgroundImage.css("background-image", function () {
    return 'url("' + $(this).data("bg-img") + '")';
}).removeAttr("data-bg-img").addClass("bg-img");

$('.most-demanded-product-delete-button').on('click', function () {
    let productId = $(this).attr("id");
    Swal.fire({
        title: $(this).data('warning-text'),
        text:  $(this).data('text'),
        showCancelButton: true,
        confirmButtonColor: '#1455AC',
        cancelButtonColor: '#d33',
        type: 'warning',
        reverseButtons: true
    }).then((result) => {
        if (result.value) {
            $.ajaxSetup({
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="_token"]').attr('content')
                }
            });
            $.ajax({
                url: $('#route-admin-most-demanded-delete').data('url'),
                method: 'POST',
                data: {id: productId},
                success: function (response) {
                    toastMagic.success(response.message);
                    location.reload()
                }
            });
        }
    })
})

$('.most-demanded-status-form').on('submit', function(event){
    event.preventDefault();
    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="_token"]').attr('content')
        }
    });
    $.ajax({
        url: $(this).attr('action'),
        method: 'POST',
        data: $(this).serialize(),
        success: function (response) {
            toastMagic.success(response.message);
            setTimeout(function (){
                location.reload()
            },1000);
        }
    });
});
