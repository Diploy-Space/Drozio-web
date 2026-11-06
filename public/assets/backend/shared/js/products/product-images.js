"use strict";

function colorWiseImageFunctionality(t) {
    const section = $("#color-wise-image-section");
    const url = section.data("url");

    if (!url) {
        return;
    }

    $.ajax({
        type: "POST",
        url: url,
        headers: {"X-CSRF-TOKEN": $('meta[name="csrf-token"]').attr("content")},
        data: {
            colors: t?.val() ?? [],
            product_id: $("#product_id").val() ?? "",
            product_gallery: $("#product-gallery-page").data("page")?.toString() === "product-gallery" ? 1 : 0,
            removed_images: $('input[name="removed_images[]"]').map(function () {
                return this.value;
            }).get(),
        },
        success: function (response) {
            section.html(response.view);
        },
    });
}

$(document).on("change", ".action-upload-color-image", function () {
    uploadColorImage(this);
});

function uploadColorImage(thisData = null) {
    if (!thisData) {
        return;
    }

    const file = thisData.files[0];

    if (!file) {
        return;
    }

    const acceptAttr = thisData.getAttribute("accept");

    if (acceptAttr) {
        const allowedExtensions = acceptAttr.split(',').map(ext => ext.trim().toLowerCase());
        const fileExtension = '.' + file.name.split('.').pop().toLowerCase();

        if (!allowedExtensions.includes(fileExtension)) {
            toastMagic.error(thisData.dataset.validationErrorMsg || `Only allowed extensions are: ${allowedExtensions.join(', ')}`);
            thisData.value = '';
            return;
        }
    }

    const maxFileSizeLimit = Number(thisData?.dataset?.maxSize || ($('#imageUploadMaxSize').data('max-size') || 20));
    const maxFileSizeLimitAsByte = maxFileSizeLimit * 1024 * 1024;

    if (maxFileSizeLimitAsByte && maxFileSizeLimitAsByte < file.size) {
        toastMagic.error(file.name + ` exceeds ${maxFileSizeLimit}MB`);

        const oversizePreview = document.getElementById(thisData.dataset.imgpreview);

        if (oversizePreview) {
            oversizePreview.setAttribute("src", '');
            oversizePreview.classList.add("d-none");
        }

        const uploadWrapper = thisData.closest(".upload-file");

        setTimeout(function () {
            if (uploadWrapper && typeof resetFileUploadAllImage === 'function') {
                resetFileUploadAllImage(uploadWrapper);
            }
        }, 200);

        return;
    }

    setTimeout(function () {
        const previewImg = document.getElementById(thisData.dataset.imgpreview);

        if (previewImg) {
            previewImg.setAttribute("src", window.URL.createObjectURL(file));
            previewImg.classList.remove("d-none");

            $(thisData).closest(".upload-file").find(".product-image-edit-icon").removeClass("d-none");
        }

        if (thisData.dataset.imgpreview?.toString() === "pre_img_viewer" && !$("#meta_image_input").val()) {
            $("#pre_meta_image_viewer").removeClass("d-none");
            $(".pre-meta-image-viewer")
                .attr("src", window.URL.createObjectURL(file))
                .addClass('d-block')
                .closest('label').find('.upload-file-textbox').addClass('d-none');
        }
    }, 100);
}
